<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // List all products with pagination
    public function index()
    {
        $products = Product::with('category')->paginate(10);

        return response()->json($products);
    }

    // Show a single product details
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json($product);
    }

    // Store new product (Admin only)
    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('name'))
        ]);
        // Validate product, images, and variants
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer',
            'status' => 'required|string',
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'color' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Create product
        $product = Product::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'stock' => $validated['stock'],
            'status' => $validated['status'],
        ]);

        // Handle variants if provided
        $product->variants()->create([
            'sku' => $validated['sku'],
            'color' => $validated['color'],
            'size' => $validated['size'],
        ]);

        // Handle images if provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path
                ]);
            }
        }

        // Load relations for response
        $product->load(['images', 'variants']);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    // Update an existing product (Admin only)
    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => Str::slug($request->input('name'))
        ]);

        // Validate product, images, and variants
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $id,
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'category_id' => 'sometimes|required|exists:categories,id',
            'stock' => 'sometimes|required|integer',
            'status' => 'sometimes|required|string',
            'sku' => 'sometimes|required|string|max:255|unique:product_variants,sku,' . $id,
            'color' => 'sometimes|required|string|max:255',
            'size' => 'sometimes|required|string|max:255',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $product = Product::findOrFail($id);
        $product->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'stock' => $validated['stock'],
            'status' => $validated['status']
        ]);

        // Handle variants if provided
        $product->variants()->create([
            'sku' => $validated['sku'],
            'color' => $validated['color'],
            'size' => $validated['size'],
        ]);

        // Handle images if provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->createOrUpdate([
                    'image_path' => $path
                ], [
                    'image_path' => $path
                ]);
            }
        }

        // Load relations for response
        $product->load(['images', 'variants']);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    // Remove an existing product (Admin only)
    public function destroy($id)
    {
        $product = Product::with(['images', 'variants'])->findOrFail($id);

        foreach ($product->images as $image) {
            // Delete the image file from storage
            \Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        $product->variants()->delete();
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 204);
    }
}
