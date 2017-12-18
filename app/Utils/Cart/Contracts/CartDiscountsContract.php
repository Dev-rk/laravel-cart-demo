<?php

namespace App\Utils\Cart\Contracts;

interface CartDiscountsContract
{
    function attachBuyOneGetOneDiscount();

    function detachBuyOneGetOneDiscount();

    function attachFixedDiscount($discountAmount = 0, $minAmount = 0);

    function detachFixedDiscount();

    function attachDiscountCard(string $cardToken = '');

    function detachDiscountCard();
}