<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('subject', setting('site_name', config('app.name')))</title>
</head>
<body style="margin:0;padding:0;background:#EDF1F2;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#15313C;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EDF1F2;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:560px;background:#FFFFFF;border:1px solid #D2DDE1;border-radius:6px;overflow:hidden;">

                <tr>
                    <td style="background:#15313C;padding:18px 24px;">
                        <a href="{{ url('/') }}" style="color:#FFFFFF;text-decoration:none;font-size:18px;font-weight:700;">
                            {{ setting('site_name', config('app.name')) }}
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;font-size:15px;line-height:1.6;">
                        @yield('body')
                    </td>
                </tr>

                <tr>
                    <td style="background:#F5F9FA;border-top:1px solid #E3EAEC;padding:16px 24px;font-size:12px;color:#7A929C;line-height:1.5;">
                        {{ __('mail.common.footer_auto', ['site' => setting('site_name', config('app.name'))]) }}
                        @if (setting('site_phone'))
                            <br>{{ setting('site_phone') }}
                        @endif
                        @if (setting('site_email'))
                            · {{ setting('site_email') }}
                        @endif
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
