<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255',
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        $ticket = SupportTicket::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'subject'     => $request->subject,
            'description' => $request->description,
            'user_id'     => $request->user()?->id, // optional auth
        ]);

        return $this->sendResponse([
            'ticket_id' => $ticket->id,
        ],  'Support ticket submitted successfully.');
    }
}