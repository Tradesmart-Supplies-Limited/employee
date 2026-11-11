@extends('emails.layouts.brand')

@php use App\Support\EmployeeFields as F; @endphp

@section('title', $ok ? 'Change approved' : 'Change reverted')
@section('tag', 'Self-service')
@section('preheader', $ok ? 'Your updated details are now on your record.' : 'HR has reverted your requested change for now, with a note.')

@section('heading', $ok ? 'Your updated details are approved ✓' : 'An update on your requested change')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ $employee->first_name }},</p>
    @include('emails.portal.partials.change-tracker', ['stage' => $ok ? 'approved' : 'rejected'])
    <p style="margin:0 0 14px;">
        @if($ok)
            HR has approved the change to your <strong>{{ collect(array_keys($change->changes))->map(fn ($f) => F::label($f))->implode(', ') }}</strong>. Your record now shows the new details.
        @else
            Thank you for updating your details. HR has reverted the change to your <strong>{{ collect(array_keys($change->changes))->map(fn ($f) => F::label($f))->implode(', ') }}</strong> for now, so your record is unchanged. The note below explains why; you're welcome to submit it again with the suggested correction.
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
    If you have any questions, please reply to HR or speak to your supervisor. Thank you.
@endsection
