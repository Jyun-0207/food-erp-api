<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitorRequest;
use App\Models\VisitorRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $visitors = VisitorRecord::orderBy('timestamp', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($visitors);
    }

    public function store(Request $request)
    {
        $visitor = new VisitorRecord();
        $visitor->fill($request->only(['sessionId', 'page', 'referrer', 'userAgent']));

        if (empty($visitor->sessionId)) {
            $visitor->sessionId = $request->header('X-Session-Id', (string) Str::ulid());
        }

        $visitor->save();

        return response()->json(['ok' => true], 201);
    }
}
