@extends('emails.admin.layout')
@section('team', 'Support Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>Your recent order has a <span class="badge badge-warning">⏳ Pending Payment</span>. To confirm your order, please complete the payment at your earliest convenience.</p>
    <div class="highlight-box warning">
        <p>If the payment has already been made, please allow a few minutes for it to reflect in our system.</p>
    </div>
    <p>Need help? Contact our support team anytime.</p>
@endsection
