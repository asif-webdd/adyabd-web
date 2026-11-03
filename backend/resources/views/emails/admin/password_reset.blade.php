@extends('emails.admin.layout')
@section('team', config('app.name') . ' Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>We received a request to reset the password for your account. Click the button below to set a new password.</p>
    <div style="text-align:center; margin: 28px 0;">
        <a href="{{ $resetUrl }}"
           style="display:inline-block; background:#006A4E; color:#fff; text-decoration:none;
                  padding:14px 32px; border-radius:8px; font-size:15px; font-weight:600;">
             Reset My Password
        </a>
    </div>
    <div class="highlight-box warning">
        <p>⏳ This link will expire in <strong>60 minutes</strong>. If you did not request a password reset, you can safely ignore this email.</p>
    </div>
    <p style="font-size:13px; color:#888;">If the button doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $resetUrl }}" style="color:#006A4E; word-break:break-all;">{{ $resetUrl }}</a>
    </p>
@endsection
