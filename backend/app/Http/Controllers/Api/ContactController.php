<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * POST /api/v1/contact
     * Store a public contact form message (no auth required).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'email'    => 'nullable|email|max:150',
            'subject'  => 'required|string|max:255',
            'order_id' => 'nullable|string|max:50',
            'message'  => 'required|string|min:10|max:3000',
        ]);

        ContactMessage::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'status'     => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
        ]);
    }
}
