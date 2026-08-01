@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img
    src="{{ url('/images/mark.png') }}"
    width="56"
    height="56"
    alt="{{ config('app.name') }}"
    style="display: block; border: 0; outline: none; text-decoration: none; margin: 0 auto 12px;"
>
@if (trim($slot) !== '' && trim($slot) !== 'Laravel')
<span class="break-all" style="display: block; font-size: 16px; font-weight: 600; color: #1a1a1a;">
{!! $slot !!}
</span>
@endif
</a>
</td>
</tr>
