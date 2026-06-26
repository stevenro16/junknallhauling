@php
    $bizName = config('business.name');
    $bizPhone = config('business.phone');
    $bizEmail = config('business.email');
    $title = $content['title'] ?? 'Rental Agreement';
    $acks = $content['acknowledgments'] ?? [];
    $instructions = $content['instructions'] ?? null;
    $signedOn = '';
    try { $signedOn = \Illuminate\Support\Carbon::parse($signedAt)->format('F j, Y \a\t g:i A'); } catch (\Throwable $e) {}
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#1C1C1C;padding:20px 28px;">
                            <span style="color:#F8C820;font-size:20px;font-weight:800;letter-spacing:0.5px;">{{ $bizName }}</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 8px;font-size:20px;line-height:1.3;color:#1C1C1C;">{{ $title }}</h1>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3f3f46;">Thank you{{ $inquiry->name ? ', '.e(explode(' ', $inquiry->name)[0]) : '' }}. Your rental agreement has been signed — a copy is below for your records.</p>

                            {{-- Signed confirmation --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                                <tr>
                                    <td style="padding:12px 16px;font-size:14px;color:#166534;">
                                        &#10003; Signed by <strong>{{ $inquiry->name ?: 'the customer' }}</strong>@if($signedOn) on {{ $signedOn }}@endif.
                                        @if($inquiry->ref)<br><span style="color:#15803d;">Reference: {{ $inquiry->ref }}</span>@endif
                                    </td>
                                </tr>
                            </table>

                            {{-- Terms --}}
                            @if(! empty($acks))
                                <h2 style="margin:0 0 10px;font-size:15px;color:#1C1C1C;">Agreed Terms</h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
                                    @foreach($acks as $ack)
                                        <tr>
                                            <td style="padding:4px 8px 4px 0;font-size:13px;color:#F8C820;vertical-align:top;width:18px;">&#10003;</td>
                                            <td style="padding:4px 0;font-size:14px;line-height:1.6;color:#3f3f46;">{{ $ack }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if($instructions)
                                <div style="margin:0 0 8px;padding:14px 16px;background-color:#fafafa;border:1px solid #ececec;border-radius:8px;font-size:13px;line-height:1.6;color:#52525b;white-space:pre-line;">{{ $instructions }}</div>
                            @endif

                            <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#71717a;">If anything looks incorrect, please contact us right away at {{ $bizPhone }}.</p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 28px;background-color:#fafafa;border-top:1px solid #ececec;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#a1a1aa;">
                                {{ $bizName }} &middot;
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $bizPhone) }}" style="color:#a1a1aa;text-decoration:none;">{{ $bizPhone }}</a> &middot;
                                <a href="mailto:{{ $bizEmail }}" style="color:#a1a1aa;text-decoration:none;">{{ $bizEmail }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
