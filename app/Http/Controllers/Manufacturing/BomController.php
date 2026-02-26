<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use Illuminate\Http\Request;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $query = Bom::with('product');

        if ($search = $request->input('search')) {
            $query->where('productName', 'like', "%{$search}%");
        }

        $boms = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($boms);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'productId' => ['required', 'string'],
            'productName' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $bom = Bom::create($data);

        return response()->json($bom->load('product'), 201);
    }

    public function show(string $id)
    {
        $bom = Bom::with('product')->findOrFail($id);

        return response()->json($bom);
    }

    public function update(Request $request, string $id)
    {
        $bom = Bom::findOrFail($id);

        $data = $request->validate([
            'productId' => ['sometimes', 'string'],
            'productName' => ['sometimes', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $bom->fill($data)->save();

        return response()->json($bom->load('product'));
    }

    public function destroy(string $id)
    {
        $bom = Bom::findOrFail($id);
        $bom->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
