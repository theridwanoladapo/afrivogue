<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // Show logged-in user's cart
    public function index()
    {
        $cart = $this->cartService->getCart();

        return response()->json([
            'items' => $cart->items()->with('product')->get(),
            'total' => $cart->total()
        ]);
    }

    // Add product to cart
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $cart = $this->cartService->addItem($product, $validated['quantity'] ?? 1);

        return response()->json(['cart' => $cart->load('items.product')], 201);
    }

    // Update item quantity in cart
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->cartService->updateItem($id, $validated['quantity'] ?? 1);

        return response()->json(['cart' => $cart->load('items.product')], 201);
    }

    // Remove item from cart
    public function destroy($id)
    {
        $cart = $this->cartService->removeItem($id);

        return response()->json(['cart' => $cart->load('items.product')]);
    }
}
