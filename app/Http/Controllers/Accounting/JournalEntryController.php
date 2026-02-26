<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::query();

        if ($startDate = $request->input('startDate')) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate = $request->input('endDate')) {
            $query->where('date', '<=', $endDate);
        }

        if ($reference = $request->input('reference')) {
            $query->where('reference', 'like', "%{$reference}%");
        }

        $entries = $query->orderBy('date', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($entries);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'entries' => ['required', 'array', 'min:1'],
            'reference' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'string'],
        ]);

        $entry = JournalEntry::create($data);

        return response()->json($entry, 201);
    }

    public function show(string $id)
    {
        $entry = JournalEntry::findOrFail($id);

        return response()->json($entry);
    }

    public function update(Request $request, string $id)
    {
        $entry = JournalEntry::findOrFail($id);

        $data = $request->validate([
            'date' => ['sometimes', 'date'],
            'description' => ['sometimes', 'string'],
            'entries' => ['sometimes', 'array', 'min:1'],
            'reference' => ['nullable', 'string'],
            'createdBy' => ['nullable', 'string'],
        ]);

        $entry->fill($data)->save();

        return response()->json($entry);
    }

    public function destroy(string $id)
    {
        $entry = JournalEntry::findOrFail($id);
        $entry->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
