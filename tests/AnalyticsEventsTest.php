<?php

declare(strict_types=1);

namespace Origamy\Tests;

use Origamy\AnalyticsClient;
use Origamy\CampaignInfo;
use Origamy\Config;
use Origamy\Context;
use Origamy\DeviceInfo;
use Origamy\LibraryInfo;
use Origamy\MessageInterface;
use Origamy\Messages\Alias;
use Origamy\Messages\Group;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Track;
use Origamy\OSInfo;
use Origamy\PageInfo;
use Origamy\Properties;
use Origamy\ScreenInfo;
use Origamy\Traits as OrigamyTraits;
use Origamy\Tests\Helpers\MockDispatcher;
use Origamy\Tests\Helpers\MockHttpServer;
use PHPUnit\Framework\TestCase;

class AnalyticsEventsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function mockTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2009-11-10T23:00:00Z');
    }

    private function mockId(): string
    {
        return "I'm unique";
    }

    private function newMockClient(MockDispatcher $mock, array $opts = []): AnalyticsClient
    {
        [$client, $err] = AnalyticsClient::newWithConfig(
            $opts['writeKey'] ?? 'wk',
            new Config(
                batchSize:  $opts['batchSize'] ?? 1,
                dispatcher: $mock,
                uid:        fn () => $this->mockId(),
                now:        fn () => $this->mockTime(),
            ),
        );
        $this->assertNull($err);
        return $client;
    }

    // -----------------------------------------------------------------------
    // HTTP wire-format tests using real HttpDispatcher + mock server
    // -----------------------------------------------------------------------

    /**
     * @group http
     */
    public function testHTTPRequestFormat(): void
    {
        $server = new MockHttpServer();
        try {
            $server->start();

            [$client, $err] = AnalyticsClient::newWithConfig('my-write-key', new Config(
                endpoint:  $server->getUrl(),
                batchSize: 1,
                uid:       fn () => $this->mockId(),
                now:       fn () => $this->mockTime(),
            ));
            $this->assertNull($err);

            $client->enqueue(new Track('Test', 'u1'));
            $client->close();

            $req = $server->readRequest();

            $this->assertSame('POST', $req['method']);
            $this->assertSame('/v1/batch', $req['path']);
            $this->assertStringContainsString('application/json', $req['contentType']);
            $this->assertTrue($req['authOK'], 'Missing Basic auth header');
            $this->assertSame('my-write-key', $req['authUser']);
            $this->assertSame('', $req['authPass']);
        } finally {
            $server->stop();
        }
    }

    /**
     * @group http
     */
    public function testWriteKeyIsCorrectlyEncoded(): void
    {
        $specialKey = 'wk+special/chars==abc123';
        $server     = new MockHttpServer();
        try {
            $server->start();

            [$client, $err] = AnalyticsClient::newWithConfig($specialKey, new Config(
                endpoint:  $server->getUrl(),
                batchSize: 1,
                uid:       fn () => $this->mockId(),
                now:       fn () => $this->mockTime(),
            ));
            $this->assertNull($err);

            $client->enqueue(new Track('E', 'u1'));
            $client->close();

            $req = $server->readRequest();
            $this->assertTrue($req['authOK']);
            $this->assertSame($specialKey, $req['authUser']);
            $this->assertSame('', $req['authPass']);
        } finally {
            $server->stop();
        }
    }

    // -----------------------------------------------------------------------
    // Batch body structure (payload-level checks via MockDispatcher)
    // -----------------------------------------------------------------------

    public function testBatchBodyStructure(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track('Test', 'u1'));
        $client->close();

        $body = $mock->getDecoded(0);

        // Must have batch array
        $this->assertArrayHasKey('batch', $body);
        $this->assertIsArray($body['batch']);
        $this->assertNotEmpty($body['batch']);

        // Must have sentAt in ISO 8601 with milliseconds
        $sentAt = $body['sentAt'] ?? '';
        $this->assertStringEndsWith('Z', $sentAt);
        $this->assertStringContainsString('.', $sentAt);

        // Must NOT have messageId or context at batch level
        $this->assertArrayNotHasKey('messageId', $body);
        $this->assertArrayNotHasKey('context', $body);
    }

    public function testContextIsPerEvent(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track('Test', 'u1'));
        $client->close();

        $body  = $mock->getDecoded(0);
        $event = $body['batch'][0];

        $this->assertArrayHasKey('context', $event);
        $lib = $event['context']['library'] ?? null;
        $this->assertNotNull($lib, 'Event context must have library');
        $this->assertSame('origamy-php', $lib['name']);
        $this->assertSame(Config::VERSION, $lib['version']);
    }

    public function testDefaultEndpointIsOrigamy(): void
    {
        $this->assertSame('https://api.origamy.com', Config::DEFAULT_ENDPOINT);
    }

    public function testSentAtFormat(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track('Test', 'u1'));
        $client->close();

        $sentAt = $mock->getDecoded(0)['sentAt'] ?? '';
        $this->assertSame('2009-11-10T23:00:00.000Z', $sentAt);
    }

    public function testCustomTimestampOnEvent(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track(
            event:     'Christmas Present Opened',
            userId:    'user-ts',
            timestamp: new \DateTimeImmutable('2023-12-25T09:00:00Z'),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $this->assertSame('2023-12-25T09:00:00Z', $event['timestamp']);
    }

    // -----------------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------------

    public function testValidationRejectsInvalidMessages(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $cases = [
            'Track missing Event'        => new Track('', 'u1'),
            'Track missing user ID'      => new Track('E', ''),
            'Group missing GroupId'      => new Group('', 'u1'),
            'Alias missing UserId'       => new Alias('p1', ''),
            'Alias missing PreviousId'   => new Alias('', 'u1'),
        ];

        foreach ($cases as $name => $msg) {
            try {
                $client->enqueue($msg);
                $this->fail("$name: expected validation error, got none");
            } catch (\Throwable $e) {
                $this->addToAssertionCount(1);
            }
        }
        $client->close();
    }

    // -----------------------------------------------------------------------
    // Realistic sample event tests
    // -----------------------------------------------------------------------

    public function testUserSignupFlow(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Identify(
            userId:      'new-user-99',
            anonymousId: 'anon-browser-xyz',
            traits:      (new OrigamyTraits())
                ->setEmail('bob@example.com')
                ->setFirstName('Bob')
                ->setLastName('Jones')
                ->set('company', 'Acme Inc')
                ->set('createdAt', '2024-06-01T12:00:00Z'),
        ));
        $client->close();

        $body  = $mock->getDecoded(0);
        $event = $body['batch'][0];

        $this->assertSame('identify',         $event['type']);
        $this->assertSame('new-user-99',       $event['userId']);
        $this->assertSame('anon-browser-xyz', $event['anonymousId']);
        $this->assertSame('bob@example.com',  $event['traits']['email']);
    }

    public function testAnonymousUserTracking(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track(
            event:       'Page Scrolled',
            anonymousId: 'anon-abc123',
            properties:  (new Properties())
                ->set('depth', 75)
                ->set('direction', 'down'),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];

        $this->assertSame('anon-abc123', $event['anonymousId']);
        $this->assertArrayNotHasKey('userId', $event);
    }

    public function testIdentifyWithFullTraits(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Identify(
            userId: 'user-full',
            traits: (new OrigamyTraits())
                ->setEmail('full@example.com')
                ->setFirstName('Jane')
                ->setLastName('Doe')
                ->setName('Jane Doe')
                ->setPhone('+15551234567')
                ->setUsername('janedoe')
                ->setWebsite('https://jane.dev')
                ->setAvatar('https://cdn.example.com/avatar.jpg')
                ->setAge(28)
                ->setGender('female')
                ->setBirthday(new \DateTimeImmutable('1996-03-15T00:00:00Z'))
                ->set('plan', 'enterprise')
                ->set('logins', 42),
        ));
        $client->close();

        $traits = $mock->getDecoded(0)['batch'][0]['traits'];

        $checks = [
            'email'     => 'full@example.com',
            'firstName' => 'Jane',
            'lastName'  => 'Doe',
            'name'      => 'Jane Doe',
            'phone'     => '+15551234567',
            'username'  => 'janedoe',
            'website'   => 'https://jane.dev',
        ];
        foreach ($checks as $field => $want) {
            $this->assertSame($want, $traits[$field] ?? null, "Trait $field mismatch");
        }
    }

    public function testPageEventWithProperties(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $ctx           = new Context();
        $ctx->page     = new PageInfo(path: '/pricing', title: 'Pricing Plans', referrer: 'https://google.com', url: 'https://example.com/pricing');
        $ctx->userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)';
        $ctx->locale    = 'en-US';

        $client->enqueue(new Page(
            userId: 'user-1',
            name:   'Pricing',
            context: $ctx,
            properties: (new Properties())
                ->set('url', 'https://example.com/pricing')
                ->set('path', '/pricing')
                ->set('title', 'Pricing Plans')
                ->set('referrer', 'https://google.com')
                ->set('search', '?plan=pro'),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $this->assertSame('page', $event['type']);
        $this->assertSame('Pricing', $event['name']);
        $this->assertSame('https://example.com/pricing', $event['properties']['url']);
    }

    public function testScreenEventWithProperties(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $ctx         = new Context();
        $ctx->os     = new OSInfo(name: 'iOS', version: '17.2');
        $ctx->device = new DeviceInfo(manufacturer: 'Apple', model: 'iPhone 15', type: 'mobile');
        $ctx->screen = new ScreenInfo(width: 390, height: 844, density: 3);

        $client->enqueue(new Screen(
            userId:  'mobile-user-7',
            name:    'Dashboard',
            context: $ctx,
            properties: (new Properties())
                ->set('category', 'Main')
                ->set('tab', 'overview'),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $this->assertSame('screen', $event['type']);
        $this->assertSame('Dashboard', $event['name']);
        $this->assertSame('iOS', $event['context']['os']['name']);
    }

    public function testGroupEventWithCompanyTraits(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Group(
            groupId: 'company-acme',
            userId:  'employee-55',
            traits:  (new OrigamyTraits())
                ->setName('Acme Corp')
                ->setEmail('contact@acme.com')
                ->setWebsite('https://acme.com')
                ->set('industry', 'Software')
                ->set('employees', 500)
                ->set('plan', 'enterprise')
                ->set('mrr', 12000),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $this->assertSame('group', $event['type']);
        $this->assertSame('company-acme', $event['groupId']);
        $this->assertSame('Acme Corp', $event['traits']['name']);
        $this->assertSame('https://acme.com', $event['traits']['website']);
    }

    public function testAliasFlow(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Alias('anon-session-xyz', 'identified-user-1'));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $this->assertSame('alias', $event['type']);
        $this->assertSame('identified-user-1', $event['userId']);
        $this->assertSame('anon-session-xyz', $event['previousId']);
    }

    public function testTrackWithRichContext(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $ctx           = new Context();
        $ctx->library  = new LibraryInfo(name: 'origamy-php', version: Config::VERSION);
        $ctx->userAgent = 'Go-http-client/2.0';
        $ctx->locale    = 'en-GB';
        $ctx->timezone  = 'Europe/London';
        $ctx->campaign  = new CampaignInfo(name: 'spring-promo', source: 'email', medium: 'newsletter');

        $client->enqueue(new Track(
            event:   'Button Clicked',
            userId:  'user-ctx',
            context: $ctx,
            properties: (new Properties())
                ->set('button', 'upgrade')
                ->set('page', '/dashboard'),
        ));
        $client->close();

        $event = $mock->getDecoded(0)['batch'][0];
        $ctx   = $event['context'];

        $this->assertSame('en-GB',         $ctx['locale']);
        $this->assertSame('Europe/London', $ctx['timezone']);
        $this->assertSame('spring-promo',  $ctx['campaign']['name']);
        $this->assertSame('email',         $ctx['campaign']['source']);
    }

    public function testTrackWithEcommerceProperties(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newMockClient($mock);

        $client->enqueue(new Track(
            event:  'Order Completed',
            userId: 'buyer-7',
            properties: (new Properties())
                ->set('orderId', 'ORDER-9999')
                ->setRevenue(249.95)
                ->setCurrency('EUR')
                ->setDiscount(25.00)
                ->setShipping(9.99)
                ->setTax(20.00)
                ->set('products', [
                    ['sku' => 'SKU-A', 'name' => 'Widget Pro',  'price' => 199.99, 'quantity' => 1],
                    ['sku' => 'SKU-B', 'name' => 'Widget Case', 'price' => 49.96,  'quantity' => 1],
                ]),
        ));
        $client->close();

        $props = $mock->getDecoded(0)['batch'][0]['properties'];
        $this->assertSame(249.95, $props['revenue']);
        $this->assertSame('EUR', $props['currency']);
        $this->assertCount(2, $props['products']);
    }

    public function testMultipleEventsInOneBatch(): void
    {
        $mock   = new MockDispatcher();
        [$client, $err] = AnalyticsClient::newWithConfig('wk', new Config(
            batchSize:  5,
            dispatcher: $mock,
            uid:        fn () => $this->mockId(),
            now:        fn () => $this->mockTime(),
        ));
        $this->assertNull($err);

        for ($i = 0; $i < 5; $i++) {
            $client->enqueue(new Track(
                event:      'Step Completed',
                userId:     'user-bulk',
                properties: (new Properties())->set('step', $i),
            ));
        }
        $client->close();

        // All 5 events should be in one batch
        $batch = $mock->getDecoded(0)['batch'];
        $this->assertCount(5, $batch);
    }

    public function testEcommerceCheckoutFlow(): void
    {
        $mock   = new MockDispatcher();
        [$client, $err] = AnalyticsClient::newWithConfig('shop-write-key', new Config(
            batchSize:  10,
            dispatcher: $mock,
            uid:        fn () => $this->mockId(),
            now:        fn () => $this->mockTime(),
        ));
        $this->assertNull($err);

        $client->enqueue(new Identify(
            userId: 'customer-42',
            traits: (new OrigamyTraits())
                ->setEmail('alice@example.com')
                ->setName('Alice Smith')
                ->set('plan', 'premium')
                ->set('signedUpAt', '2024-01-01T00:00:00Z'),
        ));

        $client->enqueue(new Track(
            event:  'Product Viewed',
            userId: 'customer-42',
            properties: (new Properties())
                ->set('productId', 'prod-789')
                ->set('name', 'Running Shoes')
                ->set('category', 'Footwear')
                ->setPrice(89.99)
                ->setCurrency('USD'),
        ));

        $client->enqueue(new Track(
            event:  'Product Added',
            userId: 'customer-42',
            properties: (new Properties())
                ->set('productId', 'prod-789')
                ->set('quantity', 1)
                ->setPrice(89.99)
                ->setCurrency('USD'),
        ));

        $client->enqueue(new Track(
            event:  'Order Completed',
            userId: 'customer-42',
            properties: (new Properties())
                ->set('orderId', 'order-001')
                ->setRevenue(89.99)
                ->setCurrency('USD'),
        ));

        $client->close();

        // All 4 events in one batch (batchSize=10)
        $batch     = $mock->getDecoded(0)['batch'];
        $typesSeen = array_column($batch, 'type');

        $this->assertContains('identify', $typesSeen);
        $this->assertContains('track',    $typesSeen);
        $this->assertCount(4, $batch);
    }
}
