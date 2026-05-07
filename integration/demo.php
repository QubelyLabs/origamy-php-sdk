<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Origamy\AnalyticsClient;
use Origamy\CallbackInterface;
use Origamy\Config;
use Origamy\Context;
use Origamy\AppInfo;
use Origamy\Dispatcher\DispatcherConfig;
use Origamy\Dispatcher\NoopDispatcher;
use Origamy\MessageInterface;
use Origamy\Messages\Alias;
use Origamy\Messages\Group;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Track;
use Origamy\Properties;
use Origamy\Traits as OrigamyTraits;

// --- Callback ---------------------------------------------------------------

// Logs success and failure events dispatched by the SDK.
$callback = new class implements CallbackInterface {
    public function success(MessageInterface $message): void
    {
        printf("[callback] sent:   %s\n", get_class($message));
    }

    public function failure(MessageInterface $message, \Throwable $error): void
    {
        printf("[callback] failed: %s — %s\n", get_class($message), $error->getMessage());
    }
};

// --- Client setup -----------------------------------------------------------

$writeKey = getenv('ORIGAMY_WRITE_KEY') ?: 'demo-write-key';

if (getenv('ORIGAMY_WRITE_KEY') === false) {
    echo "ORIGAMY_WRITE_KEY not set — using NoopDispatcher (no HTTP requests)\n";
}

// Use NoopDispatcher so the demo prints events to stdout without hitting the API.
// Remove the dispatcher option (or swap in HttpDispatcher) to send real traffic.
$noop = new NoopDispatcher(new DispatcherConfig(
    Config::DEFAULT_ENDPOINT,
    $writeKey,
    Config::VERSION,
    true,
));

$defaultContext = new Context();
$defaultContext->app = new AppInfo('origamy-php-integration', '1.0.0');

[$client, $err] = AnalyticsClient::newWithConfig($writeKey, new Config(
    dispatcher: $noop,
    batchSize: 10,
    verbose: true,
    callback: $callback,
    defaultContext: $defaultContext,
));

if ($err !== null) {
    fprintf(STDERR, "failed to create client: %s\n", $err->getMessage());
    exit(1);
}

// --- Identify — attach traits to a known user. ------------------------------

$client->enqueue(new Identify(
    userId: 'user-123',
    traits: (new OrigamyTraits())
        ->setEmail('test@origamy.com')
        ->setName('Test User')
        ->set('role', 'integration-tester'),
));

// --- Track — record a custom event. -----------------------------------------

$client->enqueue(new Track(
    event: 'Integration Test Started',
    userId: 'user-123',
    properties: (new Properties())
        ->set('sdk', 'php')
        ->set('version', Config::VERSION),
));

// --- Page — record a web page view. -----------------------------------------

$client->enqueue(new Page(
    userId: 'user-123',
    name: 'Integration Dashboard',
    properties: (new Properties())
        ->set('url', 'https://origamy.com/dashboard')
        ->set('path', '/dashboard'),
));

// --- Screen — record a mobile screen view. ----------------------------------

$client->enqueue(new Screen(
    userId: 'user-123',
    name: 'Settings Screen',
));

// --- Group — associate a user with a company or workspace. ------------------

$client->enqueue(new Group(
    groupId: 'team-origamy',
    userId: 'user-123',
    traits: (new OrigamyTraits())
        ->set('name', 'Origamy Team')
        ->set('plan', 'enterprise'),
));

// --- Anonymous track — no userId required. ----------------------------------

$client->enqueue(new Track(
    event: 'Landing Page Visited',
    anonymousId: 'anon-session-abc',
    properties: (new Properties())
        ->set('referrer', 'https://google.com'),
));

// --- Alias — merge an anonymous identity into a known user. -----------------

$client->enqueue(new Alias(
    previousId: 'anon-session-abc',
    userId: 'user-123',
));

// Flush remaining messages and release resources.
// The shutdown function also does this automatically if close() is not called.
$client->close();

echo "\nAll messages enqueued — flushed on close.\n";
