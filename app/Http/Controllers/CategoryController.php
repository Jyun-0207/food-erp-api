<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('parent')->withCount('children');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('parentId')) {
            $query->where('parentId', $request->input('parentId'));
        }

        $categories = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($categories);
    }

    public function store(CategoryRequest $request)
    {
        $category = new Category();
        $category->fill($request->validated())->save();

        return response()->json($category->load('parent'), 201);
    }

    public function show(string $id)
    {
        $category = Category::with(['parent', 'children'])->withCount('children')->findOrFail($id);

        return response()->json($category);
    }

    public function update(CategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);
        $category->fill($request->validated())->save();

        return response()->json($category);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
