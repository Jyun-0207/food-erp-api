<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartOfAccount::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $accounts = $query->orderBy('code', 'asc')->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string'],
            'parentCode' => ['nullable', 'string'],
            'balance' => ['nullable', 'numeric'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $account = ChartOfAccount::create($data);

        return response()->json($account, 201);
    }

    public function show(string $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        return response()->json($account);
    }

    public function update(Request $request, string $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:20'],
            'name' => ['sometimes', 'string', 'max:200'],
            'type' => ['sometimes', 'string'],
            'parentCode' => ['nullable', 'string'],
            'balance' => ['nullable', 'numeric'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $account->fill($data)->save();

        return response()->json($account);
    }

    public function destroy(string $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        // Check if products reference this account
        $productCount = \App\Models\Product::where('salesAccountId', $id)
            ->orWhere('purchaseAccountId', $id)
            ->count();
        if ($productCount > 0) {
            return response()->json(['message' => '此科目有相關產品使用中，無法刪除'], 409);
        }

        $account->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
