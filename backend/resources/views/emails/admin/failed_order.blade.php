@extends('emails.admin.layout')
@section('team', 'Support Team')
@section('content')
    <p class="greeting">Dear {{ $user->name }},</p>
    <p>We noticed that your recent order could not be completed. This may have been due to a technical issue.</p>
    <div class="highlight-box warning">
        <p>Your cart has been saved — you can try again at any time.</p>
    </div>
    <p>Please try again or contact our support team.</p>
@endsection
