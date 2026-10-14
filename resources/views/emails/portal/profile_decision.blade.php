@extends('emails.layouts.brand')

@php use App\Support\EmployeeFields as F; @endphp

@section('title', $ok ? 'Change approved' : 'Change not approved')
@section('tag', 'Self-service')
@section('preheader', $ok ? 'Your updated details are now on your record.' : 'HR did not approve your requested change.')

@section('heading', $ok ? 'Your updated details are approved ✓' : 'Your requested change wasn\'t approved')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ $employee->first_name }},</p>
    @include('emails.portal.partials.change-tracker', ['stage' => $ok ? 'approved' : 'rejected'])
    <p style="margin:0 0 14px;">
        @if($ok)
            HR has approved the change to your <strong>{{ collect(array_keys($change->changes))->map(fn ($f) => F::label($f))->implode(', ') }}</strong>. Your record now shows the new details.
        @else
            HR did not approve the change to your <strong>{{ collect(array_keys($change->changes))->map(fn ($f) => F::label($f))->implode(', ') }}</strong>. Your record is unchanged.
        @endif
    </p>
    @if($change->review_note)
        <div style="border-left:4px solid {{ $ok ? '#16a34a' : '#dc2626' }}; background:#f8fafc; padding:10px 14px; border-radius:6px; font-size:13px;">
            <strong>HR's note:</strong> {{ $change->review_note }}
        </div>
    @endif
    @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'View my information'])
@endsection

@section('footnote')
    Questions? Reply to HR or speak to your supervisor.
@endsection
