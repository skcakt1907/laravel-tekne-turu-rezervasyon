@php
    $showCustomer = $showCustomer ?? false;
    $notice = $notice ?? true;
@endphp

<p style="margin:0 0 12px;">{{ __('mail.common.hello', ['name' => $greetName]) }}</p>

<p style="margin:0 0 12px;font-size:16px;font-weight:600;">{{ __('mail.'.$template.'.intro') }}</p>

@include('emails.partials.summary', ['showCustomer' => $showCustomer])

<p style="margin:0 0 12px;color:#54707C;">{{ __('mail.'.$template.'.body') }}</p>

@if (! empty($extraNote))
    <p style="margin:0 0 12px;padding:10px 14px;background:#F4DEDA;border-left:3px solid #98392F;color:#98392F;font-size:14px;">
        {{ $extraNote }}
    </p>
@endif

@isset($buttonUrl)
    @include('emails.partials.button', ['url' => $buttonUrl, 'label' => $buttonLabel])
@endisset

@if ($notice)
    <p style="margin:0 0 6px;font-size:13px;color:#7A929C;">{{ __('mail.common.estimate_notice') }}</p>
    <p style="margin:0;font-size:13px;color:#7A929C;">{{ __('mail.common.no_payment') }}</p>
@endif

<p style="margin:18px 0 0;color:#54707C;">{{ __('mail.common.regards') }}</p>
