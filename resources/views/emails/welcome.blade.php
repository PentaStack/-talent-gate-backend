<html>
  <body style="font-family: Arial, sans-serif; line-height: 1.5; color: #0f172a;">
    <p>Hi {{ $user->name }},</p>
    <p>Welcome to Talent Gate - thanks for joining.</p>
    <p>Your account was created successfully. Please verify your email, then log in to get started.</p>

    <p style="margin: 24px 0;">
      <a
        href="{{ $verificationUrl }}"
        style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px;"
      >
        Verify Account
      </a>
    </p>

    <p>After verification, you will be redirected to the login page automatically.</p>
    <p>If the button does not work, copy this URL:</p>
    <p>{{ $verificationUrl }}</p>
  </body>
</html>
