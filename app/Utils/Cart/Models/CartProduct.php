<?php

namespace App\Utils\Cart\Models;

class CartProduct
{
    protected $id;
    protected $name;
    protected $price;
    protected $sourcePrice;
    protected $count;

    public function __construct(int $id = 0, string $name = '', float $price = 0, int $count = 1)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price * $count;
        $this->count = $count;
        $this->sourcePrice = $price;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        return isset($this->$name) ? $this->$name = $value : false;
    }
}