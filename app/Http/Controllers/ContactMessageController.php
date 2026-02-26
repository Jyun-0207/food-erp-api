<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->has('isRead')) {
            $query->where('isRead', filter_var($request->input('isRead'), FILTER_VALIDATE_BOOLEAN));
        }

        $messages = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($messages);
    }

    public function store(ContactMessageRequest $request)
    {
        $message = new ContactMessage();
        $message->fill($request->validated())->save();

        return response()->json($message, 201);
    }

    public function show(string $id)
    {
        $message = ContactMessage::findOrFail($id);

        return response()->json($message);
    }

    public function update(Request $request, string $id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->isRead = !$message->isRead;
        $message->save();

        return response()->json($message);
    }

    public function destroy(string $id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
