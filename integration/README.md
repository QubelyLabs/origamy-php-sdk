# Origamy PHP SDK — Integration Demo

A self-contained demo application that exercises every message type supported by
the PHP SDK. It is designed for integration testing against the Origamy platform.

## Requirements

- PHP 8.0+
- Composer

## How it works

By default the demo uses `NoopDispatcher`, which prints all batched events to
stdout instead of sending real HTTP requests. Set `ORIGAMY_WRITE_KEY` to a real
key and remove (or replace) the `dispatcher` option in `demo.php` to send live
traffic.

## Setup

```sh
# From this directory
composer install
```

## Run

```sh
# Using the NoopDispatcher (default)
php demo.php

# With a real write key
ORIGAMY_WRITE_KEY=your_key php demo.php
```

The demo covers all six message types:

| Type     | Purpose                                      |
|----------|----------------------------------------------|
| Identify | Attach traits (name, email, plan) to a user  |
| Track    | Record a custom named event                  |
| Page     | Record a web page view                       |
| Screen   | Record a mobile screen view                  |
| Group    | Associate a user with a company or workspace |
| Alias    | Merge an anonymous ID into a known user ID   |

## Configuration

Key options set in `demo.php`:

| Option            | Value in demo      | Description                            |
|-------------------|--------------------|----------------------------------------|
| `dispatcher`      | `NoopDispatcher`   | Prints to stdout, no HTTP              |
| `batchSize`       | 10                 | Flush after 10 messages                |
| `callback`        | anonymous class    | Logs success/failure per message       |
| `defaultContext`  | app name + version | Injected into every message            |
| `verbose`         | true               | Detailed internal logging              |

Replace `$noop` with an `HttpDispatcher` instance (or omit `dispatcher:` in
`Config`) to target a live Origamy endpoint.

## Flush behaviour (PHP-specific)

PHP is single-threaded, so the SDK flushes synchronously:

1. **On batch size** — when the pending count reaches `batchSize`
2. **On `close()`** — call before the script exits
3. **Shutdown function** — automatically registered; catches remaining messages
   if `close()` is not called explicitly
