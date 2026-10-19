<?php

declare(strict_types=1);

namespace Origamy;

use Origamy\Exceptions\SegmentException;

/**
 * Changes who is in a list (manual) segment.
 *
 * Needs a secret (sk_) key: public keys are refused. Unlike AnalyticsClient,
 * calls are synchronous and return the API's answer.
 *
 * Members are arrays with at least one of `email`, `phone` or `userId`, and
 * optional `traits` (stored on the profile, adds only):
 *
 *     $segments = new SegmentsClient(getenv('ORIGAMY_SECRET_KEY'));
 *     $result = $segments->addMembers(42, [
 *         ['email' => 'jane@example.com', 'traits' => ['first_name' => 'Jane']],
 *         ['phone' => '0803 123 4567'],
 *     ], phoneRegion: 'NG');
 */
class SegmentsClient
{
    /** The most rows the API takes in one request. Longer lists are split. */
    public const MAX_MEMBERS_PER_REQUEST = 1000;

    /** Tries of one request that is rate limited or finds the service unavailable. */
    private const MAX_ATTEMPTS   = 3;
    private const RETRY_STATUSES = [429, 502, 503, 504];

    private string $endpoint;

    /** @var callable(int): int  Milliseconds to wait before retry $attempt (0-based) when the API sends no Retry-After. */
    private $retryAfter;

    public function __construct(
        private string $secretKey,
        string $endpoint = '',
        private int $timeoutSeconds = 30,
        ?callable $retryAfter = null,
    ) {
        $this->endpoint   = rtrim($endpoint ?: Config::DEFAULT_ENDPOINT, '/');
        $this->retryAfter = $retryAfter ?? static fn (int $attempt): int => 1000 * (2 ** $attempt);
    }

    /**
     * Adds people to a list segment. Each person goes through the event
     * pipeline as an identify: someone new becomes a profile, and
     * segment-entered journeys start. Re-adding a member refreshes their traits.
     *
     * Lists longer than MAX_MEMBERS_PER_REQUEST are sent in several requests.
     * If one fails, the SegmentException's $result covers the ones before it.
     *
     * @param array<int, array<string, mixed>> $members
     * @param string|null $phoneRegion ISO 3166 alpha-2 country for phone numbers without a country code
     * @throws SegmentException
     */
    public function addMembers(int $segmentId, array $members, ?string $phoneRegion = null): SegmentMembersResult
    {
        return $this->write($segmentId, $members, 'add', $phoneRegion);
    }

    /**
     * Removes people from a list segment and starts segment-exited journeys.
     * A `userId` matches that person; an email or phone matches members whose
     * stored email or phone is the same.
     *
     * @param array<int, array<string, mixed>> $members
     * @throws SegmentException
     */
    public function removeMembers(int $segmentId, array $members, ?string $phoneRegion = null): SegmentMembersResult
    {
        return $this->write($segmentId, $members, 'remove', $phoneRegion);
    }

    private function write(int $segmentId, array $members, string $list, ?string $phoneRegion): SegmentMembersResult
    {
        $total = new SegmentMembersResult($segmentId);
        if ($segmentId <= 0) {
            throw new SegmentException(0, 'segment id must be a positive number', 'invalid_request', $total);
        }

        $rows = array_map(self::wireMember(...), array_values($members));
        foreach (array_chunk($rows, self::MAX_MEMBERS_PER_REQUEST) as $chunk => $batch) {
            $body = [$list => $batch];
            if ($phoneRegion !== null && $phoneRegion !== '') {
                $body['phoneRegion'] = $phoneRegion;
            }

            try {
                $res = $this->post($segmentId, json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } catch (SegmentException $e) {
                throw $e->withResult($total);
            } catch (\JsonException $e) {
                throw new SegmentException(0, 'encoding members: ' . $e->getMessage(), 'invalid_request', $total);
            }

            $total->added    += (int) ($res['added'] ?? 0);
            $total->removed  += (int) ($res['removed'] ?? 0);
            $total->notFound += (int) ($res['not_found'] ?? 0);
            foreach ($res['rejected'] ?? [] as $r) {
                $total->rejected[] = [
                    'list'  => (string) ($r['list'] ?? $list),
                    'index' => (int) ($r['index'] ?? 0) + $chunk * self::MAX_MEMBERS_PER_REQUEST,
                    'error' => (string) ($r['error'] ?? ''),
                ];
            }
        }
        return $total;
    }

    /**
     * Keeps only the fields the API reads, as a JSON object: PHP's [] must
     * never reach the API as an array, or the whole request is refused.
     */
    private static function wireMember(mixed $member): object
    {
        if (!is_array($member)) {
            return new \stdClass();
        }
        $out = [];
        $userId = $member['userId'] ?? $member['user_id'] ?? null;
        if ($userId !== null && $userId !== '') {
            $out['userId'] = (string) $userId;
        }
        foreach (['email', 'phone'] as $key) {
            if (isset($member[$key]) && $member[$key] !== '') {
                $out[$key] = (string) $member[$key];
            }
        }
        if (!empty($member['traits']) && is_array($member['traits'])) {
            $out['traits'] = (object) $member['traits'];
        }
        return (object) $out;
    }

    /** @return array<string, mixed> the decoded 200 response */
    private function post(int $segmentId, string $payload): array
    {
        for ($attempt = 0; ; $attempt++) {
            [$status, $body, $retryAfter, $curlError] = $this->request($segmentId, $payload);

            if ($status === 200) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    throw new SegmentException($status, 'response is not JSON');
                }
                return $decoded;
            }

            $err = $status === 0
                ? new SegmentException(0, 'sending request: ' . $curlError)
                : self::toException($status, $body);
            $retry = $status === 0 || in_array($status, self::RETRY_STATUSES, true);
            if (!$retry || $attempt + 1 >= self::MAX_ATTEMPTS) {
                throw $err;
            }

            $waitMs = $retryAfter > 0 ? $retryAfter * 1000 : ($this->retryAfter)($attempt);
            if ($waitMs > 0) {
                usleep($waitMs * 1000);
            }
        }
    }

    /** @return array{int, string, int, string} status (0 when no response), body, Retry-After seconds, curl error */
    private function request(int $segmentId, string $payload): array
    {
        $ch = curl_init($this->endpoint . '/v1/segments/' . $segmentId . '/members');
        if ($ch === false) {
            throw new SegmentException(0, 'curl_init failed');
        }

        $retryAfter = 0;
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
                'User-Agent: origamy-php (version: ' . Config::VERSION . ')',
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$retryAfter): int {
                if (stripos($line, 'Retry-After:') === 0) {
                    $retryAfter = max(0, (int) trim(substr($line, 12)));
                }
                return strlen($line);
            },
        ]);

        $body      = curl_exec($ch);
        $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($body === false) {
            return [0, '', 0, $curlError];
        }
        return [$status, (string) $body, $retryAfter, ''];
    }

    private static function toException(int $status, string $body): SegmentException
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded) && is_string($decoded['error'] ?? null) && $decoded['error'] !== '') {
            $code = is_string($decoded['code'] ?? null) ? $decoded['code'] : null;
            return new SegmentException($status, $decoded['error'], $code);
        }
        $message = trim($body);
        return new SegmentException($status, $message !== '' ? $message : 'request failed');
    }
}
