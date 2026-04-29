<?php

declare(strict_types=1);

namespace Origamy;

/**
 * Integrations map for analytics messages.
 * Mirrors Go's Integrations map[string]interface{} with the same helper methods.
 */
class Integrations implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function enableAll(): static
    {
        return $this->enable('all');
    }

    public function disableAll(): static
    {
        return $this->disable('all');
    }

    public function enable(string $name): static
    {
        return $this->set($name, true);
    }

    public function disable(string $name): static
    {
        return $this->set($name, false);
    }

    public function set(string $name, mixed $value): static
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
