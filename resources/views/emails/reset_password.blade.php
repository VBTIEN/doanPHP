@component('mail::message')
# Reset Your Password

You have requested to reset your password. Click the button below to proceed:

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

If you did not request this, please ignore this email.

Thanks,
{{ config('app.name') }}
@endcomponent