@extends('emails.layouts.brand')

@section('title', 'Your details')
@section('tag', 'Employee portal')
@section('preheader', $pending ? 'HR is reviewing your requested change.' : 'Your details were updated.')
@section('heading', $pending ? 'We received your request ✓' : 'Your details were updated ✓')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ $employee->first_name }},</p>
    @if($pending)
        @include('emails.portal.partials.change-tracker', ['stage' => 'review'])
        <p style="margin:0 0 14px;">Some of the details you changed are checked by HR first, so they stay as they were until HR approves them. You'll get another email with the decision. HR may ask you for a supporting document. You can upload it under <strong>My Documents</strong>.</p>
        @include('emails.portal.partials.change-table', ['rows' => $pending, 'title' => 'Waiting for HR approval', 'color' => '#d97706'])
    @else
        <p style="margin:0 0 14px;">These changes are now on your record. HR has been notified.</p>
    @endif
    @if($applied)@include('emails.portal.partials.change-table', ['rows' => $applied, 'title' => 'Updated now', 'color' => '#16a34a'])@endif

    @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'View my information'])
@endsection

@section('footnote')
    Didn't make this change? Contact HR straight away and change your portal password.
@endsection
