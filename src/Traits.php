<?php

declare(strict_types=1);

namespace Origamy;

class Traits implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function setAddress(string $address): self
    {
        return $this->set('address', $address);
    }

    public function setAge(int $age): self
    {
        return $this->set('age', $age);
    }

    public function setAvatar(string $url): self
    {
        return $this->set('avatar', $url);
    }

    public function setBirthday(\DateTimeInterface $date): self
    {
        return $this->set('birthday', $date->format('Y-m-d\TH:i:s\Z'));
    }

    public function setCreatedAt(\DateTimeInterface $date): self
    {
        return $this->set('createdAt', $date->format('Y-m-d\TH:i:s\Z'));
    }

    public function setDescription(string $desc): self
    {
        return $this->set('description', $desc);
    }

    public function setEmail(string $email): self
    {
        return $this->set('email', $email);
    }

    public function setFirstName(string $firstName): self
    {
        return $this->set('firstName', $firstName);
    }

    public function setGender(string $gender): self
    {
        return $this->set('gender', $gender);
    }

    public function setLastName(string $lastName): self
    {
        return $this->set('lastName', $lastName);
    }

    public function setName(string $name): self
    {
        return $this->set('name', $name);
    }

    public function setPhone(string $phone): self
    {
        return $this->set('phone', $phone);
    }

    public function setTitle(string $title): self
    {
        return $this->set('title', $title);
    }

    public function setUsername(string $username): self
    {
        return $this->set('username', $username);
    }

    public function setWebsite(string $url): self
    {
        return $this->set('website', $url);
    }

    /**
     * @param mixed $value
     */
    public function set(string $field, $value): self
    {
        $this->data[$field] = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function get(string $field)
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
