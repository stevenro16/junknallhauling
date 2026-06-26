@php
    $bizName = config('business.name');
    $bizPhone = config('business.phone');
    $bizEmail = config('business.email');
    $lines = $lines ?? [];
    $details = $details ?? [];
    $heading = $heading ?? $bizName;
    $ctaLabel = $ctaLabel ?? null;
    $ctaUrl = $ctaUrl ?? null;
    $footnote = $footnote ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
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
                            <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;color:#1C1C1C;">{{ $heading }}</h1>

                            @foreach($lines as $line)
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#3f3f46;">{{ $line }}</p>
                            @endforeach

                            @if(! empty($details))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;border-top:1px solid #e4e4e7;">
                                    @foreach($details as $label => $value)
                                        <tr>
                                            <td style="padding:9px 0;font-size:13px;color:#71717a;border-bottom:1px solid #f1f1f4;width:38%;vertical-align:top;">{{ $label }}</td>
                                            <td style="padding:9px 0;font-size:14px;color:#18181b;font-weight:600;border-bottom:1px solid #f1f1f4;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if($ctaUrl && $ctaLabel)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 6px;">
                                    <tr>
                                        <td style="border-radius:8px;background-color:#F8C820;">
                                            <a href="{{ $ctaUrl }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#1C1C1C;text-decoration:none;border-radius:8px;">{{ $ctaLabel }} &rarr;</a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:12px 0 0;font-size:12px;line-height:1.5;color:#a1a1aa;word-break:break-all;">Or paste this link into your browser:<br>{{ $ctaUrl }}</p>
                            @endif

                            @if($footnote)
                                <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#71717a;">{{ $footnote }}</p>
                            @endif
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
