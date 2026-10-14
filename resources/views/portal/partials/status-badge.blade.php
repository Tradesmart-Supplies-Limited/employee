@php
    $cls = match ($status) {
        'Approved' => 'bg-success',
        'Rejected' => 'bg-danger',
        'Paid'     => 'bg-success',
        default    => 'bg-warning text-dark',
    };
    $icon = match ($status) {
        'Approved', 'Paid' => 'bi-check-circle',
        'Rejected'         => 'bi-x-circle',
        default            => 'bi-hourglass-split',
    };
@endphp
<span class="badge {{ $cls }}"><i class="bi {{ $icon }} me-1"></i>{{ $status }}</span>
