<?php

namespace App\Utils\Cart;

use App\Utils\Cart\Contracts\CartContract;
use App\Utils\Cart\Contracts\CartDiscountsContract;
use App\Utils\Cart\Models\CartProduct;
use App\Utils\Cart\Models\DiscountCard;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

class Cart implements CartContract, CartDiscountsContract
{
    protected $cartName = "cartLaravel";
    protected $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Get single product from cart
     *
     * @param int $id
     * @return mixed|null
     */
    public function getItem(int $id)
    {
        $cartContent = $this->getContent();

        return $cartContent->get('items')->has($id) ? $cartContent->get('items')->get($id) : null;
    }

    /**
     * Edit single product from cart
     *
     * @param int $id
     * @param array $options
     * @return null
     */
    public function updateItem(int $id, array $options = [])
    {
        $cartContent = $this->getContent();

        if ($cartContent->get('items')->has($id) )
        {
            $item = $cartContent->get('items')->get($id);

            foreach ($options as $optionName => $value)
            {
                if ($item->$optionName && $optionName != 'id')
                    $item->$optionName = $value;

                if ($optionName == 'count')
                    $item->price = $item->sourcePrice * $value;
            }
            $this->session->get($this->cartName)->get('items')->put($id, $item);

            $this->updateCartInfo();

            return $item;
        }

        return null;
    }

    /**
     * Get whole cart content
     *
     * @return Collection
     */
    public function getContent()
    {
        return $this->session->has($this->cartName)
            ? $this->session->get($this->cartName)
            : new Collection([
                'totalPrice'  => 0.0,
                'sourcePrice' => 0.0,
                'discounts'   => new Collection(),
                'items'       => new Collection()
            ]);
    }

    /**
     * Get full cart price
     *
     * @return mixed
     */
    public function getCartPrice()
    {
        return $this->session->has($this->cartName) ? $this->session->get($this->cartName)->get('totalPrice') : 0;
    }

    /**
     * Get source cart price
     *
     * @return mixed
     */
    public function getSourceCartPrice()
    {
        return $this->session->has($this->cartName) ? $this->session->get($this->cartName)->get('sourcePrice') : 0;
    }

    /**
     * Add product to cart
     *
     * @param int $id
     * @param string $name
     * @param float $price
     * @param int $count
     * @return CartProduct|mixed
     */
    public function add(int $id = 0, string $name = '', float $price = 0, int $count = 0)
    {
        $cartContent = $this->getContent();
        $cartItems = $cartContent->get('items');

        if ($cartItems && $cartItems->has($id)) {
            $product = $cartItems->get($id);
            $product->count += $count;
            $product->price += ($count * $product->sourcePrice);
        } else {
            $product = $this->createCartProduct($id, $name, $price, $count);
            $cartItems->put($id, $product);
        }

        $cartItems = $cartItems->sortByDesc(function ($product, $key) {
            return $product->sourcePrice;
        });

        $this->session->put($this->cartName, $cartContent->put('items', $cartItems));

        $this->updateCartInfo();

        return $product;
    }

    /**
     * Add multiple products to cart
     *
     * @param array $cartProducts
     */
    public function addMultiple(array $cartProducts)
    {
        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct)
                if (isset($cartProduct['id']) && isset($cartProduct['name']))
                    $this->add(
                        $cartProduct['id'],
                        $cartProduct['name'],
                        isset($cartProduct['price']) ? $cartProduct['price'] : 0,
                        isset($cartProduct['count']) ? $cartProduct['count'] : 1
                    );
        }
    }

    /**
     * Remove single item from cart
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id)
    {
        $items = $this->getContent()->get('items');

        if ($items->has($id)) {
            $items->forget($id);

            $this->session->get($this->cartName)->put('items', $items);

            $this->updateCartInfo();

            return true;
        }

        return false;
    }

    /**
     * Remove some amount of items from cart
     *
     * @param array $id
     * @return bool
     */
    public function removeMultiple(array $id)
    {
        if (!empty($id)) {
            foreach ($id as $items)
                $this->remove($items);

            return true;
        }

        return false;
    }

    /**
     * Clear cart
     */
    public function flush()
    {
        $this->session->forget($this->cartName);
    }

    /**
     * Attach BuyOneGetOneFree discount to the cart
     */
    public function attachBuyOneGetOneDiscount()
    {
        if ($this->getContent()->get('items')->isNotEmpty()) {
            $this->session->get($this->cartName)->get('discounts')->put('BuyOneGetOneDiscount', true);

            $this->updateCartInfo();
        }
    }

    /**
     * Attach discount with fixed amount to the cart
     */
    public function attachFixedDiscount($discountAmount = 0, $minAmount = 0)
    {
        $this->session->get($this->cartName)->get('discounts')->put('FixedDiscount', compact('discountAmount', 'minAmount'));
        $this->updateCartInfo();
    }

    /**
     * Attach user loyalty cart to the cart
     */
    public function attachDiscountCard(string $cardToken = '')
    {
        $this->session->get($this->cartName)->get('discounts')->put('DiscountCard', $cardToken);

        $this->updateCartInfo();
    }

    /**
     * Detach BuyOneGetOneFree discount to the cart
     */
    public function detachBuyOneGetOneDiscount()
    {
        $discounts = $this->session->get($this->cartName)->get('discounts');

        if ($discounts && $discounts->has('BuyOneGetOneDiscount'))
            $this->session->get($this->cartName)->put('discounts', $discounts->forget('BuyOneGetOneDiscount'));

        $this->updateCartInfo();
    }

    /**
     * Detach discount with fixed amount to the cart
     */
    public function detachFixedDiscount()
    {
        $discounts = $this->session->get($this->cartName)->get('discounts');

        if ($discounts && $discounts->has('FixedDiscount'))
            $this->session->get($this->cartName)->put('discounts', $discounts->forget('FixedDiscount'));

        $this->updateCartInfo();
    }

    /**
     * Detach user loyalty cart to the cart
     */
    public function detachDiscountCard()
    {
        $discounts = $this->session->get($this->cartName)->get('discounts');

        if ($discounts && $discounts->has('DiscountCard'))
            $this->session->get($this->cartName)->put('discounts', $discounts->forget('DiscountCard'));

        $this->updateCartInfo();
    }

    /**
     * Apply discount when for each item got one free
     */
    protected function applyBuyOneGetOneDiscount()
    {
        $cartContent = $this->getContent();
        $items = $cartContent->get('items');

        if ($items->isNotEmpty()) {
            $totals = $cartContent->get('items')->map(function ($item) {
                return $item->count;
            })->sum();

            $limit = intval(ceil($totals / 2));

            $second = 0;

            foreach ($items as &$item) {
                if ($second + $item->count > $limit) {
                    if ($second < $limit)
                        $item->price = ($item->count - ($second + $item->count - $limit)) * $item->sourcePrice;
                    else
                        $item->price = 0;
                }

                $second += $item->count;
            }

            $this->session->get($this->cartName)->put('items', $items);
            $this->session->get($this->cartName)->put('totalPrice', $this->calculateCartPrice());
        }
    }

    /**
     * Cancel BuyOneGetOne discount
     */
    protected function cancelBuyOneGetOneDiscount()
    {
        $cartContent = $this->getContent();
        $items = $cartContent->get('items');

        if ($items->isNotEmpty()) {
            foreach ($items as &$item)
                $item->price = $item->count * $item->sourcePrice;

            $this->session->get($this->cartName)->put('items', $items);
            $this->session->get($this->cartName)->put('totalPrice', $this->calculateCartPrice());
        }
    }

    /**
     * Apply fixed discount for all cart content
     *
     * @param int $discountAmount
     * @param int $minAmount
     */
    protected function applyFixedDiscount($discountAmount = 0, $minAmount = 0)
    {
        $cartPrice = $this->getContent()->get('totalPrice');

        if ($cartPrice && $cartPrice >= $minAmount)
            $this->session->get($this->cartName)->put('totalPrice', round($cartPrice * (1 - ($discountAmount / 100)), 2));
    }

    /**
     * Apply loyalty card for all cart content
     *
     * @param string $cardToken
     */
    protected function applyDiscountCard(string $cardToken = '')
    {
        $card = DiscountCard::where('card', $cardToken)->first();

        if (!empty($card) && isset($card->amount)) {
            $cartPrice = $this->getContent()->get('totalPrice') * (1 - ($card->amount / 100));

            $this->session->get($this->cartName)->put('totalPrice', round($cartPrice, 2));
        }
    }

    /**
     * Create instance of App\Utils\Cart\Models\CartProduct
     *
     * @param int $id
     * @param string $name
     * @param float $price
     * @param int $count
     * @return CartProduct
     */
    protected function createCartProduct(int $id = 0, string $name = '', float $price = 0, int $count = 1)
    {
        return new CartProduct($id, $name, $price, $count);
    }

    /**
     * Method updates prices in cart and applies attached discounts
     */
    protected function updateCartInfo()
    {
        $this->session->get($this->cartName)->put('totalPrice', $this->calculateCartPrice());
        $this->session->get($this->cartName)->put('sourcePrice', $this->calculateSourceCartPrice());

        $discounts = $this->session->get($this->cartName)->get('discounts');

        if ($discounts && $discounts->isNotEmpty()) {
            if ($discounts->has('BuyOneGetOneDiscount'))
                $this->applyBuyOneGetOneDiscount();
            else
                $this->cancelBuyOneGetOneDiscount();

            if ($discounts->has('FixedDiscount')) {
                $conf = $discounts->get('FixedDiscount');
                $this->applyFixedDiscount($conf['discountAmount'], $conf['minAmount']);
            }

            if ($discounts->has('DiscountCard'))
                $this->applyDiscountCard($discounts->get('DiscountCard'));
        }
    }

    /**
     * Calculate full cart price, without global cart discounts
     *
     * @return float
     */
    protected function calculateCartPrice()
    {
        return round($this->getContent()->get('items')->map(function ($item) {
            return $item->price;
        })->sum(), 2);
    }

    /**
     * Calculate source cart price
     *
     * @return float
     */
    protected function calculateSourceCartPrice()
    {
        return round($this->getContent()->get('items')->map(function ($item) {
            return $item->sourcePrice * $item->count;
        })->sum(), 2);
    }
}