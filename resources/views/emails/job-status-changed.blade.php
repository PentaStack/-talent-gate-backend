<html>
  <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #0f172a; max-width: 600px; margin: 0 auto; padding: 24px;">
    <p>Hi {{ $job->employer->name }},</p>

    @if ($job->status->value === 'active')
      <p>Great news! Your job listing <strong>{{ $job->title }}</strong> has been <strong style="color: #16a34a;">approved</strong> and is now live on Talent Gate.</p>
      <p>Candidates can now discover and apply to your posting.</p>
    @else
      <p>Unfortunately, your job listing <strong>{{ $job->title }}</strong> was <strong style="color: #dc2626;">not approved</strong> at this time.</p>
      @if ($job->rejection_reason)
        <p><strong>Reason:</strong> {{ $job->rejection_reason }}</p>
      @endif
      <p>You may edit your posting to address the feedback and resubmit it for review.</p>
    @endif

    <p style="margin: 28px 0;">
      <a
        href="{{ config('app.frontend_url') }}/employer/jobs"
        style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600;"
      >
        View My Jobs
      </a>
    </p>

    <p style="color: #64748b; font-size: 0.85em;">— The Talent Gate Team</p>
  </body>
</html>
