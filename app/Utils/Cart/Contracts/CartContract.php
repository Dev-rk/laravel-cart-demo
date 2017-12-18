<?php

namespace App\Utils\Cart\Contracts;

interface CartContract
{
    function add(int $id = 0, string $name = '', float $price = 0, int $count = 0);

    function addMultiple(array $cartProducts);

    function remove(int $id);

    function removeMultiple(array $id);

    function flush();

    function getItem(int $id);

    function updateItem(int $id, array $options = []);

    function getContent();

    function getCartPrice();

    function getSourceCartPrice();

    public function attachBuyOneGetOneDiscount();

    public function detachBuyOneGetOneDiscount();

    public function attachFixedDiscount($discountAmount = 0, $minAmount = 0);

    public function detachFixedDiscount();

    public function attachDiscountCard(string $cardToken = '');

    public function detachDiscountCard();
}