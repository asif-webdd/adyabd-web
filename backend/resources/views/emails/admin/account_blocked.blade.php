@extends('emails.admin.layout')
@section('team', 'Platform Trust & Safety Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>We regret to inform you that your account has been <span class="badge badge-danger"> Suspended</span> due to a violation of our platform policies.</p>
    <div class="highlight-box danger">
        <p>During this period, you will not be able to place orders or access certain features.</p>
    </div>
    <p>If you believe this action was taken in error, please contact our support team with your registered phone number <strong>{{ $user->phone }}</strong>.</p>
@endsection
