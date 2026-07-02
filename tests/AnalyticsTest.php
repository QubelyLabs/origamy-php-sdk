<?php

declare(strict_types=1);

namespace Origamy\Tests;

use Origamy\AnalyticsClient;
use Origamy\Config;
use Origamy\Context;
use Origamy\Exceptions\ConfigException;
use Origamy\Exceptions\FieldException;
use Origamy\Integrations;
use Origamy\MessageInterface;
use Origamy\Messages\Alias;
use Origamy\Messages\Group;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Track;
use Origamy\Properties;
use Origamy\Tests\Helpers\MockDispatcher;
use Origamy\Tests\Helpers\TestCallback;
use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase
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

    private function fixture(string $name): array
    {
        $path = __DIR__ . '/fixtures/' . $name;
        $json = file_get_contents($path);
        $this->assertIsString($json, "Fixture file not found: $name");
        return json_decode($json, true);
    }

    private function makeConfig(MockDispatcher $mock, array $opts = []): Config
    {
        return new Config(
            batchSize:   $opts['batchSize']   ?? 1,
            dispatcher:  $mock,
            uid:         fn () => $this->mockId(),
            now:         fn () => $this->mockTime(),
            verbose:     $opts['verbose']     ?? false,
            callback:    $opts['callback']    ?? null,
            retryAfter:  $opts['retryAfter']  ?? null,
            defaultContext: $opts['defaultContext'] ?? null,
        );
    }

    /** Create client via newWithConfig and assert no error. */
    private function newClient(MockDispatcher $mock, array $opts = []): AnalyticsClient
    {
        [$client, $err] = AnalyticsClient::newWithConfig('h97jamjwbh', $this->makeConfig($mock, $opts));
        $this->assertNull($err, 'Expected no config error');
        return $client;
    }

    // -----------------------------------------------------------------------
    // Enqueue fixture tests (all 6 message types)
    // -----------------------------------------------------------------------

    public function testEnqueueAlias(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Alias('A', 'B'));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-alias.json'),
            $mock->getDecoded(0),
        );
    }

    public function testEnqueueGroup(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Group('A', 'B'));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-group.json'),
            $mock->getDecoded(0),
        );
    }

    public function testEnqueueIdentify(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Identify('B'));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-identify.json'),
            $mock->getDecoded(0),
        );
    }

    public function testEnqueuePage(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Page('B', '', 'A'));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-page.json'),
            $mock->getDecoded(0),
        );
    }

    public function testEnqueueScreen(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Screen(userId: 'B', name: 'A'));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-screen.json'),
            $mock->getDecoded(0),
        );
    }

    public function testEnqueueTrack(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);
        $client->enqueue(new Track('Download', '123456', properties: (new Properties())
            ->set('application', 'Segment Desktop')
            ->set('version', '1.1.0')
            ->set('platform', 'osx'),
        ));
        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-enqueue-track.json'),
            $mock->getDecoded(0),
        );
    }

    // -----------------------------------------------------------------------
    // Custom message type
    // -----------------------------------------------------------------------

    public function testEnqueueCustomTypeFails(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $custom = new class implements MessageInterface {
            public function validate(): void {}
            public function toArray(): array { return []; }
            public function jsonSerialize(): mixed { return []; }
        };

        $this->expectException(\InvalidArgumentException::class);
        $client->enqueue($custom);
        $client->close();
    }

    // -----------------------------------------------------------------------
    // Timestamp / messageId / context / many / integrations
    // -----------------------------------------------------------------------

    public function testTrackWithTimestamp(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $client->enqueue(new Track(
            event:     'Download',
            userId:    '123456',
            timestamp: new \DateTimeImmutable('2015-07-10T23:00:00Z'),
            properties: (new Properties())
                ->set('application', 'Segment Desktop')
                ->set('version', '1.1.0')
                ->set('platform', 'osx'),
        ));
        $client->close();

        $this->assertEquals(
            $this->fixture('test-timestamp-track.json'),
            $mock->getDecoded(0),
        );
    }

    public function testTrackWithMessageId(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $client->enqueue(new Track(
            event:     'Download',
            userId:    '123456',
            messageId: 'abc',
            properties: (new Properties())
                ->set('application', 'Segment Desktop')
                ->set('version', '1.1.0')
                ->set('platform', 'osx'),
        ));
        $client->close();

        $this->assertEquals(
            $this->fixture('test-messageid-track.json'),
            $mock->getDecoded(0),
        );
    }

    public function testTrackWithContext(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $ctx        = new Context();
        $ctx->extra = ['whatever' => 'here'];

        $client->enqueue(new Track(
            event:   'Download',
            userId:  '123456',
            context: $ctx,
            properties: (new Properties())
                ->set('application', 'Segment Desktop')
                ->set('version', '1.1.0')
                ->set('platform', 'osx'),
        ));
        $client->close();

        $this->assertEquals(
            $this->fixture('test-context-track.json'),
            $mock->getDecoded(0),
        );
    }

    public function testTrackMany(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock, ['batchSize' => 3]);

        for ($i = 0; $i < 5; $i++) {
            $client->enqueue(new Track(
                event:  'Download',
                userId: '123456',
                properties: (new Properties())
                    ->set('application', 'Segment Desktop')
                    ->set('version', $i),
            ));
        }
        $client->close();

        // First flush: 3 messages
        $this->assertEquals(
            $this->fixture('test-many-track.json'),
            $mock->getDecoded(0),
        );
    }

    public function testTrackWithIntegrations(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $intg = new Integrations();
        $intg->set('All', true);
        $intg->set('Intercom', false);
        $intg->set('Mixpanel', true);

        $client->enqueue(new Track(
            event:        'Download',
            userId:       '123456',
            integrations: $intg,
            properties: (new Properties())
                ->set('application', 'Segment Desktop')
                ->set('version', '1.1.0')
                ->set('platform', 'osx'),
        ));
        $client->close();

        $this->assertEquals(
            $this->fixture('test-integrations-track.json'),
            $mock->getDecoded(0),
        );
    }

    // -----------------------------------------------------------------------
    // Flush on close (PHP equivalent of the Go interval test)
    // -----------------------------------------------------------------------

    public function testFlushOnClose(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock, ['batchSize' => 100]);

        $client->enqueue(new Track(
            event:  'Download',
            userId: '123456',
            properties: (new Properties())
                ->set('application', 'Segment Desktop')
                ->set('version', '1.1.0')
                ->set('platform', 'osx'),
        ));

        // Not flushed yet (batch size not reached)
        $this->assertEmpty($mock->payloads);

        $client->close();

        $this->assertCount(1, $mock->payloads);
        $this->assertEquals(
            $this->fixture('test-interval-track.json'),
            $mock->getDecoded(0),
        );
    }

    // -----------------------------------------------------------------------
    // Client lifecycle
    // -----------------------------------------------------------------------

    public function testClientCloseTwice(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $client->close();

        $this->expectException(\RuntimeException::class);
        $client->close();
    }

    public function testEnqueueAfterClose(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $client->close();

        $this->expectException(\RuntimeException::class);
        $client->enqueue(new Track('Event', 'u1'));
    }

    public function testClientConfigError(): void
    {
        [$client, $err] = AnalyticsClient::newWithConfig('key', new Config(intervalMs: -1));

        $this->assertNull($client);
        $this->assertInstanceOf(ConfigException::class, $err);
    }

    public function testClientEnqueueValidationError(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $this->expectException(FieldException::class);
        $client->enqueue(new Track('', 'u1')); // missing event
    }

    // -----------------------------------------------------------------------
    // Callbacks
    // -----------------------------------------------------------------------

    public function testClientSuccessCallback(): void
    {
        $successes = [];
        $failures  = [];

        $mock     = new MockDispatcher();
        $callback = new TestCallback(
            onSuccess: function (MessageInterface $m) use (&$successes) { $successes[] = $m; },
            onFailure: function (MessageInterface $m, \Throwable $e) use (&$failures) { $failures[] = $e; },
        );

        $client = $this->newClient($mock, ['callback' => $callback]);
        $client->enqueue(new Track('Event', 'u1'));
        $client->close();

        $this->assertCount(1, $successes);
        $this->assertEmpty($failures, 'No failures expected');
    }

    public function testClientMarshalMessageError(): void
    {
        $failures = [];

        $mock     = new MockDispatcher();
        $callback = new TestCallback(
            onFailure: function (MessageInterface $m, \Throwable $e) use (&$failures) {
                $failures[] = $e;
            },
        );

        $client = $this->newClient($mock, ['callback' => $callback]);

        // NAN cannot be JSON-encoded; json_encode returns false
        $props = new Properties();
        $props->set('invalid', NAN);

        $client->enqueue(new Track('Event', 'u1', properties: $props));
        $client->close();

        $this->assertCount(1, $failures);
        $this->assertInstanceOf(\RuntimeException::class, $failures[0]);
        $this->assertStringContainsString('serialize', $failures[0]->getMessage());
    }

    public function testClientMarshalContextError(): void
    {
        $failures = [];

        $mock     = new MockDispatcher();
        $callback = new TestCallback(
            onFailure: function (MessageInterface $m, \Throwable $e) use (&$failures) {
                $failures[] = $e;
            },
        );

        $defaultCtx        = new Context();
        $defaultCtx->extra = ['invalid' => NAN];

        $client = $this->newClient($mock, [
            'callback'       => $callback,
            'defaultContext' => $defaultCtx,
        ]);

        $client->enqueue(new Track('Event', 'u1'));
        $client->close();

        $this->assertCount(1, $failures);
        $this->assertInstanceOf(\RuntimeException::class, $failures[0]);
    }

    public function testClientRetryError(): void
    {
        $failures = [];

        $mock        = new MockDispatcher();
        $mock->error = new \RuntimeException('transport error');

        $callback = new TestCallback(
            onFailure: function (MessageInterface $m, \Throwable $e) use (&$failures) {
                $failures[] = $e;
            },
        );

        $client = $this->newClient($mock, [
            'callback'   => $callback,
            'batchSize'  => 1,
            'retryAfter' => fn (int $n) => 0, // no wait between retries
        ]);

        $client->enqueue(new Track('Event', 'u1'));
        // With batchSize=1, flush (and all retries) happen synchronously inside enqueue()
        $this->assertCount(1, $failures);
        $this->assertSame('transport error', $failures[0]->getMessage());

        $client->close();
    }

    public function testClientResponse400(): void
    {
        $failures = [];

        $mock        = new MockDispatcher();
        $mock->error = new \RuntimeException('400 response from API');

        $callback = new TestCallback(
            onFailure: function (MessageInterface $m, \Throwable $e) use (&$failures) {
                $failures[] = $e;
            },
        );

        $client = $this->newClient($mock, [
            'callback'   => $callback,
            'batchSize'  => 1,
            'retryAfter' => fn (int $n) => 0,
        ]);

        $client->enqueue(new Track('Event', 'u1'));
        $this->assertNotEmpty($failures);

        $client->close();
    }

    // -----------------------------------------------------------------------
    // sentAt format
    // -----------------------------------------------------------------------

    public function testSentAtHasMilliseconds(): void
    {
        $mock   = new MockDispatcher();
        $client = $this->newClient($mock);

        $client->enqueue(new Track('E', 'u1'));
        $client->close();

        $body   = $mock->getDecoded(0);
        $sentAt = $body['sentAt'] ?? '';

        // Must end with Z and contain exactly 3 decimal places
        $this->assertSame('2009-11-10T23:00:00.000Z', $sentAt);
    }

    // -----------------------------------------------------------------------
    // Default endpoint
    // -----------------------------------------------------------------------

    public function testDefaultEndpointIsOrigamy(): void
    {
        $this->assertSame('https://events.origamy.io', Config::DEFAULT_ENDPOINT);
    }
}
