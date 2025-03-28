<form action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="file" class="form-label">Select Excel File</label>
        <input class="form-control" type="file" id="file" name="file" accept=".xlsx,.xls" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-upload me-2"></i>Upload & Process
    </button>
</form>