<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /** GET /api/v1/user/profile */
    public function profile()
    {
        return response()->json(Auth::user());
    }

    /** PUT /api/v1/user/profile */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'               => 'sometimes|string|max:100',
            'email'              => 'sometimes|email|unique:users,email,' . $user->id,
            'date_of_birth'      => 'sometimes|nullable|date',
            'gender'             => 'sometimes|nullable|in:male,female,other',
            'preferred_language' => 'sometimes|in:bn,en',
        ]);

        // A phone number can be ADDED (social-login accounts have none) but not swapped without OTP.
        if (blank($user->phone) && $request->filled('phone')) {
            $request->validate(['phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/', 'unique:users,phone']]);
            $validated['phone'] = $request->phone;
        }

        $user->update($validated);

        return response()->json($user->fresh());
    }

    /**
     * PUT /api/v1/user/password
     * Change password from account/profile page.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /**
     * POST /api/v1/user/avatar
     * Upload or replace profile avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = Auth::user();

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Profile picture updated.',
            'avatar'  => $path,
        ]);
    }

    /**
     * DELETE /api/v1/user/account
     * Soft-delete (or hard-delete) the authenticated user's account.
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        // Revoke all tokens
        $user->tokens()->delete();

        // phone/email/google_id are UNIQUE and soft-deleted rows keep them, which would block
        // the person from ever registering again: anonymise them first.
        $stamp = '_del' . $user->id . '_' . time();
        $user->forceFill([
            'phone'     => $user->phone ? substr($user->phone, 0, 8) . $stamp : null,
            'email'     => $user->email ? $stamp . '@deleted.invalid' : null,
            'google_id' => null,
            'fcm_token' => null,
        ])->saveQuietly();

        $user->delete();

        return response()->json(['message' => 'Account has been deleted.']);
    }

    /** GET /api/v1/user/wallet */
    public function wallet()
    {
        $user = Auth::user();
        return response()->json([
            'balance'    => $user->wallet_balance ?? 0,
            'currency'   => 'BDT',
            'updated_at' => now(),
        ]);
    }

    /** GET /api/v1/user/transactions */
    public function transactions(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * POST /api/v1/user/wallet/withdraw
     * Request a wallet withdrawal.
     */
    public function walletWithdraw(Request $request)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:100',
            'method'         => 'required|in:bkash,nagad,rocket,bank',
            'account_number' => 'required|string|max:30',
        ]);

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            // Lock the row: two parallel requests must not both pass the balance check.
            $user = \App\Models\User::whereKey(Auth::id())->lockForUpdate()->first();

            if ($user->wallet_balance < $validated['amount']) {
                return ['error' => 'Insufficient balance.'];
            }
            if (Transaction::where('user_id', $user->id)->where('status', 'pending')
                ->whereNotNull('payment_method')->where('type', 'debit')->count() >= 3) {
                return ['error' => 'You already have pending withdrawal requests. Please wait for them to be processed.'];
            }

            $before = (float) $user->wallet_balance;
            $user->decrement('wallet_balance', $validated['amount']);

            return ['tx' => Transaction::create([
                'user_id'        => $user->id,
                'type'           => 'debit',
                'amount'         => $validated['amount'],
                'balance_before' => $before,
                'balance_after'  => $before - (float) $validated['amount'],
                'description'    => $validated['method'] . ' Withdrawal: ' . $validated['account_number'],
                'transaction_id' => 'WD-' . strtoupper(uniqid()),
                'payment_method' => $validated['method'],
                'status'         => 'pending',
            ])];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'message'     => 'Withdrawal request received. Will be processed within 3-5 business days.',
            'transaction' => $result['tx'],
        ]);
    }

    /** GET /api/v1/user/dashboard */
    public function dashboard()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id);

        return response()->json([
            'total_orders'     => (clone $orders)->count(),
            'pending_orders'   => (clone $orders)->where('status', 'pending')->count(),
            'completed_orders' => (clone $orders)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
            'wallet_balance'   => $user->wallet_balance ?? 0,
            'recent_orders'    => (clone $orders)->with(['items' => fn($q) => $q->limit(1)])->latest()->limit(3)->get(),
        ]);
    }
}
