<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpBlock;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Cache, Hash, Storage, Validator};
use App\Services\ActivityLogger;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|unique:users,phone',
            'email'    => 'sometimes|email|nullable|unique:users,email',
            'password' => 'required|string|min:6',
        ], [
            'name.required'     => 'Name is required.',
            'name.max'          => 'Name may not exceed 100 characters.',
            'phone.required'    => 'Phone number is required.',
            'phone.unique'      => 'This phone number is already registered.',
            'email.email'       => 'Please enter a valid email address.',
            'email.unique'      => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        if (IpBlock::isBlocked($request->ip())) {
            return $this->error('Registration is restricted from your IP address.', 403);
        }

        $user = User::create([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'referral_code' => strtoupper(substr(md5($request->phone . time()), 0, 8)),
            'referred_by'   => $request->referral_code ? User::where('referral_code', $request->referral_code)->value('id') : null,
            'last_login_ip' => $request->ip(),
            'device_type'   => $request->header('X-Device-Type', 'web'),
        ]);

        $token = $user->createToken('api_token')->accessToken;
        return $this->success(['user' => $user, 'token' => $token], 'Registration successful!', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ], [
            'phone.required'    => 'Phone number or email is required.',
            'password.required' => 'Password is required.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        if (IpBlock::isBlocked($request->ip())) {
            return $this->error('Login is restricted from your IP address.', 403);
        }

        $user = User::where('phone', $request->phone)->orWhere('email', $request->phone)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid phone number / email or password.', 401);
        }
        if ($user->is_blocked) {
            return $this->error('Your account has been suspended. Reason: ' . ($user->blocked_reason ?? 'Policy violation'), 403);
        }

        $user->update(['last_login_ip' => $request->ip(), 'last_login_at' => now()]);
        $token = $user->createToken('api_token')->accessToken;

        ActivityLogger::logManual(
            logName: 'auth',
            event: 'login',
            action: ($user->name ?? $user->phone) . ' logged in via API',
            subject: $user,
            properties: [
                'ip'     => $request->ip(),
                'device' => $request->header('X-Device-Type', 'web'),
            ],
        );

        return $this->success(['user' => $user, 'token' => $token], 'Login successful!');
    }

    /** Wrong-OTP counter: after 5 wrong guesses the code is burned and the user must request a new one. */
    private function otpLockedOut(string $type, string $phone): bool
    {
        return (int) Cache::get("otp_fail_{$type}_{$phone}", 0) >= 5;
    }

    private function otpFailed(string $type, string $phone): void
    {
        $key = "otp_fail_{$type}_{$phone}";
        $n = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $n, now()->addMinutes(10));
        if ($n >= 5) {
            Cache::forget("otp_{$type}_{$phone}");
        }
    }

    /**
     * Verify OTP.
     *
     * Checks type-prefixed cache key (matching sendOtp).
     * For 'login' type: returns token if user exists, or just verified=true for new users.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
            'type'  => 'sometimes|in:login,forgot_password,verify_phone',
        ], [
            'phone.required' => 'Phone number is required.',
            'otp.required'   => 'OTP is required.',
            'otp.size'       => 'OTP must be exactly 6 digits.',
            'type.in'        => 'Invalid OTP type.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        $type   = $request->get('type', 'login');
        $cached = Cache::get("otp_{$type}_" . $request->phone);

        if ($this->otpLockedOut($type, $request->phone)) {
            return $this->error('Too many wrong attempts. Please request a new OTP.', 429);
        }
        if (!$cached || !hash_equals((string) $cached, (string) $request->otp)) {
            $this->otpFailed($type, $request->phone);
            return $this->error('Invalid or expired OTP.', 422);
        }

        Cache::forget("otp_{$type}_" . $request->phone);

        if ($type === 'login') {
            $user = User::where('phone', $request->phone)->first();
            if ($user) {
                $user->update(['phone_verified_at' => now()]);
                $token = $user->createToken('api_token')->accessToken;
                return $this->success(['user' => $user, 'token' => $token], 'OTP verified successfully!');
            }
            return $this->success(['verified' => true], 'OTP verified! Please complete your registration.');
        }

        return $this->success(['verified' => true], 'OTP verified successfully!');
    }

    /**
     * Alias for sendOtp with type=forgot_password.
     * Frontend forgot-password page already uses /auth/send-otp with type param.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'phone' => 'required|exists:users,phone',
        ], [
            'phone.required' => 'Phone number is required.',
            'phone.exists'   => 'No account found with this phone number.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        $request->merge(['type' => 'forgot_password']);
        return $this->sendOtp($request);
    }

    /**
     * Send OTP via SMS.
     *
     * Supported types:
     *  - 'login'            → 6-digit, expires in 5 min
     *  - 'forgot_password'  → 6-digit, expires in 10 min
     *  - 'verify_phone'     → 6-digit, expires in 5 min
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'phone' => 'required|string',
            'type'  => 'sometimes|in:login,forgot_password,verify_phone',
        ], [
            'phone.required' => 'Phone number is required.',
            'type.in'        => 'Invalid OTP type.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        $type = $request->get('type', 'login');
        $ttl  = $type === 'forgot_password' ? 600 : 300;             // 10 min vs 5 min
        $otp  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT); // CSPRNG, always 6 digits

        Cache::put("otp_{$type}_" . $request->phone, $otp, $ttl);
        Cache::forget("otp_fail_{$type}_" . $request->phone);
        Cache::forget("otp_attempts_{$type}_" . $request->phone);
        app(SmsService::class)->sendOtp($request->phone, $otp);

        return $this->success([], 'OTP has been sent.');
    }

    /**
     * Reset password after OTP verification.
     * Accepts both 'password' (frontend) and 'new_password' (legacy) field names.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $passwordField = $request->has('password') ? 'password' : 'new_password';

        $validation = Validator::make($request->all(), [
            'phone'                          => 'required|exists:users,phone',
            'otp'                            => 'required|string|size:6',
            $passwordField                   => 'required|string|min:8',
            $passwordField . '_confirmation' => 'sometimes|same:' . $passwordField,
        ], [
            'phone.required'    => 'Phone number is required.',
            'phone.exists'      => 'No account found with this phone number.',
            'otp.required'      => 'OTP is required.',
            'otp.size'          => 'OTP must be exactly 6 digits.',
            $passwordField . '.required' => 'New password is required.',
            $passwordField . '.min'      => 'Password must be at least 8 characters.',
            $passwordField . '_confirmation.same' => 'Password confirmation does not match.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        $cached = Cache::get('otp_forgot_password_' . $request->phone);
        if ($this->otpLockedOut('forgot_password', $request->phone)) {
            return $this->error('Too many wrong attempts. Please request a new OTP.', 429);
        }
        if (!$cached || !hash_equals((string) $cached, (string) $request->otp)) {
            $this->otpFailed('forgot_password', $request->phone);
            return $this->error('Invalid or expired OTP.', 422);
        }

        User::where('phone', $request->phone)->update([
            'password' => Hash::make($request->get($passwordField)),
        ]);
        Cache::forget('otp_forgot_password_' . $request->phone);

        return $this->success([], 'Password has been reset successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        ActivityLogger::logManual(
            logName: 'auth',
            event: 'logout',
            action: ($user->name ?? $user->phone) . ' logged out via API',
            subject: $user,
            properties: [
                'ip'     => $request->ip(),
                'device' => $request->header('X-Device-Type', 'web'),
            ],
        );

        $request->user()->currentAccessToken()->delete();
        return $this->success([], 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user()->load('addresses', 'seller:id,user_id,slug,shop_name,status'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validation = Validator::make($request->all(), [
            'name'               => 'sometimes|string|max:100',
            'email'              => 'sometimes|email|nullable|unique:users,email,' . $user->id,
            'date_of_birth'      => 'sometimes|nullable|date',
            'gender'             => 'sometimes|nullable|in:male,female,other',
            'preferred_language' => 'sometimes|in:bn,en',
        ], [
            'name.max'      => 'Name may not exceed 100 characters.',
            'email.email'   => 'Please enter a valid email address.',
            'email.unique'  => 'This email address is already in use.',
            'gender.in'     => 'Gender must be male, female, or other.',
            'preferred_language.in' => 'Language must be bn or en.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);
        }
        $user->update($request->only('name', 'email', 'date_of_birth', 'gender', 'preferred_language'));
        return $this->success($user->fresh(), 'Profile updated successfully.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'current_password'          => 'required',
            'new_password'              => 'required|string|min:6|confirmed',
            'new_password_confirmation' => 'required',
        ], [
            'current_password.required'          => 'Current password is required.',
            'new_password.required'              => 'New password is required.',
            'new_password.min'                   => 'New password must be at least 6 characters.',
            'new_password.confirmed'             => 'Password confirmation does not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
        ]);
        if ($validation->fails()) return $this->error($validation->errors(), 422);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return $this->error('Current password is incorrect.', 422);
        }
        $request->user()->update(['password' => Hash::make($request->new_password)]);
        return $this->success([], 'Password changed successfully.');
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return $this->success([], 'FCM token updated successfully.');
    }

    /**
     * POST /api/v1/auth/social-login   { provider: "google", token: "<Google ID token>" }
     *
     * The ID token is verified with Google's tokeninfo endpoint and must have been issued
     * for OUR client id (GOOGLE_CLIENT_ID in .env or the google_client_id setting).
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'provider' => 'required|in:google,facebook',
            'token'    => 'required|string',
        ]);
        if ($v->fails()) return $this->error($v->errors(), 422);

        if (IpBlock::isBlocked($request->ip())) {
            return $this->error('Login is restricted from your IP address.', 403);
        }

        $profile = $request->provider === 'facebook'
            ? $this->verifyFacebookToken($request->token)
            : $this->verifyGoogleToken($request->token);
        if ($profile instanceof JsonResponse) return $profile;

        // $profile = ['id' => provider user id, 'email' => ?, 'name' => ?, 'avatar' => ?]
        $idCol = $request->provider === 'facebook' ? 'facebook_id' : 'google_id';
        $user = User::where($idCol, $profile['id'])
            ->when($profile['email'], fn($q) => $q->orWhere('email', $profile['email']))->first();
        if (!$user) {
            $user = User::create([
                'name'              => $profile['name'] ?: ($profile['email'] ? strstr($profile['email'], '@', true) : 'Customer'),
                'email'             => $profile['email'],
                $idCol              => $profile['id'],
                'avatar'            => $profile['avatar'],
                'phone'             => null,
                'password'          => Hash::make(\Illuminate\Support\Str::random(40)), // unusable until reset
                'email_verified_at' => $profile['email'] ? now() : null,
                'referral_code'     => strtoupper(substr(md5($profile['id'] . time()), 0, 8)),
                'last_login_ip'     => $request->ip(),
                'device_type'       => $request->header('X-Device-Type', 'web'),
            ]);
        } elseif (!$user->{$idCol}) {
            $user->update([$idCol => $profile['id']]);
        }

        if ($user->is_blocked) {
            return $this->error('Your account has been suspended. Reason: ' . ($user->blocked_reason ?? 'Policy violation'), 403);
        }

        $user->update(['last_login_ip' => $request->ip(), 'last_login_at' => now()]);
        $token = $user->createToken('api_token')->accessToken;

        return $this->success(['user' => $user->load('seller:id,user_id,slug,shop_name,status'), 'token' => $token], 'Login successful!');
    }

    /** @return array{id:string,email:?string,name:?string,avatar:?string}|JsonResponse */
    private function verifyGoogleToken(string $idToken): array|JsonResponse
    {
        $clientId = setting('google_client_id') ?: config('services.google.client_id');
        if (!$clientId) return $this->error('Google login is not configured on this server.', 501);

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        } catch (\Throwable $e) {
            return $this->error('Could not reach Google. Please try again.', 502);
        }

        $p = $res->json();
        if (!$res->ok() || ($p['aud'] ?? null) !== $clientId
            || !in_array($p['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)
            || ($p['email_verified'] ?? 'false') !== 'true'
            || empty($p['email'])) {
            return $this->error('Google sign-in could not be verified.', 401);
        }
        return ['id' => (string) $p['sub'], 'email' => $p['email'], 'name' => $p['name'] ?? null, 'avatar' => $p['picture'] ?? null];
    }

    /** Verifies a Facebook user access token belongs to OUR app, then reads the profile from the Graph API. */
    private function verifyFacebookToken(string $accessToken): array|JsonResponse
    {
        $appId     = setting('facebook_app_id') ?: config('services.facebook.app_id');
        $appSecret = setting('facebook_app_secret') ?: config('services.facebook.app_secret');
        if (!$appId || !$appSecret) return $this->error('Facebook login is not configured on this server.', 501);

        try {
            $debug = \Illuminate\Support\Facades\Http::timeout(10)->get('https://graph.facebook.com/debug_token', [
                'input_token' => $accessToken, 'access_token' => $appId . '|' . $appSecret,
            ])->json('data');
            if (!($debug['is_valid'] ?? false) || (string) ($debug['app_id'] ?? '') !== (string) $appId) {
                return $this->error('Facebook sign-in could not be verified.', 401);
            }
            $me = \Illuminate\Support\Facades\Http::timeout(10)->get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email,picture.type(large)', 'access_token' => $accessToken,
            ])->json();
        } catch (\Throwable $e) {
            return $this->error('Could not reach Facebook. Please try again.', 502);
        }
        if (empty($me['id']) || (string) $me['id'] !== (string) ($debug['user_id'] ?? '')) {
            return $this->error('Facebook sign-in could not be verified.', 401);
        }
        return [
            'id' => (string) $me['id'], 'email' => $me['email'] ?? null, 'name' => $me['name'] ?? null,
            'avatar' => $me['picture']['data']['url'] ?? null,
        ];
    }
}
