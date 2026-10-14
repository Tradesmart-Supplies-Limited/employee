@php $m = \App\Services\AttendanceService::meta($status); @endphp
<span class="at-chip" style="--bg:{{ $m['bg'] }};--c:{{ $m['color'] }}"><i class="bi {{ $m['icon'] }}"></i> {{ $m['label'] }}</span>
