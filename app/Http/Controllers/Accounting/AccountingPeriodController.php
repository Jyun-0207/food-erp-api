<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountingPeriod::query();

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $periods = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($periods);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periodType' => ['required', 'string'],
            'year' => ['required', 'integer'],
            'month' => ['nullable', 'integer'],
            'quarter' => ['nullable', 'integer'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date'],
            'status' => ['nullable', 'string'],
            'closedBy' => ['nullable', 'string'],
            'trialBalance' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $period = AccountingPeriod::create($data);

        return response()->json($period, 201);
    }

    public function show(string $id)
    {
        $period = AccountingPeriod::findOrFail($id);

        return response()->json($period);
    }

    public function update(Request $request, string $id)
    {
        $period = AccountingPeriod::findOrFail($id);

        $data = $request->validate([
            'periodType' => ['sometimes', 'string'],
            'year' => ['sometimes', 'integer'],
            'month' => ['nullable', 'integer'],
            'quarter' => ['nullable', 'integer'],
            'startDate' => ['sometimes', 'date'],
            'endDate' => ['sometimes', 'date'],
            'status' => ['nullable', 'string'],
            'closedAt' => ['nullable', 'date'],
            'closedBy' => ['nullable', 'string'],
            'trialBalance' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $period->fill($data)->save();

        return response()->json($period);
    }

    public function destroy(string $id)
    {
        $period = AccountingPeriod::findOrFail($id);
        $period->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
