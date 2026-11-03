@extends('emails.admin.layout')
@section('team', 'Support Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>We were unable to process your payment for a recent order. This may be due to a temporary issue with your payment method.</p>
    <div class="highlight-box danger">
        <p> Your cart has been saved. Please try again using a different card or payment method.</p>
    </div>
    <p>If you need assistance, our support team is ready to help at any time.</p>
@endsection
