<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - New Email Verification Batch</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #5a67d8;
            --secondary: #805ad5;
        }

        body {
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            margin-bottom: 1.8rem;
            border-bottom-left-radius: 1.5rem;
            border-bottom-right-radius: 1.5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card {
            border: none;
            border-radius: 0.8rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        textarea {
            font-family: monospace;
            resize: none;
            min-height: 45vh;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--secondary);
        }
    </style>
</head>
<body>

<div class="header">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="fs-4 m-0"><i class="fas fa-envelope-circle-check me-2"></i> ZeusMailer - New Email Verification Batch</h1>
    </div>
</div>

<div class="container pb-5">
    <div class="card">
        <div class="card-header bg-primary bg-gradient text-white fw-semibold">
            <i class="fas fa-database me-2"></i> Start a New Email Verification
        </div>

        <div class="card-body">
            <form id="createVerifyForm">
                <div class="mb-5">
                    <label class="form-label"><i class="fas fa-file-alt me-1"></i> Paste Emails (one per line)</label>
                    <textarea id="emails" name="emails" class="form-control lg-50"
                              placeholder="user1@example.com&#10;user2@yahoo.com&#10;user3@outlook.com"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-upload me-1"></i> Or Upload Email List (.txt)</label>
                    <input type="file" class="form-control" id="file" name="file" accept=".txt">
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="fas fa-play me-1"></i> Start Verifying
                    </button>
                </div>
            </form>

            <div id="statusArea" class="mt-4 text-center fw-semibold text-secondary" style="display:none;">
                <i class="fas fa-spinner fa-spin me-1"></i> Creating batch...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('createVerifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById('file');
    const textarea = document.getElementById('emails');
    const status = document.getElementById('statusArea');

    status.style.display = 'block';
    status.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creating batch...';

    let emails = [];

    // ✅ File upload
    if (fileInput.files.length > 0) {
        try {
            const file = fileInput.files[0];
            const text = await file.text();
            emails = text.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);
        } catch (err) {
            console.error('File read error:', err);
            status.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i> Failed to read file.</span>';
            return;
        }
    } else {
        // ✅ Manual paste
        const text = textarea.value.trim();
        emails = text.split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);
    }

    if (emails.length === 0) {
        status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> No valid emails found.</span>';
        return;
    }

    const formData = new FormData();
    emails.forEach(email => formData.append('emails[]', email));
    if (fileInput.files.length > 0)
        formData.append('file', fileInput.files[0]);

    try {
        const res = await fetch('{{ route('verifier.start') }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector("meta[name='csrf-token']").content},
            body: formData
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        if (data.error) {
            status.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle me-1"></i> ${data.error}</span>`;
            return;
        }

        status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Batch created successfully!</span>';
        setTimeout(() => window.location.href = `/verifier/batch/${data.batch_id}`, 1000);
    } catch (err) {
        console.error('Batch creation failed:', err);
        status.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Failed to create batch.</span>';
    }
});
</script>
</body>
</html>
