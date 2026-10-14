@extends('emails.layouts.brand')

@section('title', 'Rota published')
@section('tag', 'Shift rota')
@section('preheader', $rota->title)
@section('heading', ($first ? 'Rota published: ' : 'Rota updated: ') . $rota->department)

@section('body')
    <p style="margin:0 0 12px;"><strong>{{ $by }}</strong> approved and published the {{ $rota->department }} rota for <strong>{{ $rota->week_start->format('l d M') }} – {{ $rota->week_end->format('l d M Y') }}</strong>{{ $first ? '' : ' (version ' . $rota->version . ')' }}.</p>
    <p style="margin:0 0 12px;">{{ $first ? $people . ' employee(s) are on it' : $people . ' employee(s) had changes' }}; {{ $emailed }} were emailed their shifts (the others have no email address on file).</p>
    <p style="margin:0 0 12px;">Attendance now expects these people at their rostered times. Sunday and public-holiday shifts are recorded as pre-approved overtime when worked.</p>
    @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'View / print the rota'])
@endsection
