@extends('emails.admin.layout')
@section('team', 'Platform Trust & Safety Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>We are writing to inform you that your account has received a <span class="badge badge-warning"> Formal Warning</span> due to activity that violates our platform policies.</p>
    <div class="highlight-box warning">
        <p>Please review our terms of service. Continued violations may result in your account being suspended.</p>
    </div>
    <p>If you believe this is an error, please contact our support team immediately.</p>
@endsection
