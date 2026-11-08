@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="header-link">
<img src="{{ asset('icons/icon-192.png') }}" class="logo" width="40" height="40" alt="">
<span class="wordmark">{{ $slot }}</span>
</a>
</td>
</tr>
