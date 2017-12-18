<?php

namespace Tests\Feature;

use App\Utils\Cart\Cart;
use App\Utils\Cart\Models\CartProduct;
use App\Utils\Cart\Models\DiscountCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CartTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('session.driver', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function testGetCartPrice()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $this->assertEquals(1150, $cart->getCartPrice());
    }

    public function testGetSourceCartPrice()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $this->assertEquals(1150, $cart->getSourceCartPrice());
    }

    public function testApplyDiscountCard()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $card = new DiscountCard();
        $card->card = '0000000000000000';
        $card->amount = 10;
        $card->user_id = 1;
        $card->save();

        $cart->attachDiscountCard('0000000000000000');

        $testValue = $this->getCartSkeleton(1035.0, 1150.0, new Collection(['DiscountCard' => '0000000000000000']));

        $testCase = new CartProduct(1, 'testCase', 200, 3);
        $testCase->sourcePrice = 200;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(2, 'testCase2', 150, 1);
        $testCase->sourcePrice = 150;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(3, 'testCase3', 100, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $card->delete();

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testApplyFixedDiscount()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $cart->attachFixedDiscount(10, 15.0);

        $testValue = $this->getCartSkeleton(1035.0, 1150.0, new Collection(['FixedDiscount' => ['discountAmount' => 10, 'minAmount' => 15]]));

        $testCase = new CartProduct(1, 'testCase', 200, 3);
        $testCase->sourcePrice = 200;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(2, 'testCase2', 150, 1);
        $testCase->sourcePrice = 150;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(3, 'testCase3', 100, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testApplyBuyOneGetOneDiscount()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $cart->attachBuyOneGetOneDiscount();

        $testValue = $this->getCartSkeleton(750.0, 1150.0, new Collection(['BuyOneGetOneDiscount' => true]));

        $testCase = new CartProduct(1, 'testCase', 200, 3);
        $testCase->sourcePrice = 200;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(2, 'testCase2', 150, 1);
        $testCase->price = 150;
        $testCase->sourcePrice = 150;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(3, 'testCase3', 0, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testGetCartContent()
    {
        $this->flushSession();

        $cart = $this->getCart();
        $cart->add(1, 'testCase', 200, 1);

        $testValue = new Collection([
            'totalPrice' => 200,
            'sourcePrice' => 200,
            'discounts' => new Collection(),
            'items' => new Collection([1 => new CartProduct(1, 'testCase', 200, 1)])
        ]);

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testGetItem()
    {
        $this->flushSession();

        $cart = $this->getCart();
        $cart->add(1, 'testCase', 200, 1);
        $testValue = new CartProduct(1, 'testCase', 200, 1);

        $item = $cart->getItem(1);
        $this->assertEquals($testValue, $item);
    }

    public function testUpdateItem()
    {
        $this->flushSession();

        $cart = $this->getCart();
        $cart->add(1, 'testCase', 200, 1);
        $cart->updateItem(1, ['name' => 'testCaseChanged', 'count' => 2]);

        $testValue = new CartProduct(1, 'testCaseChanged', 200, 2);

        $item = $cart->getItem(1);

        $this->assertEquals($testValue, $item);
    }

    public function testFlush()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $cart->flush();

        $this->assertEquals(new Collection([
            'totalPrice' => 0,
            'sourcePrice'=> 0,
            'discounts' => new Collection(),
            'items' => new Collection()
        ]), $cart->getContent());
    }
/*

*/

    public function testAddSingleProduct()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->add(1, 'testCase', 200, 1);

        $testValue = new Collection([
            'totalPrice' => 200,
            'sourcePrice'=> 200,
            'discounts' => new Collection(),
            'items' => new Collection([1 => new CartProduct(1, 'testCase', 200, 1)])
        ]);

        $this->assertEquals($testValue, Session::get('cartLaravel'));
    }

    public function testAddMultipleProduct()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $testValue = $this->getCartSkeleton(1150, 1150);

        $testCase = new CartProduct(1, 'testCase', 200, 3);
        $testCase->sourcePrice = 200;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(2, 'testCase2', 150, 1);
        $testCase->sourcePrice = 150;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(3, 'testCase3', 100, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $this->assertEquals($testValue, $cart->getContent());

    }

    public function testRemoveProduct()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $cart->remove(1);

        $testValue = $this->getCartSkeleton(550.0, 550.0);

        $testCase = new CartProduct(2, 'testCase2', 150, 1);
        $testCase->sourcePrice = 150;
        $testValue->get('items')->put($testCase->id, $testCase);
        $testCase = new CartProduct(3, 'testCase3', 100, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testRemoveMultipleProduct()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->addMultiple($this->getProductArray());

        $cart->removeMultiple([1, 2]);

        $testValue = $this->getCartSkeleton(400.0, 400.0);

        $testCase = new CartProduct(3, 'testCase3', 100, 4);
        $testCase->sourcePrice = 100;
        $testValue->get('items')->put($testCase->id, $testCase);

        $this->assertEquals($testValue, $cart->getContent());
    }

    public function testGetContent()
    {
        $this->flushSession();

        $cart = $this->getCart();

        $cart->add(1, 'testCase', 200, 1);
        $cart->add(1, 'testCase', 200, 1);

        $testCase = new CartProduct(1, 'testCase', 200, 2);
        $testCase->sourcePrice = 200;

        $this->assertEquals($cart->getContent(), Session::get('cartLaravel'));
        $this->assertEquals($cart->getContent(), new Collection([
            'totalPrice' => 400,
            'sourcePrice' => 400,
            'discounts' => new Collection(),
            'items' => new Collection([1 => $testCase])
        ]));
    }

    /**
     * Get an instance of the cart.
     *
     * @return Cart
     */
    private function getCart()
    {
        $session = $this->app->make('session');

        return new Cart($session);
    }

    /**
     * Get an instance of the cart.
     *
     * @param float $totalPrice
     * @param float $sourcePrice
     * @param null $discounts
     * @return Collection
     */
    private function getCartSkeleton($totalPrice = 0.0, $sourcePrice = 0.0, $discounts = null)
    {
        return new Collection([
            'totalPrice'  => $totalPrice,
            'sourcePrice' => $sourcePrice,
            'discounts'   => (!$discounts ? new Collection() : $discounts),
            'items'       => new Collection()
        ]);
    }

    /**
     * Get predefined array with test products
     *
     * @return array
     */
    private function getProductArray()
    {
        return [
            0 => ['id' => 1, 'name' => 'testCase', 'price' => 200, 'count' => 1],
            1 => ['id' => 2, 'name' => 'testCase2', 'price' => 150, 'count' => 1],
            2 => ['id' => 3, 'name' => 'testCase3', 'price' => 100, 'count' => 3],
            3 => ['id' => 1, 'name' => 'testCase', 'price' => 200, 'count' => 1],
            4 => ['id' => 1, 'name' => 'testCase', 'price' => 200, 'count' => 1],
            5 => ['id' => 3, 'name' => 'testCase3', 'price' => 100, 'count' => 1]
        ];
    }
}