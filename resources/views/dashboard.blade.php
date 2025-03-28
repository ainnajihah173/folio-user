@extends('layouts.app')

@section('content')
<div class="container-fluid vh-100 d-flex flex-column px-0">
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-people-fill me-2"></i>FOLIO User Manager
            </a>
            <div class="d-flex align-items-center">
                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="flex-grow-1 overflow-auto bg-light">
        <div class="container-fluid h-100 px-4 py-4">
            <div class="row h-100 gx-4">
                <!-- Left Sidebar - Fixed Width -->
                <div class="col-lg-3 h-100 d-flex flex-column">
                    <div class="card shadow-sm border-0 rounded-3 h-100 d-flex flex-column">
                        <div class="card-header bg-primary text-white rounded-top-3">
                            <h5 class="mb-0"><i class="bi bi-upload me-2"></i>User Import</h5>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <form id="importForm" action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data" class="flex-grow-1 d-flex flex-column">
                                @csrf
                                <div class="mb-4">
                                    <label for="fileInput" class="form-label fw-semibold text-muted">Select File</label>
                                    <input type="file" id="fileInput" name="file" class="form-control rounded-3 shadow-sm" accept=".xlsx" required>
                                    <div class="form-text small text-muted mt-1">Supported: XLSX (Max 10MB)</div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-gradient w-100 mt-auto rounded-3">
                                    <i class="bi bi-cloud-upload me-2"></i> Process Import
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm border-0 rounded-3 mt-4 flex-grow-1">
                        <div class="card-header bg-info text-white rounded-top-3">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Quick Guide</h5>
                        </div>
                        <div class="card-body p-4 overflow-auto">
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary mb-2">File Format</h6>
                                <p class="small text-muted mb-2">Use the template to ensure correct formatting:</p>
                                <ul class="list-unstyled small text-muted">
                                    <li><strong>Required Fields:</strong> username, externalSystemId, patronGroup, lastName, firstName</li>
                                    <li><strong>Example:</strong> username: jhandey, externalSystemId: 111_112, patronGroup: staff</li>
                                    <li class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Header row must be included</li>
                                </ul>
                            </div>

                            <!-- Collapsible Key Field Guidelines -->
                            <h6 class="fw-bold text-primary mb-2">Key Field Guidelines</h6>
                            <div class="accordion" id="fieldGuidelines">
                                <div class="accordion-item border-0 rounded-3">
                                    <h2 class="accordion-header" id="headingFields">
                                        <button class="accordion-button collapsed bg-light text-primary fw-semibold rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFields" aria-expanded="false" aria-controls="collapseFields">
                                            View Field Details
                                        </button>
                                    </h2>
                                    <div id="collapseFields" class="accordion-collapse collapse" aria-labelledby="headingFields" data-bs-parent="#fieldGuidelines">
                                        <div class="accordion-body small text-muted">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">
                                                    <strong>patronGroup</strong>: Must be an existing group (e.g., faculty, staff). Contact admin to verify.
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>addressTypeId</strong>: Must be an existing type (e.g., Home). No duplicate types per user.
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>preferredContactTypeId</strong>: Use: mail, email, text, phone, mobile.
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>deactivateMissingUsers</strong>: True to deactivate users not in this import; false to keep active.
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>updateOnlyPresentFields</strong>: True to update only provided fields; false to update all.
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>sourceType</strong>: Prefix for externalSystemId (e.g., "test_somebody012").
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>requestPreference</strong>: holdShelf (true), delivery (true/false), fulfillment (Hold Shelf/Delivery).
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>departments</strong>: Must exist or be defined in the import (e.g., Accounting).
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('template.download') }}" class="btn btn-outline-primary btn-gradient w-100 mt-4 rounded-3">
                                <i class="bi bi-download me-2"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content - Flexible Width -->
                <div class="col-lg-9 h-100">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-success text-white rounded-top-3">
                            <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Import Results</h5>
                        </div>
                        <div class="card-body overflow-auto p-4">
                            @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show rounded-3">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @endif
                            
                            @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show rounded-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @endif
                            
                            @if(session('logs'))
                            <div class="mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary fw-bold"><i class="bi bi-list-check me-2"></i>Import Details</h6>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="copyLogs()">
                                        <i class="bi bi-clipboard me-1"></i> Copy Logs
                                    </button>
                                </div>
                                <div class="bg-dark text-white p-4 rounded-3">
                                    <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ json_encode(session('logs'), JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Modern Font Stack */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f8f9fa;
    }

    /* Gradient Backgrounds */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #0d6efd 100%);
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }

    /* Card Styling */
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1) !important;
    }
    .card-header {
        border-bottom: none;
    }
    .card-body {
        background-color: #fff;
    }

    /* Button Styling */
    .btn-gradient {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        transition: background 0.3s ease;
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #0056b3 0%, #003f7f 100%);
        color: white;
    }
    .btn-outline-primary {
        border-color: #007bff;
        color: #007bff;
        transition: all 0.3s ease;
    }
    .btn-outline-primary:hover {
        background: #007bff;
        color: white;
    }

    /* Form Inputs */
    .form-control {
        border: 1px solid #ced4da;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Accordion Styling */
    .accordion-button {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
    .accordion-button:not(.collapsed) {
        background-color: #e7f1ff;
        color: #0056b3;
    }
    .accordion-body {
        padding: 1rem;
    }

    /* Improved Table Responsiveness */
    .table-responsive {
        min-height: 0;
        overflow-x: auto;
    }

    /* Better Card Body Scrolling */
    .card-body.overflow-auto {
        max-height: calc(100vh - 200px);
    }

    /* Consistent Icon Alignment */
    .bi {
        vertical-align: middle;
    }

    /* Text Colors */
    .text-primary {
        color: #0056b3 !important;
    }
    .text-muted {
        color: #6c757d !important;
    }

    /* Alerts */
    .alert {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
</style>
@endsection

@section('scripts')
<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Please select a file first');
        return false;
    }
    
    // Show loading indicator
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Processing...';
    submitBtn.disabled = true;
});

function copyLogs() {
    const logsContent = document.querySelector('pre').textContent;
    navigator.clipboard.writeText(logsContent)
        .then(() => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
            alert.style.zIndex = '1100';
            alert.innerHTML = `
                <i class="bi bi-clipboard-check me-2"></i>
                Logs copied to clipboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        });
}
</script>
@endsection