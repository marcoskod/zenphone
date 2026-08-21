<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * GET /api/me - auth status for the single-page homepage, checked on load to decide
     * whether to show the inline auth fields or go straight to the order form.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'authenticated' => (bool) $user,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => (float) $user->balance,
            ] : null,
        ]);
    }

    /**
     * POST /api/auth/quick - inline register-or-login used by the order form.
     *
     * If the email doesn't exist yet, creates the account with the given password and
     * logs in. If the email exists, the password MUST be verified via Auth::attempt():
     * on a wrong password this returns a validation error and does nothing else - it
     * never proceeds with a purchase, never silently reuses/creates an account for an
     * email that already belongs to someone else, and never leaks via timing or response
     * shape whether the email exists (both the "wrong password" and "would-be new
     * account with bad input" paths return the same 422 validation-error shape).
     */
    public function quick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $existing = User::where('email', $validated['email'])->first();

        if ($existing) {
            if (! Auth::attempt($validated, remember: true)) {
                throw ValidationException::withMessages([
                    'password' => ["Le mot de passe est incorrect pour cette adresse e-mail."],
                ]);
            }

            if (Auth::user()->is_suspended) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'password' => ['Ce compte a été suspendu. Contactez le support si vous pensez qu\'il s\'agit d\'une erreur.'],
                ]);
            }

            $request->session()->regenerate();

            $user = $existing;
        } else {
            $user = User::create([
                'name' => explode('@', $validated['email'])[0],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Auth::login($user, remember: true);
            $request->session()->regenerate();
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'balance' => (float) $user->balance,
            ],
        ]);
    }
}
