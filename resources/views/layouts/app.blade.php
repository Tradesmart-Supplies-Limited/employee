<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body
  data-user-name="{{ auth()->user()->name }}"
  data-user-avatar="{{ asset('assets/images/avatar/avatar.jpg') }}">

  <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<div class="admin-shell">

  

    <div class="sidebar-backdrop" data-sidebar-close></div>

    @include('partials.sidebar')

    <div class="admin-main">

        @include('partials.navbar')

        <main class="dashboard-content page-transition" id="page-content">

    
            <div class="container-fluid px-3 px-lg-4 py-4">

                @include('partials.alerts')

                @yield('content')

            </div>

        </main>

        @include('partials.footer')

    </div>

</div>

@include('partials.scripts')

<script>
    // Show loader manually
    function showLoader() {
        document.getElementById('global-loader').classList.remove('d-none');
    }

    // Hide loader
    function hideLoader() {
        document.getElementById('global-loader').classList.add('d-none');
    }

    // Show loader on any form submit
    document.addEventListener('submit', function () {
        showLoader();
    });

    // Show loader on page navigation clicks
    // document.addEventListener('click', function (e) {
    //     const link = e.target.closest('a');
    //     if (link && link.href && !link.target) {
    //         showLoader();
    //     }
    // });

    

    document.addEventListener("click", function (e) {
    const link = e.target.closest("a");

    if (link && link.href && !link.target && !link.hasAttribute("download")) {
        e.preventDefault();

        document.getElementById("page-content").style.opacity = "0";
        document.getElementById("page-content").style.transform = "translateY(6px)";

        setTimeout(() => {
            window.location = link.href;
        }, 150);
    }
});

function showToast(message, type = 'success') {

    const colors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-primary'
    };

    const toast = document.createElement('div');

    toast.className = `toast align-items-center text-white ${colors[type]} border-0 show mb-2`;
    toast.role = 'alert';

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;

    document.getElementById('toast-container').appendChild(toast);

    // auto remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}



</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const selector = document.getElementById('employeeSelector');
    const container = document.getElementById('employeePayrollSummary');

    selector.addEventListener('change', function () {

        const payrollId = this.value;

        if (!payrollId) return;

        const url = `/payroll/runs/${runId}/employee/${payrollId}/summary`;

        container.innerHTML = `
            <div class="text-center py-3">
                Loading payroll...
            </div>
        `;

        fetch(url)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(() => {
                container.innerHTML = `
                    <div class="text-danger text-center">
                        Failed to load payroll
                    </div>
                `;
            });

    });

});
</script>






<div id="global-loader" class="global-loader d-none">
    <div class="loader-box">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 mb-0">Loading...</p>
    </div>
</div>


<div class="modal fade" id="importEmployeesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                    Import Employees
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body">

                <p class="text-muted small mb-3">
                    Upload a CSV file to bulk create employees. Make sure it follows the required format.
                </p>

                {{-- DOWNLOAD SAMPLE --}}
                <a href="{{ route('employees.sample-csv') }}"
                   class="btn btn-sm btn-outline-primary mb-3 w-100">
                    <i class="bi bi-download"></i>
                    Download Sample CSV
                </a>

                {{-- UPLOAD FORM --}}
                <form method="POST"
                      action="{{ route('employees.import') }}"
                      enctype="multipart/form-data">

                    @csrf

                    <label class="form-label">Select CSV File</label>

                    <input type="file"
                           name="file"
                           accept=".csv"
                           class="form-control mb-3"
                           required>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-cloud-upload"></i>
                        Upload & Import
                    </button>

                </form>

            </div>

        </div>

    </div>
</div>

@stack('modals')



</body>
</html>