<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * POST /api/contact - stores a support ticket from the bottom-bar contact modal.
     * No admin UI reads these yet - that's a future phase.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicket::create($validated);

        return response()->json([
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons rapidement.',
        ]);
    }
}
