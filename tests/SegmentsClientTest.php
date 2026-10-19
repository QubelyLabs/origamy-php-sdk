<?php

declare(strict_types=1);

namespace Origamy\Tests;

use Origamy\Exceptions\SegmentException;
use Origamy\SegmentsClient;
use Origamy\Tests\Helpers\SegmentsMockServer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('http')]
class SegmentsClientTest extends TestCase
{
    private SegmentsMockServer $server;

    protected function setUp(): void
    {
        $this->server = new SegmentsMockServer();
    }

    protected function tearDown(): void
    {
        $this->server->stop();
    }

    private function client(): SegmentsClient
    {
        return new SegmentsClient('sk_test', $this->server->getUrl(), retryAfter: static fn (int $a): int => 0);
    }

    public function testAddMembersSendsRequest(): void
    {
        $this->server->start();

        $res = $this->client()->addMembers(42, [
            ['email' => 'jane@example.com', 'traits' => ['first_name' => 'Jane']],
            ['phone' => '0803 123 4567'],
            ['user_id' => 'user-42', 'ignored' => 'x'],
        ], phoneRegion: 'NG');

        $this->assertSame(42, $res->segmentId);
        $this->assertSame(3, $res->added);
        $this->assertSame([], $res->rejected);

        $reqs = $this->server->requests();
        $this->assertCount(1, $reqs);
        $this->assertSame('POST', $reqs[0]['method']);
        $this->assertSame('/v1/segments/42/members', $reqs[0]['path']);
        $this->assertSame('sk_test', $reqs[0]['authUser']);
        $this->assertSame('', $reqs[0]['authPass']);
        $this->assertSame([
            'add' => [
                ['email' => 'jane@example.com', 'traits' => ['first_name' => 'Jane']],
                ['phone' => '0803 123 4567'],
                ['userId' => 'user-42'],
            ],
            'phoneRegion' => 'NG',
        ], $reqs[0]['body']);
    }

    public function testMembersAndTraitsAreJsonObjects(): void
    {
        $this->server->start();

        $this->client()->addMembers(42, [['email' => 'a@example.com', 'traits' => []], 'not an array']);

        $raw = $this->server->requests()[0]['raw'];
        $this->assertSame('{"add":[{"email":"a@example.com"},{}]}', $raw);
    }

    public function testRemoveMembersSendsRemoveList(): void
    {
        $this->server->start([[
            'status' => 200,
            'body'   => ['segment_id' => 7, 'added' => 0, 'removed' => 1, 'not_found' => 1, 'rejected' => []],
        ]]);

        $res = $this->client()->removeMembers(7, [['email' => 'a@example.com'], ['userId' => 'u2']]);

        $this->assertSame(1, $res->removed);
        $this->assertSame(1, $res->notFound);
        $this->assertSame(
            ['remove' => [['email' => 'a@example.com'], ['userId' => 'u2']]],
            $this->server->requests()[0]['body'],
        );
    }

    public function testSplitsLongListsAndCombinesResults(): void
    {
        $this->server->start();

        $members = [];
        for ($i = 0; $i < 2500; $i++) {
            $members[] = ['email' => "p{$i}@example.com"];
        }
        $members[5]['email']    = 'bad';
        $members[1500]['email'] = 'bad';
        $members[2499]['email'] = 'bad';

        $res = $this->client()->addMembers(42, $members, phoneRegion: 'GB');

        $reqs = $this->server->requests();
        $this->assertSame([1000, 1000, 500], array_map(static fn ($r) => count($r['body']['add']), $reqs));
        foreach ($reqs as $r) {
            $this->assertSame('GB', $r['body']['phoneRegion']);
        }
        $this->assertSame('p1000@example.com', $reqs[1]['body']['add'][0]['email']);
        $this->assertSame(2497, $res->added);
        $this->assertSame([5, 1500, 2499], array_column($res->rejected, 'index'));
        $this->assertSame(['add', 'add', 'add'], array_column($res->rejected, 'list'));
    }

    public function testEmptyListSendsNothing(): void
    {
        $this->server->start();

        $res = $this->client()->addMembers(42, []);

        $this->assertSame(0, $res->added);
        $this->assertCount(0, $this->server->requests());
    }

    public static function apiErrors(): array
    {
        return [
            'not found'  => [404, ['error' => 'segment not found', 'code' => 'segment_not_found'], 'segment_not_found'],
            'not a list' => [409, ['error' => 'computed from conditions', 'code' => 'segment_not_list'], 'segment_not_list'],
            'managed'    => [409, ['error' => 'mirrors a group', 'code' => 'segment_managed'], 'segment_managed'],
            'invalid'    => [400, ['error' => 'more than 1000 rows', 'code' => 'invalid_request'], 'invalid_request'],
            'public key' => [403, ['error' => 'changing segment members needs a secret (sk_) key'], null],
        ];
    }

    #[DataProvider('apiErrors')]
    public function testApiErrorsThrowWithoutRetry(int $status, array $body, ?string $code): void
    {
        $this->server->start([['status' => $status, 'body' => $body]]);

        try {
            $this->client()->addMembers(42, [['email' => 'a@example.com']]);
            $this->fail('expected SegmentException');
        } catch (SegmentException $e) {
            $this->assertSame($status, $e->statusCode);
            $this->assertSame($code, $e->errorCode);
            $this->assertStringContainsString($body['error'], $e->getMessage());
        }
        $this->assertCount(1, $this->server->requests());
    }

    public function testRetriesRateLimitAndUnavailable(): void
    {
        $this->server->start([
            ['status' => 429, 'body' => ['error' => 'rate limit exceeded'], 'headers' => ['Retry-After' => '1']],
            ['status' => 503, 'body' => ['error' => 'did not answer', 'code' => 'unavailable']],
            ['echo' => true],
        ]);

        $started = microtime(true);
        $res     = $this->client()->addMembers(42, [['email' => 'a@example.com']]);

        $this->assertSame(1, $res->added);
        $this->assertCount(3, $this->server->requests());
        $this->assertGreaterThanOrEqual(0.9, microtime(true) - $started, 'Retry-After was not honoured');
    }

    public function testGivesUpAfterThreeAttempts(): void
    {
        $this->server->start([['status' => 503, 'body' => ['error' => 'did not answer', 'code' => 'unavailable']]]);

        try {
            $this->client()->addMembers(42, [['email' => 'a@example.com']]);
            $this->fail('expected SegmentException');
        } catch (SegmentException $e) {
            $this->assertSame('unavailable', $e->errorCode);
        }
        $this->assertCount(3, $this->server->requests());
    }

    public function testFailedChunkCarriesEarlierResults(): void
    {
        $this->server->start([
            ['echo' => true],
            ['status' => 400, 'body' => ['error' => 'invalid phone region', 'code' => 'invalid_request']],
        ]);

        $members = [];
        for ($i = 0; $i < 1500; $i++) {
            $members[] = ['userId' => (string) $i];
        }

        try {
            $this->client()->addMembers(42, $members);
            $this->fail('expected SegmentException');
        } catch (SegmentException $e) {
            $this->assertSame('invalid_request', $e->errorCode);
            $this->assertNotNull($e->result);
            $this->assertSame(1000, $e->result->added);
        }
    }

    public function testConnectionFailureThrows(): void
    {
        $client = new SegmentsClient('sk_test', 'http://127.0.0.1:1', retryAfter: static fn (int $a): int => 0);

        try {
            $client->addMembers(42, [['userId' => 'u1']]);
            $this->fail('expected SegmentException');
        } catch (SegmentException $e) {
            $this->assertSame(0, $e->statusCode);
        }
    }

    public function testRejectsNonPositiveSegmentId(): void
    {
        $this->expectException(SegmentException::class);
        (new SegmentsClient('sk_test', 'http://127.0.0.1:1'))->addMembers(0, [['userId' => 'u1']]);
    }
}
