<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if(isset($title))
        <title>{{ $title }}</title>
    @endif
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; background-color: #f4f4f4;">
<!-- Main Container -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 30px auto; background-color: #ffffff;">
    <tr>
        <td>
            <!-- Header -->
{{--            @if(isset($header))--}}
{{--                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fc5000;">--}}
{{--                    <tr>--}}
{{--                        <td align="center" style="padding: 30px 0;">--}}
{{--                            <!-- Logo -->--}}
{{--                            <img src="{{ $message->embed(embedImageFromUrl(settings('admin_logo','/images/admin_logo.png'))) }}" alt="{{ get_site_name() }}" style="max-width: 200px; display: block;">--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td style="color: #ffffff; text-align: center; padding: 40px 20px;">--}}
{{--                            @if(isset($header))--}}
{{--                                {{ $header }}--}}
{{--                            @endif--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </table>--}}

{{--            @else--}}
{{--                <table border="0"  style="background-color: #fc5000;" cellpadding="0" cellspacing="0" width="100%">--}}

{{--                    <tr>--}}
{{--                        <td style="padding: 2px;">--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </table>--}}
{{--            @endif--}}

            <table border="0"  style="background-color: #fc5000;" cellpadding="0" cellspacing="0" width="100%">

                <tr>
                    <td style="padding: 2px;">
                    </td>
                </tr>
            </table>

            <!-- Content -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="padding: 20px;">
                        {{ $slot }}
                    </td>
                </tr>
            </table>

            <!-- Footer -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
                <tr>
                    <td style="padding: 30px 20px; text-align: center;">

                        <!-- Contact Info -->
                        <div style="color: #666666; font-size: 14px; margin: 10px 0; font-style: italic;">
                            <p>© {{ date('Y') }}. All Rights Reserved. </p>
{{--                            <p>© {{ date('Y') }} {{ settings('site_name') }}. All Rights Reserved. </p>--}}
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
