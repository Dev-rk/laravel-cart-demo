<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Utils\Cart\Cart;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Cart $cart)
    {
        /**
         * Example usage
         */

        // Find and add multiple products
        $products = Product::find([1, 2, 3, 4, 5, 6, 7, 8, 10]);
        $cart->addMultiple($products->toArray());

        // Attach discounts
        $cart->attachBuyOneGetOneDiscount();
        $cart->attachFixedDiscount(20, 20);
        $cart->attachDiscountCard('12323423434');

        // Add single product to cart
        $product = Product::select('id', 'name', 'price')->find(1);
        $cart->add($product->id, $product->name, $product->price, 1);

        // Update products in cart
        $cart->updateItem($product->id, ['count' => 15, 'name' => 'someAnotherName']);

        // Remove product from cart
        $cart->remove($product->id);

        // Get cart content
        $currentCart = $cart->getContent();

        // Detach discounts
        $cart->detachBuyOneGetOneDiscount();
        $cart->detachFixedDiscount();
        $cart->detachDiscountCard();

        // Clear cart
        $cart->flush();

        return view('welcome', compact('products'));
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        return view('home');
    }
}
