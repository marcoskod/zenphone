<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceAdjustment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function suspend(User $user): RedirectResponse
    {
        // is_suspended is deliberately not in User::$fillable (it must never be settable
        // via a user-facing mass-assignment path), so it's set directly rather than
        // through update().
        $user->is_suspended = ! $user->is_suspended;
        $user->save();

        return back()->with('status', $user->is_suspended
            ? "Utilisateur {$user->email} suspendu."
            : "Utilisateur {$user->email} réactivé.");
    }

    /**
     * Manually adjusts a user's balance. Never bypasses the balance column directly -
     * every adjustment goes through increment()/decrement() and is recorded in
     * balance_adjustments with the admin who made it, the amount, the resulting
     * balance, and an optional reason, so the change is always auditable.
     */
    public function credit(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'amount_fcfa' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $validated['amount_fcfa'];

        DB::transaction(function () use ($user, $amount, $validated, $request) {
            if ($amount > 0) {
                $user->increment('balance', $amount);
            } else {
                $user->decrement('balance', abs($amount));
            }

            BalanceAdjustment::create([
                'user_id' => $user->id,
                'admin_id' => $request->user()->id,
                'amount_fcfa' => $amount,
                'balance_after' => $user->fresh()->balance,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return back()->with('status', "Solde de {$user->email} ajusté de ".number_format($amount, 0, ',', ' ').' FCFA.');
    }
}
