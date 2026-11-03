@extends('emails.admin.layout')
@section('team', config('app.name') . ' Team')
@section('content')
    <p class="greeting">Welcome, {{ $user->name }}! </p>
    <p>We're thrilled to have you on board. Your account is now active and ready to use.</p>
    <div class="highlight-box">
        <p> Your wallet balance: <strong>BDT {{ number_format($user->wallet_balance ?? 0, 2) }}</strong> — ready to use on your next order!</p>
    </div>
    <p>Start exploring our wide range of products and enjoy a seamless shopping experience.</p>
@endsection
