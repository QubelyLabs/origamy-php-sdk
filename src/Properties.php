<?php

declare(strict_types=1);

namespace Origamy;

/**
 * Properties map for analytics messages.
 * Mirrors Go's Properties map[string]interface{} with the same helper methods.
 */
class Properties implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function setRevenue(float $revenue): static
    {
        return $this->set('revenue', $revenue);
    }

    public function setCurrency(string $currency): static
    {
        return $this->set('currency', $currency);
    }

    public function setValue(float $value): static
    {
        return $this->set('value', $value);
    }

    public function setPath(string $path): static
    {
        return $this->set('path', $path);
    }

    public function setReferrer(string $referrer): static
    {
        return $this->set('referrer', $referrer);
    }

    public function setTitle(string $title): static
    {
        return $this->set('title', $title);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setCategory(string $category): static
    {
        return $this->set('category', $category);
    }

    public function setSku(string $sku): static
    {
        return $this->set('sku', $sku);
    }

    public function setPrice(float $price): static
    {
        return $this->set('price', $price);
    }

    public function setProductId(string $id): static
    {
        return $this->set('id', $id);
    }

    public function setOrderId(string $id): static
    {
        return $this->set('orderId', $id);
    }

    public function setTotal(float $total): static
    {
        return $this->set('total', $total);
    }

    public function setSubtotal(float $subtotal): static
    {
        return $this->set('subtotal', $subtotal);
    }

    public function setShipping(float $shipping): static
    {
        return $this->set('shipping', $shipping);
    }

    public function setTax(float $tax): static
    {
        return $this->set('tax', $tax);
    }

    public function setDiscount(float $discount): static
    {
        return $this->set('discount', $discount);
    }

    public function setCoupon(string $coupon): static
    {
        return $this->set('coupon', $coupon);
    }

    public function setProducts(Product ...$products): static
    {
        return $this->set('products', $products);
    }

    public function setRepeat(bool $repeat): static
    {
        return $this->set('repeat', $repeat);
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

/** Represents a product in the E-commerce API. */
class Product implements \JsonSerializable
{
    public function __construct(
        public string $id    = '',
        public string $sku   = '',
        public string $name  = '',
        public float  $price = 0.0,
    ) {}

    public function jsonSerialize(): array
    {
        $data = ['price' => $this->price];
        if ($this->id   !== '') $data['id']   = $this->id;
        if ($this->sku  !== '') $data['sku']  = $this->sku;
        if ($this->name !== '') $data['name'] = $this->name;
        return $data;
    }
}
