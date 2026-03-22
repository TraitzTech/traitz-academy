@props(['url'])
@php
    $siteLogo = \App\Helpers\SettingHelper::get('site_logo');
    $siteName = \App\Helpers\SettingHelper::get('site_name', config('app.name'));
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($siteLogo)
<img src="{{ asset('storage/' . $siteLogo) }}" class="logo" alt="{{ $siteName }} Logo" style="max-height: 60px; width: auto;">
@else
{{ $siteName }}
@endif
</a>
</td>
</tr>
