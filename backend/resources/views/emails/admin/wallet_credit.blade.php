@extends('emails.admin.layout')
@section('team', 'Finance Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>Good news! Your wallet has been credited. </p>
    <div class="highlight-box">
        <p> Current wallet balance: <strong>BDT {{ number_format($user->wallet_balance ?? 0, 2) }}</strong></p>
    </div>
    <p>You can use this balance on your next purchase at checkout. Thank you for being a valued customer!</p>
@endsection
