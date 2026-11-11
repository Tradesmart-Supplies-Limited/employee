{{-- Round profile photo, or initials. Vars: $user, $size (px) --}}
@php $sz = $size ?? 36; @endphp
@if($user?->photo_url)
    <img src="{{ $user->photo_url }}" alt="" class="rounded-circle flex-shrink-0" style="width:{{ $sz }}px;height:{{ $sz }}px;object-fit:cover;">
@else
    <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold flex-shrink-0"
          style="width:{{ $sz }}px;height:{{ $sz }}px;background:linear-gradient(135deg,var(--brand,#185FA5),#4f8cff);color:#fff;font-size:{{ max(11, round($sz * .34)) }}px;">{{ $user?->initials }}</span>
@endif
