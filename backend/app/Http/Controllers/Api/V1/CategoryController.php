<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // List all categories
    public function index()
    {
        $categories = Category::all();

        return response()->json($categories);
    }

    // Show a single category with its products
    public function show($id)
    {
        $category = Category::with('products')->findOrFail($id);

        return response()->json($category);
    }

    // Create a new category (Admin only)
    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('name'))
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    // Update an existing category (Admin only)
    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => Str::slug($request->input('name'))
        ]);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
        ]);
        
        $category = Category::findOrFail($id);
        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    // Delete a category (Admin only)
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
