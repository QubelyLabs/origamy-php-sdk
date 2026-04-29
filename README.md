## Installation

```bash
composer require origamy/php-sdk
```

Requires PHP 8.1+.

## Usage

### Basic Usage

```php
<?php

use Origamy\AnalyticsClient;
use Origamy\Config;
use Origamy\Messages\Track;

[$client, $err] = AnalyticsClient::newWithConfig(
    getenv('ORIGAMY_WRITE_KEY'),
    new Config(),
);
if ($err !== null) {
    throw $err;
}

$client->enqueue(new Track(
    event:  'Signed Up',
    userId: 'user-123',
));

$client->close();
```

The client automatically flushes any pending messages when the PHP process exits (via a registered shutdown function), so an explicit `close()` call is only needed when you want to guarantee delivery before the script continues.

### Message Types

All six Segment-compatible message types are supported:

```php
use Origamy\Messages\Track;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Group;
use Origamy\Messages\Alias;
use Origamy\Properties;
use Origamy\Traits as OrigamyTraits;

// Track an action
$client->enqueue(new Track(
    event:  'Order Completed',
    userId: 'user-123',
    properties: (new Properties())
        ->set('orderId', 'ORD-9999')
        ->setRevenue(99.99)
        ->setCurrency('USD'),
));

// Identify a user with traits
$client->enqueue(new Identify(
    userId: 'user-123',
    traits: (new OrigamyTraits())
        ->setEmail('alice@example.com')
        ->setName('Alice Smith')
        ->set('plan', 'pro'),
));

// Track a page view
$client->enqueue(new Page(
    userId: 'user-123',
    name:   'Pricing',
    properties: (new Properties())
        ->set('url', 'https://example.com/pricing')
        ->set('title', 'Pricing Plans'),
));

// Track a mobile screen view
$client->enqueue(new Screen(
    userId: 'user-123',
    name:   'Dashboard',
    properties: (new Properties())->set('tab', 'overview'),
));

// Associate a user with a group/company
$client->enqueue(new Group(
    groupId: 'company-acme',
    userId:  'user-123',
    traits: (new OrigamyTraits())
        ->setName('Acme Corp')
        ->setWebsite('https://acme.com'),
));

// Alias an anonymous ID to an identified user
$client->enqueue(new Alias(
    previousId: 'anon-session-abc',
    userId:     'user-123',
));
```

### Anonymous Users

Pass `anonymousId` instead of (or in addition to) `userId` for anonymous tracking:

```php
$client->enqueue(new Track(
    event:       'Page Scrolled',
    anonymousId: 'anon-browser-xyz',
    properties:  (new Properties())->set('depth', 75),
));
```

### Configuration

Configuration is passed as a `Config` object to `newWithConfig`:

```php
use Origamy\AnalyticsClient;
use Origamy\Config;

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    endpoint:  'https://api.origamy.com',
    batchSize: 100,
    verbose:   true,
));
```

### Development Mode (Noop Dispatcher)

For local development, use `NoopDispatcher` to log events to the console instead of sending them:

```php
use Origamy\AnalyticsClient;
use Origamy\Config;
use Origamy\Dispatcher\DispatcherConfig;
use Origamy\Dispatcher\NoopDispatcher;
use Origamy\Messages\Track;
use Origamy\Properties;

$dispatcher = new NoopDispatcher(new DispatcherConfig(verbose: true));

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    dispatcher: $dispatcher,
));

$client->enqueue(new Track(
    event:  'button_clicked',
    userId: 'user-123',
    properties: (new Properties())->set('button', 'signup'),
));

$client->close();
```

### Custom Queue

```php
use Origamy\Config;
use Origamy\Queue\InMemoryQueue;

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    queue: new InMemoryQueue(capacity: 1000),
));
```

### Custom Dispatcher

Implement the `DispatcherInterface` for custom transport:

```php
use Origamy\Dispatcher\DispatcherInterface;

interface DispatcherInterface
{
    public function send(string $payload): void;
    public function close(): void;
}
```

```php
use Origamy\Config;
use Origamy\Dispatcher\DispatcherInterface;

class MyGrpcDispatcher implements DispatcherInterface
{
    public function send(string $payload): void
    {
        // Send via gRPC
    }

    public function close(): void {}
}

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    dispatcher: new MyGrpcDispatcher(),
));
```

### Success / Failure Callbacks

```php
use Origamy\CallbackInterface;
use Origamy\Config;
use Origamy\MessageInterface;

class MyCallback implements CallbackInterface
{
    public function success(MessageInterface $message): void
    {
        // Message delivered successfully
    }

    public function failure(MessageInterface $message, \Throwable $error): void
    {
        error_log('Delivery failed: ' . $error->getMessage());
    }
}

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    callback: new MyCallback(),
));
```

### Full Configuration

```php
use Origamy\AnalyticsClient;
use Origamy\Config;
use Origamy\StdLogger;

[$client, $err] = AnalyticsClient::newWithConfig('your-write-key', new Config(
    endpoint:      'https://api.origamy.com',
    batchSize:     250,
    verbose:       true,
    logger:        new StdLogger(),
    queueCapacity: 500,
    retryAfter:    fn (int $attempt) => min(100 * 2 ** $attempt, 10_000),
));
if ($err !== null) {
    throw $err;
}
```

## Publishing

### Prerequisites

- Write access to the `origamy/php-sdk` package on [Packagist](https://packagist.org)
- Packagist API token or webhook configured on the repository

### Steps

**1. Update the version constant** in [src/Config.php](src/Config.php):

```php
public const VERSION = '3.0.1';
```

**2. Run tests** to confirm everything passes:

```bash
composer test
```

**3. Commit and tag** the release following [semver](https://semver.org) with a `v` prefix:

```bash
git add .
git commit -m "release: v3.0.1"
git tag v3.0.1
git push origin main --tags
```

Packagist picks up new tags automatically if a webhook is configured. Otherwise trigger a manual update:

**4. (Optional) Trigger a Packagist update** via the API:

```bash
curl -XPOST -H 'content-type:application/json' \
  "https://packagist.org/api/update-package?username=YOUR_USER&apiToken=YOUR_TOKEN" \
  -d '{"repository":{"url":"https://github.com/qubely/origamy-php-sdk"}}'
```

Once the tag is published, the new version is available via:

```bash
composer require origamy/php-sdk:^3.0.1
```

### Useful Commands

```bash
composer install          # Install dependencies
composer test             # Run the full test suite
./vendor/bin/phpunit      # Run tests directly
./vendor/bin/phpunit --group http   # Run HTTP integration tests only
```

## HTTP Wire Format

Events are batched and sent as a single HTTP POST to `/v1/batch`. The request body follows the same format as the Origamy Web SDK:

```json
{
  "batch": [
    {
      "type": "track",
      "messageId": "uuid",
      "userId": "user-123",
      "event": "Order Completed",
      "timestamp": "2024-01-15T10:30:00Z",
      "properties": { "revenue": 99.99 },
      "context": {
        "library": { "name": "origamy-php", "version": "3.0.0" }
      }
    }
  ],
  "sentAt": "2024-01-15T10:30:00.123Z"
}
```

Context is attached per-event (not at the batch level). `sentAt` uses ISO 8601 with milliseconds, matching `new Date().toISOString()` from the Web SDK.

Authentication uses HTTP Basic Auth with the write key as the username and an empty password.

## Flush Behaviour

PHP is single-threaded, so there is no background goroutine or interval timer. Messages are flushed:

- **Automatically** when the batch size is reached (default: 250 messages).
- **On `close()`** — call this before the script exits to guarantee delivery.
- **On shutdown** — a `register_shutdown_function` flushes any remaining messages when the PHP process ends normally.

## Available Configuration

| Option          | Type                       | Description                                       |
| --------------- | -------------------------- | ------------------------------------------------- |
| `endpoint`      | `string`                   | API endpoint URL                                  |
| `batchSize`     | `int`                      | Max messages per batch                            |
| `dispatcher`    | `DispatcherInterface`      | Custom dispatcher (HTTP, Noop, gRPC, etc.)        |
| `queue`         | `QueueInterface`           | Custom message queue                              |
| `queueCapacity` | `int`                      | Capacity for the default in-memory queue          |
| `verbose`       | `bool`                     | Enable verbose logging                            |
| `logger`        | `LoggerInterface`          | Custom logger                                     |
| `callback`      | `CallbackInterface`        | Success/failure delivery callbacks                |
| `defaultContext`| `Context`                  | Default context merged into every message         |
| `retryAfter`    | `callable(int): int`       | Retry delay in ms; receives the attempt index     |

## Defaults

| Setting         | Default                   |
| --------------- | ------------------------- |
| Endpoint        | `https://api.origamy.com` |
| Batch size      | 250 messages              |
| Queue capacity  | 100 messages              |
| Request timeout | 10 seconds                |
| Retry attempts  | 10 (exponential backoff)  |

## License

The library is released under the [MIT license](License.md).
