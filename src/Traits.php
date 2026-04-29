<?php

declare(strict_types=1);

namespace Origamy;

/**
 * Traits map for analytics messages.
 * Mirrors Go's Traits map[string]interface{} with the same helper methods.
 */
class Traits implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function setAddress(string $address): static
    {
        return $this->set('address', $address);
    }

    public function setAge(int $age): static
    {
        return $this->set('age', $age);
    }

    public function setAvatar(string $url): static
    {
        return $this->set('avatar', $url);
    }

    public function setBirthday(\DateTimeInterface $date): static
    {
        return $this->set('birthday', $date->format('Y-m-d\TH:i:s\Z'));
    }

    public function setCreatedAt(\DateTimeInterface $date): static
    {
        return $this->set('createdAt', $date->format('Y-m-d\TH:i:s\Z'));
    }

    public function setDescription(string $desc): static
    {
        return $this->set('description', $desc);
    }

    public function setEmail(string $email): static
    {
        return $this->set('email', $email);
    }

    public function setFirstName(string $firstName): static
    {
        return $this->set('firstName', $firstName);
    }

    public function setGender(string $gender): static
    {
        return $this->set('gender', $gender);
    }

    public function setLastName(string $lastName): static
    {
        return $this->set('lastName', $lastName);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setPhone(string $phone): static
    {
        return $this->set('phone', $phone);
    }

    public function setTitle(string $title): static
    {
        return $this->set('title', $title);
    }

    public function setUsername(string $username): static
    {
        return $this->set('username', $username);
    }

    public function setWebsite(string $url): static
    {
        return $this->set('website', $url);
    }

    public function set(string $field, mixed $value): static
    {
        $this->data[$field] = $value;
        return $this;
    }

    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
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
