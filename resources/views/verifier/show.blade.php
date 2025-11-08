<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - Email Verifier Live</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #38bdf8;
            --secondary: #0ea5e9;
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
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .card {
            border: none;
            border-radius: 0.8rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        #results_table {
            height: 65vh;
            overflow-y: auto;
        }
        table th {
            background: var(--primary);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .valid-row { background: #d1fae5; }
        .invalid-row { background: #fee2e2; }
        .error-row { background: #fef3c7; }
        .status-pill {
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.82rem;
        }
        .progress { height: 20px; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="fs-4 m-0"><i class="fas fa-envelope-circle-check me-2"></i> ZeusMailer - Email Verifier</h1>
        <div>
            <a href="{{ url('/verifier') }}" class="btn btn-light fw-semibold me-2">
                <i class="fas fa-plus me-1"></i> New Batch
            </a>
        </div>
    </div>
</div>

<!-- CONTENT -->
<div class="container pb-5">
    <div class="card">
        <div class="card-header bg-info bg-gradient text-white fw-semibold">
            <i class="fas fa-circle-nodes me-2"></i> Live Results for Batch: <code>{{ $batch->id }}</code>
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="w-100 me-3">
                    <div class="progress">
                        <div id="progressBar" class="progress-bar bg-info" style="width: 0%">0%</div>
                    </div>
                </div>

                <div class="btn-group">
                    <button id="downloadBtn" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Export Valid
                    </button>
                </div>
            </div>

            <div id="results_table" class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Checked At</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <tr><td colspan="4" class="text-center text-muted">Loading results...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3 small text-muted">
                Valid: <span id="validCount" class="fw-semibold text-success">0</span> /
                Invalid: <span id="invalidCount" class="fw-semibold text-danger">0</span> /
                Processed: <span id="processedCount" class="fw-semibold text-dark">0</span> /
                Total: <span id="totalCount" class="fw-semibold text-dark">{{ $batch->total }}</span>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const batchId = "{{ $batch->id }}";
const progressBar = document.getElementById('progressBar');
const resultsBody = document.getElementById('resultsBody');
const stopBtn = document.getElementById('stopBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resumeBtn = document.getElementById('resumeBtn');
const downloadBtn = document.getElementById('downloadBtn');

let pollInterval = null;
let seen = {};
let allResults = [];

function setProgressColor(percent) {
    progressBar.className = 'progress-bar';
    if (percent < 50) progressBar.classList.add('bg-success');
    else if (percent < 80) progressBar.classList.add('bg-warning');
    else progressBar.classList.add('bg-danger');
}

async function poll() {
    try {
        const res = await fetch(`/verifier/batch/${batchId}/status`);
        if (!res.ok) return;
        const data = await res.json();

        const processed = data.processed ?? 0;
        const valid = data.valid ?? 0;
        const invalid = data.invalid ?? 0;
        const total = data.total ?? 0;
        const status = data.status ?? 'running';
        const percent = total === 0 ? 0 : Math.round((processed / total) * 100);

        progressBar.style.width = percent + '%';
        progressBar.textContent = percent + '%';
        setProgressColor(percent);

        document.getElementById('processedCount').textContent = processed;
        document.getElementById('validCount').textContent = valid;
        document.getElementById('invalidCount').textContent = invalid;
        document.getElementById('totalCount').textContent = total;

        // Load recent results (if API returns them in the future)
        const recent = data.recent_results ?? [];
        if (recent.length) {
            const rows = [];
            for (const r of recent) {
                if (seen[r.email]) continue;
                seen[r.email] = true;
                allResults.push(r);

                const tr = document.createElement('tr');
                if (r.status === 'valid') tr.classList.add('valid-row');
                else if (r.status === 'invalid') tr.classList.add('invalid-row');
                else tr.classList.add('error-row');

                tr.innerHTML = `
                    <td>${r.email ?? '-'}</td>
                    <td><span class="status-pill ${r.status === 'valid' ? 'text-success' : (r.status === 'invalid' ? 'text-danger' : 'text-warning')}">${r.status?.toUpperCase() ?? '-'}</span></td>
                    <td>${r.reason ?? '-'}</td>
                    <td>${r.checked_at ?? '-'}</td>
                `;
                rows.push(tr);
            }

            if (rows.length) {
                if (resultsBody.innerHTML.includes('Loading')) resultsBody.innerHTML = '';
                for (const row of rows.reverse()) resultsBody.prepend(row);
            }
        }

        if (status === 'completed' || processed >= total) {
            clearInterval(pollInterval);
            pollInterval = null;
            stopBtn.disabled = true;
            pauseBtn.disabled = true;
            resumeBtn.disabled = true;
        }

    } catch (err) {
        console.error('poll error', err);
    }
}

stopBtn.addEventListener('click', () => {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = null;
    stopBtn.disabled = true;
});

downloadBtn.addEventListener('click', () => {
    const validEmails = allResults.filter(r => r.status === 'valid');
    if (!validEmails.length) {
        alert('No valid emails to export yet.');
        return;
    }
    const content = validEmails.map(r => r.email).join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = `valid_emails_${batchId}.txt`;
    a.click();
    URL.revokeObjectURL(url);
});

pollInterval = setInterval(poll, 1500);
poll();
</script>
</body>
</html>
