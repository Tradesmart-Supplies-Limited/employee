@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <h4 class="mb-4">Dashboard</h4>

    <div class="row">

        <div class="col-md-4">
            <div class="card p-3">
                <h6>Users</h6>
                <h3>{{ $stats['employees'] }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h6>Companies</h6>
                <h3>{{ $stats['branches'] }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h6>Requests</h6>
                <h3>{{ $stats['departments'] }}</h3>
            </div>
        </div>

    </div>

</div>
@endsection