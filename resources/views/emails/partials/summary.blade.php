@php
    $locale = app()->getLocale();
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#F5F9FA;border:1px solid #E3EAEC;border-radius:4px;margin:16px 0;">
    <tbody>
        @foreach ([
            'code' => $reservation->code,
            'yacht' => $yacht->getTranslation('name', $locale),
            'dates' => $reservation->starts_at->format('d.m.Y H:i').' — '.$reservation->ends_at->format('d.m.Y H:i'),
            'guests' => $reservation->guests,
            'estimate' => money($reservation->estimated_total, $reservation->currency),
        ] as $key => $value)
            <tr>
                <td style="padding:8px 14px;font-size:13px;color:#7A929C;width:40%;border-bottom:1px solid #E3EAEC;">
                    {{ __('mail.common.'.$key) }}
                </td>
                <td style="padding:8px 14px;font-size:14px;font-weight:600;border-bottom:1px solid #E3EAEC;">
                    {{ $value }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if (! empty($showCustomer))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="background:#F4E8D2;border:1px solid #E8D5AE;border-radius:4px;margin:16px 0;">
        <tbody>
            @foreach ([
                'customer' => $reservation->customer_name,
                'phone' => $reservation->customer_phone,
                'email' => $reservation->customer_email,
            ] as $key => $value)
                <tr>
                    <td style="padding:8px 14px;font-size:13px;color:#7A5314;width:40%;">{{ __('mail.common.'.$key) }}</td>
                    <td style="padding:8px 14px;font-size:14px;font-weight:600;color:#7A5314;">{{ $value }}</td>
                </tr>
            @endforeach
            @if ($reservation->message)
                <tr>
                    <td style="padding:8px 14px;font-size:13px;color:#7A5314;">{{ __('mail.common.note') }}</td>
                    <td style="padding:8px 14px;font-size:14px;color:#7A5314;">{{ $reservation->message }}</td>
                </tr>
            @endif
        </tbody>
    </table>
@endif
