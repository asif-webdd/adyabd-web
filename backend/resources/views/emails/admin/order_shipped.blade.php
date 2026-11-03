@extends('emails.admin.layout')
@section('team', 'Delivery Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>Great news!  Your order is on its way.</p>
    <div class="highlight-box">
        <p> Our courier partner will deliver your order to your address shortly. You can track your order from the app or website.</p>
    </div>
    <p>Thank you for shopping with us! We hope you love your purchase.</p>
@endsection
