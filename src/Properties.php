<?php

declare(strict_types=1);

namespace Origamy;

class Properties implements \ArrayAccess, \Countable, \JsonSerializable
{
    private array $data = [];

    public function setRevenue(float $revenue): self
    {
        return $this->set('revenue', $revenue);
    }

    public function setCurrency(string $currency): self
    {
        return $this->set('currency', $currency);
    }

    public function setValue(float $value): self
    {
        return $this->set('value', $value);
    }

    public function setPath(string $path): self
    {
        return $this->set('path', $path);
    }

    public function setReferrer(string $referrer): self
    {
        return $this->set('referrer', $referrer);
    }

    public function setTitle(string $title): self
    {
        return $this->set('title', $title);
    }

    public function setUrl(string $url): self
    {
        return $this->set('url', $url);
    }

    public function setName(string $name): self
    {
        return $this->set('name', $name);
    }

    public function setCategory(string $category): self
    {
        return $this->set('category', $category);
    }

    public function setSku(string $sku): self
    {
        return $this->set('sku', $sku);
    }

    public function setPrice(float $price): self
    {
        return $this->set('price', $price);
    }

    public function setProductId(string $id): self
    {
        return $this->set('id', $id);
    }

    public function setOrderId(string $id): self
    {
        return $this->set('orderId', $id);
    }

    public function setTotal(float $total): self
    {
        return $this->set('total', $total);
    }

    public function setSubtotal(float $subtotal): self
    {
        return $this->set('subtotal', $subtotal);
    }

    public function setShipping(float $shipping): self
    {
        return $this->set('shipping', $shipping);
    }

    public function setTax(float $tax): self
    {
        return $this->set('tax', $tax);
    }

    public function setDiscount(float $discount): self
    {
        return $this->set('discount', $discount);
    }

    public function setCoupon(string $coupon): self
    {
        return $this->set('coupon', $coupon);
    }

    public function setProducts(Product ...$products): self
    {
        return $this->set('products', $products);
    }

    public function setRepeat(bool $repeat): self
    {
        return $this->set('repeat', $repeat);
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

class Product implements \JsonSerializable
{
    /** @var string */
    public $id;
    /** @var string */
    public $sku;
    /** @var string */
    public $name;
    /** @var float */
    public $price;

    public function __construct(
        string $id    = '',
        string $sku   = '',
        string $name  = '',
        float  $price = 0.0
    ) {
        $this->id    = $id;
        $this->sku   = $sku;
        $this->name  = $name;
        $this->price = $price;
    }

    public function jsonSerialize(): array
    {
        $data = ['price' => $this->price];
        if ($this->id   !== '') $data['id']   = $this->id;
        if ($this->sku  !== '') $data['sku']  = $this->sku;
        if ($this->name !== '') $data['name'] = $this->name;
        return $data;
    }
}
