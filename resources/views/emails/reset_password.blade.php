@component('mail::message')
# Reset Your Password

You have requested to reset your password. Click the button below to proceed:

@component('mail::button', ['url' => $url, 'color' => 'blue'])
Reset Password
@endcomponent

If you did not request this, please ignore this email.

<p style="color: #6b7280; font-size: 14px; text-align: center;">
    This link will expire in 24 hours.
</p>

Thanks,  
<span style="color: #1e3a8a; font-weight: bold;">{{ config('app.name') }}</span>
@endcomponent