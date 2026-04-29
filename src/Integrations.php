<?php

declare(strict_types=1);

namespace Origamy;

class Integrations implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function enableAll(): self
    {
        return $this->enable('all');
    }

    public function disableAll(): self
    {
        return $this->disable('all');
    }

    public function enable(string $name): self
    {
        return $this->set($name, true);
    }

    public function disable(string $name): self
    {
        return $this->set($name, false);
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): self
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
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

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
}
