<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // List all orders
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', Auth::id())
            ->latest()->get();

        return response()->json($orders);
    }

    // Show single order details
    public function show($id)
    {
        $order = Order::with('items.product')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($order);
    }

    // Checkout: create order from the cart
    public function store(Request $request)
    {
        $user = Auth::user();
        $cartItems = Cart::with('items')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,   // update later
                'order_status' => 'pending',
            ]);

            $total = 0;

            foreach ($cartItems as $item) {
                $product = $item->product;

                if ($product->stock < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Product {$product->name} is out of stock"
                    ], 400);
                }

                // Deduct stock
                $product->decrement('stock', $item->quantity);

                // Save order item
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item->quantity,
                    'price'      => $product->price,
                ]);

                $total += $product->price * $item->quantity;
            }

            // Update order total
            $order->update(['total' => $total]);

            // Clear user cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order'   => $order->load('items.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Cancel order (if still pending)
    public function cancel($id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order cannot be canceled'], 400);
        }

        $order->update(['status' => 'canceled']);

        return response()->json(['message' => 'Order canceled successfully']);
    }
}
