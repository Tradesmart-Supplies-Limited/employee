@extends('layouts.app')

@section('content')

<div class="panel">

    <div class="panel-header d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-0">Payroll Runs</h4>
            <small class="text-muted">Monthly payroll processing cycles</small>
        </div>

        <a href="{{ route('payroll.runs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Run
        </a>

    </div>

    <div class="row mt-3">

        @foreach($runs as $run)

        @php
            $statusColor = match(strtolower($run->status)) {
                'draft' => 'secondary',
                'processing' => 'warning',
                'processed' => 'info',
                'paid' => 'success',
                default => 'dark'
            };
        @endphp

        <div class="col-md-4 mb-4">

            <div class="card run-card border-0 shadow-sm h-100 cursor-pointer"
                 onclick="window.location='{{ route('payroll.runs.show', $run->id) }}'">

                {{-- HEADER --}}
                <div class="card-header bg-white border-0">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <h5 class="mb-1">
                                <i class="bi bi-calendar3 text-primary me-1"></i>
                                {{ $run->alias }}
                            </h5>

                            <small class="text-muted d-block">
                                {{ $run->period ?? 'No alias set' }}
                            </small>

                            <small class="text-muted">
                                ID: #{{ $run->id }}
                            </small>
                        </div>

                        <span class="badge bg-{{ $statusColor }}">
                            {{ ucfirst($run->status) }}
                        </span>

                    </div>

                </div>

                {{-- FINANCIAL STATS --}}
                <div class="card-body">

                    <div class="row g-2">

                        <div class="col-12">

                            <div class="p-2 bg-light rounded">
                                <small class="text-muted">Total Income</small>
                                <div class="fw-bold text-primary">
                                    K {{ number_format($run->total_income,2) }}
                                </div>
                            </div>

                        </div>

                        <div class="col-12">

                            <div class="p-2 bg-light rounded">
                                <small class="text-muted">Total Deductions</small>
                                <div class="fw-bold text-danger">
                                    K {{ number_format($run->total_deductions,2) }}
                                </div>
                            </div>

                        </div>

                        <div class="col-12">

                            <div class="p-3 rounded bg-success bg-opacity-10">
                                <small class="text-muted">Net Pay</small>
                                <div class="fw-bold text-success fs-5">
                                    K {{ number_format($run->net_pay,2) }}
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                {{-- AUDIT TRAIL --}}
                <div class="px-3 pb-2">

                    <small class="text-muted d-block">
                        <i class="bi bi-person-plus me-1"></i>
                        Created by:
                        <strong>{{ $run->createdBy?->name ?? 'System' }}</strong>
                    </small>

                    <small class="text-muted d-block">
                        <i class="bi bi-pencil-square me-1"></i>
                        Last updated by:
                        <strong>{{ $run->updatedBy?->name ?? '—' }}</strong>
                        @if($run->updated_at)
                            • {{ $run->updated_at->format('d M Y H:i') }}
                        @endif
                    </small>

                    <small class="text-muted d-block">
                        <i class="bi bi-check2-circle me-1"></i>
                        Audited by:
                        <strong>{{ $run->auditedBy?->name ?? 'Not audited' }}</strong>
                    </small>

                    <small class="text-muted d-block mt-2">
                        @if($run->finalized_at)
                            <i class="bi bi-lock-fill text-danger"></i>
                            Finalized by {{ $run->finalizedBy?->name }}
                        @else
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            Draft run
                        @endif
                    </small>

                </div>

                {{-- ACTION BAR --}}
                <div class="card-footer bg-light border-0">

                    <div class="action-bar">

                        <a href="{{ route('payroll.runs.show', $run->id) }}" title="Open">
                            <i class="bi bi-folder2-open"></i>
                        </a>

                        <a href="{{ route('payroll.runs.generate', $run->id) }}" title="Generate">
                            <i class="bi bi-gear-wide-connected"></i>
                        </a>

                        <a href="#" title="Reports">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </a>

                        <a href="#" title="Backup">
                            <i class="bi bi-cloud-arrow-down"></i>
                        </a>

                        <a href="#" title="Share">
                            <i class="bi bi-share"></i>
                        </a>

                        <a href="#" class="text-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </a>

                    </div>

                </div>

                

            </div>

        </div>

        @endforeach

    </div>

</div>

@endsection