<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', '');

        $tickets = SupportTicket::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.support.index', ['tickets' => $tickets, 'status' => $status]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('status', "Ticket #{$ticket->id} mis à jour.");
    }
}
