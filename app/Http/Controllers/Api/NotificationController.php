<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * POST /api/notifications/{id}/read - called from the notification bell dropdown
     * (both the customer dashboard and, potentially, the admin one, since both share
     * the same Notifiable User model). Looks the notification up scoped to the
     * authenticated user's own collection, so one user can never mark - or even
     * discover the existence of - another user's notification by guessing an id.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        abort_unless($notification !== null, 404);

        $notification->markAsRead();

        return response()->json(['status' => 'read']);
    }
}
