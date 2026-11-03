<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AdminUser, SupportTicket, SupportMessage};
use App\Notifications\SupportTicketNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with('messages')
            ->orderByDesc('created_at')
            ->get();
        return $this->success($tickets);
    }

    public function create(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'subject'  => 'required|string|max:200',
            'category' => 'required|in:order,payment,delivery,return,account,other',
            'message'  => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $ticket = SupportTicket::create([
            'user_id'  => $request->user()->id,
            'order_id' => $request->order_id,
            'subject'  => $request->subject,
            'category' => $request->category,
            'priority' => 'medium',
            'status'   => 'open',
        ]);

        SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $request->user()->id,
            'sender_type' => 'user',
            'message'     => $request->message,
        ]);

        // Notify all active admins about the new support ticket
        $customerName = $request->user()->name ?? $request->user()->phone;
        AdminUser::where('is_active', true)->each(
            fn ($admin) => $admin->notify(
                new SupportTicketNotification($ticket->id, $ticket->subject, $customerName)
            )
        );

        return $this->success($ticket->load('messages'), 'Ticket created.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('messages')
            ->firstOrFail();
        return $this->success($ticket);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $v = Validator::make($request->all(), ['message' => 'required|string']);
        if ($v->fails()) return $this->error($v->errors(), 422);

        $msg = SupportMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $request->user()->id,
            'sender_type' => 'user',
            'message'     => $request->message,
        ]);

        return $this->success($msg, 'Message sent.');
    }
}
