@extends('emails.admin.layout')
@section('team', config('app.name') . ' Team')
@section('content')
    <p class="greeting">SMTP Test Successful! </p>
    <p>This is a test email sent from your <strong>{{ config('app.name') }}</strong> admin panel to verify your SMTP configuration.</p>
    <div class="highlight-box">
        <p> Your SMTP settings are working correctly. Emails will be delivered successfully.</p>
    </div>
    <p style="font-size:13px; color:#888;">
        Sent at: {{ now()->format('d M Y, h:i A') }}<br>
        Server: {{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }}
    </p>
@endsection
