<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRecord::query();

        if ($employeeId = $request->input('employeeId')) {
            $query->where('employeeId', $employeeId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('date', $date);
        }

        if ($startDate = $request->input('startDate')) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate = $request->input('endDate')) {
            $query->where('date', '<=', $endDate);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $records = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        // Normalize: date → "YYYY-MM-DD", checkInTime/checkOutTime → "HH:mm"
        $records->getCollection()->transform(fn ($r) => $this->normalizeRecord($r));

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employeeId' => ['required', 'string'],
            'employeeName' => ['required', 'string'],
            'date' => ['required', 'string'],
            'shiftTypeId' => ['nullable', 'string'],
            'shiftTypeName' => ['nullable', 'string'],
            'checkInTime' => ['nullable', 'string'],
            'checkOutTime' => ['nullable', 'string'],
            'workHours' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $dateStr = $data['date'];

        // Convert "HH:mm" → DateTime for DB storage
        if (!empty($data['checkInTime'])) {
            $data['checkInTime'] = $this->timeToDateTime($dateStr, $data['checkInTime']);
        }
        if (!empty($data['checkOutTime'])) {
            $data['checkOutTime'] = $this->timeToDateTime($dateStr, $data['checkOutTime']);
        }

        $record = AttendanceRecord::create($data);

        return response()->json($this->normalizeRecord($record), 201);
    }

    public function show(string $id)
    {
        $record = AttendanceRecord::findOrFail($id);

        return response()->json($this->normalizeRecord($record));
    }

    public function update(Request $request, string $id)
    {
        $record = AttendanceRecord::findOrFail($id);

        $data = $request->validate([
            'employeeId' => ['sometimes', 'string'],
            'employeeName' => ['sometimes', 'string'],
            'date' => ['sometimes', 'string'],
            'shiftTypeId' => ['nullable', 'string'],
            'shiftTypeName' => ['nullable', 'string'],
            'checkInTime' => ['nullable', 'string'],
            'checkOutTime' => ['nullable', 'string'],
            'workHours' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $dateStr = $data['date'] ?? ($record->date ? Carbon::parse($record->date)->format('Y-m-d') : date('Y-m-d'));

        // Convert "HH:mm" → DateTime for DB storage
        if (array_key_exists('checkInTime', $data)) {
            $data['checkInTime'] = !empty($data['checkInTime'])
                ? $this->timeToDateTime($dateStr, $data['checkInTime'])
                : null;
        }
        if (array_key_exists('checkOutTime', $data)) {
            $data['checkOutTime'] = !empty($data['checkOutTime'])
                ? $this->timeToDateTime($dateStr, $data['checkOutTime'])
                : null;
        }

        $record->fill($data)->save();

        return response()->json($this->normalizeRecord($record));
    }

    public function destroy(string $id)
    {
        $record = AttendanceRecord::findOrFail($id);
        $record->delete();

        return response()->json(['message' => '刪除成功']);
    }

    /**
     * Convert "HH:mm" + "YYYY-MM-DD" → Carbon DateTime
     */
    private function timeToDateTime(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}:00");
    }

    /**
     * Convert DateTime → "HH:mm" string, or null
     */
    private function dateTimeToTime($dt): ?string
    {
        if (!$dt) return null;
        return Carbon::parse($dt)->format('H:i');
    }

    /**
     * Normalize record for frontend: date → "YYYY-MM-DD", times → "HH:mm"
     */
    private function normalizeRecord(AttendanceRecord $record): array
    {
        $data = $record->toArray();
        $data['date'] = $record->date ? Carbon::parse($record->date)->format('Y-m-d') : null;
        $data['checkInTime'] = $this->dateTimeToTime($record->checkInTime);
        $data['checkOutTime'] = $this->dateTimeToTime($record->checkOutTime);
        return $data;
    }
}
