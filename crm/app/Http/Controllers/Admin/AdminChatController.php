<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChatController extends Controller
{
    public function getUsersWithChat(Request $request)
    {
        $user = auth()->user();
        $users = User::query()->where('id', '!=', $user->id)
            ->withCount(['unreadMessages' => function($query) {
                $query->where('is_admin', false)
                    ->whereNull('read_at');
            }])
            ->with(['lastMessage'])
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_online' => $user->is_online,
                    'unread_count' => $user->unread_messages_count,
                    'last_message' => $user->lastMessage ? $user->lastMessage->message : null,
                    'last_message_time' => $user->lastMessage ? $user->lastMessage->created_at : null,
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function getMessages(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $messages = Message::where(function($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->with(['attachments', 'sender'])
            ->orderBy('created_at','asc')
            ->paginate(20);

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'message' => 'required_without:attachments',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'sometimes|string',
        ]);

        $message = new Message([
            'message' => $request->message,
            'sender_id' => $request->user()->id,
            'receiver_id' => $userId,
            'is_admin' => true,
        ]);

        $message->save();

        // Handle attachments if any
        if ($request->has('attachments') && is_array($request->attachments)) {
            foreach ($request->attachments as $attachmentUrl) {
                // Convert URL to file info and save attachment
                $this->saveAttachmentFromUrl($attachmentUrl, $message->id);
            }
        }

        // Load attachments relationship after saving
        $message->load('attachments', 'sender');

        // Broadcast the message
        broadcast(new MessageSent($message));
//        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }


}
