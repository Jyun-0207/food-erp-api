<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $holidays = Holiday::where('year', $year)->orderBy('date')->get();
        return response()->json($holidays);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $date = $request->input('date');
        $year = (int) substr($date, 0, 4);

        $holiday = Holiday::create([
            'name' => $request->input('name'),
            'date' => $date,
            'year' => $year,
            'description' => $request->input('description'),
        ]);

        return response()->json($holiday, 201);
    }

    public function update(Request $request, string $holiday)
    {
        $record = Holiday::findOrFail($holiday);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'date' => ['sometimes', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $request->only(['name', 'date', 'description']);
        if (isset($data['date'])) {
            $data['year'] = (int) substr($data['date'], 0, 4);
        }

        $record->update($data);
        return response()->json($record);
    }

    public function destroy(string $holiday)
    {
        Holiday::findOrFail($holiday)->delete();
        return response()->json(null, 204);
    }
}
