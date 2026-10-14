@if(session('status'))
    <div class="alert alert-info small py-2 d-flex gap-2 align-items-start">
        <i class="bi bi-envelope-check mt-1"></i><span>{{ session('status') }}</span>
    </div>
@endif
@if(session('success'))
    <div class="alert alert-success small py-2 d-flex gap-2 align-items-start">
        <i class="bi bi-check-circle mt-1"></i><span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger small py-2 d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-triangle mt-1"></i><span>{{ session('error') }}</span>
    </div>
@endif
