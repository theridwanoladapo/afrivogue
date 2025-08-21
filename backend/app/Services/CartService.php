<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function getCart()
    {
        $user = Auth::user();

        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(Product $product, int $qty = 1)
    {
        $cart = $this->getCart();

        $item = $cart->items()->where(['product_id' => $product->id])->first();
        if ($item) {
            $item->update(['quantity' => $qty]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $qty,
                'price'      => $product->price,
            ]);
        }

        return $cart->refresh();
    }

    public function updateItem($itemId, int $qty)
    {
        $cart = $this->getCart();

        $item = $cart->items()->findOrFail($itemId);
        $item->update(['quantity' => $qty]);

        return $cart->refresh();
    }

    public function removeItem($itemId)
    {
        $cart = $this->getCart();

        $cart->items()->findOrFail($itemId)->delete();

        return $cart->refresh();
    }
}
