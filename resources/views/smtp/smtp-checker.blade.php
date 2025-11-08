<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - SMTP Checker</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
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

        /* HEADER */
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

        #combo_input {
            font-family: monospace;
            min-height: 50vh;
            resize: none;
        }

        #results_table {
            height: 60vh; /* increased height for wider viewer */
            overflow-y: auto;
        }

        .form-label {
            font-weight: 600;
        }

        table th {
            background: var(--primary);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .progress {
            height: 20px;
        }

        .success-row {
            background: #d1fae5;
        }

        .fail-row {
            background: #fee2e2;
        }

        .status-pill {
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.82rem;
        }

        @media (max-width: 992px) {
            #combo_input, #results_table {
                min-height: 40vh;
                height: 40vh;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="fs-4 m-0"><i class="fas fa-server me-2"></i> ZeusMailer - SMTP Checker</h1>
            <a href="{{ url('/') }}" class="btn btn-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="container pb-5">
        <div class="card">
            <div class="card-header bg-primary bg-gradient text-white fw-semibold">
                <i class="fas fa-network-wired me-2"></i> SMTP Checker Tool
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <!-- INPUT AREA (narrower) -->
                    <div class="col-lg-5 col-md-12">
                        <label class="form-label mb-2">
                            <i class="fas fa-file-alt me-1"></i> Combo Input
                        </label>
                        <textarea id="combo_input" class="form-control"
                            placeholder="email@example.com:password"></textarea>

                        <div class="mt-3 d-flex align-items-center">
                            <input type="file" id="combo_file" class="form-control me-2" accept=".txt" style="max-width: 60%;">
                            <button id="startBtn" class="btn btn-success fw-semibold">
                                <i class="fas fa-play me-1"></i> Start Checking
                            </button>
                        </div>

                        <div class="progress mt-3">
                            <div id="progressBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%;">0%</div>
                        </div>

                        <div class="mt-3 small text-muted">
                            <strong>Note:</strong> The checker enqueues all combos and runs in background workers. Keep this tab open for live updates or leave and come back using the batch id.
                        </div>
                    </div>

                    <!-- RESULTS TABLE (wider) -->
                    <div class="col-lg-7 col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">
                                <i class="fas fa-list me-1"></i> Live Results
                            </label>

                            <div>
                                <button id="exportBtn" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-download me-1"></i> Export Successes
                                </button>
                                <button id="stopPollBtn" class="btn btn-outline-secondary btn-sm" style="display:none;">
                                    <i class="fas fa-stop me-1"></i> Stop Live
                                </button>
                            </div>
                        </div>

                        <div id="results_table" class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 24%;">Email</th>
                                        <th style="width: 28%;">SMTP Host</th>
                                        <th style="width: 18%;">Password</th>
                                        <th style="width: 8%;">Port</th>
                                        <th style="width: 12%;">Status</th>
                                        <th style="width: 10%;">Time (s)</th>
                                    </tr>
                                </thead>
                                <tbody id="resultsBody">
                                    <tr><td colspan="6" class="text-center text-muted">Waiting to start...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-2 small text-muted">
                            Batch ID: <span id="currentBatch" class="fw-semibold text-dark">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Elements
        const startBtn = document.getElementById('startBtn');
        const comboInput = document.getElementById('combo_input');
        const comboFile = document.getElementById('combo_file');
        const resultsBody = document.getElementById('resultsBody');
        const progressBar = document.getElementById('progressBar');
        const exportBtn = document.getElementById('exportBtn');
        const currentBatchSpan = document.getElementById('currentBatch');
        const stopPollBtn = document.getElementById('stopPollBtn');

        // State
        let batchId = null;
        let totalCount = 0;
        let pollInterval = null;
        let seen = {}; // map email -> true (to avoid duplicate rows)

        // Load combos from file into textarea
        comboFile.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const text = await file.text();
            comboInput.value = text.trim();
        });

        // Start: submit combos to backend (multipart/form-data)
        startBtn.addEventListener('click', async () => {
            const combosText = comboInput.value.trim();
            const file = comboFile.files[0];

            if (!file && !combosText) {
                alert('Please provide combos via file or paste them in the textarea.');
                return;
            }

            startBtn.disabled = true;
            startBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enqueuing...';

            const form = new FormData();
            if (file) form.append('file', file);
            if (combosText) form.append('combos', combosText);

            try {
                const res = await fetch('{{ route('smtp.checker.store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: form
                });

                if (!res.ok) {
                    const err = await res.json().catch(()=>({error: 'Server error'}));
                    throw new Error(err.error || 'Failed to enqueue');
                }

                const data = await res.json();
                batchId = data.batch_id;
                totalCount = data.total ?? 0;
                currentBatchSpan.textContent = batchId;
                resultsBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Enqueued — waiting for workers...</td></tr>';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                seen = {};

                // start polling status
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(() => pollStatus(batchId), 1000);
                stopPollBtn.style.display = 'inline-block';

            } catch (err) {
                alert('Error: ' + (err.message || 'Failed to enqueue'));
                console.error(err);
            } finally {
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="fas fa-play me-1"></i> Start Checking';
            }
        });

        // Stop live polling
        stopPollBtn.addEventListener('click', () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = null;
            stopPollBtn.style.display = 'none';
        });

        // Poll status and update UI
        async function pollStatus(id) {
            if (!id) return;
            try {
                const res = await fetch(`{{ url('/smtp-checker') }}/${id}/status`);
                if (res.status === 404) {
                    // stop polling if not found
                    if (pollInterval) clearInterval(pollInterval);
                    pollInterval = null;
                    stopPollBtn.style.display = 'none';
                    progressBar.className = 'progress-bar bg-danger';
                    progressBar.style.width = '100%';
                    progressBar.textContent = 'Batch Not Found';
                    return;
                }
                const data = await res.json();
                const processed = data.processed ?? 0;
                const success = data.success ?? 0;
                const total = data.total ?? totalCount ?? 0;
                const recent = data.recent_results ?? [];

                // update progress bar
                const percent = total === 0 ? 0 : Math.round((processed / total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                setProgressColor(percent);

                // update table using recent_results (most recent last)
                // keep a map 'seen' to avoid re-adding the same row
                if (recent.length > 0) {
                    // we'll render the recent list in chronological order
                    const rows = [];
                    for (const r of recent) {
                        if (seen[r.email]) continue;
                        seen[r.email] = true;

                        const tr = document.createElement('tr');
                        tr.classList.add((r.status === 'success') ? 'success-row' : 'fail-row');

                        const checkedAt = r.ts ?? new Date().toLocaleString();

                        tr.innerHTML = `
                            <td>${escapeHtml(r.email ?? '')}</td>
                            <td>${escapeHtml(r.smtp_host ?? '-')}</td>
                            <td>${escapeHtml(r.password ?? '-')}</td>
                            <td>${r.port ?? '-'}</td>
                            <td>
                                <span class="status-pill ${r.status === 'success' ? 'text-success' : 'text-danger'}">
                                    ${String(r.status).toUpperCase()}
                                </span>
                            </td>
                            <td>${r.response_time ?? '-'}</td>
                        `;
                        rows.push(tr);
                    }

                    // append new rows to top (most recent first)
                    if (rows.length) {
                        // insert new rows at top below header
                        resultsBody.querySelectorAll('tr').forEach(n => {
                            // if the "waiting" row exists and there are real rows, clear it
                            if (n.querySelector('td') && n.querySelector('td').colSpan == 6 && rows.length) {
                                resultsBody.innerHTML = '';
                            }
                        });
                        for (const row of rows.reverse()) { // reverse so most recent at top
                            resultsBody.prepend(row);
                        }
                    }
                }

                // if done, stop polling
                if (total > 0 && processed >= total) {
                    if (pollInterval) clearInterval(pollInterval);
                    pollInterval = null;
                    stopPollBtn.style.display = 'none';
                    // final color
                    setProgressColor(percent);
                }
            } catch (err) {
                console.error('poll status error', err);
            }
        }

        // set progress bar color: green (0-50), orange (51-80), red (81-100)
        function setProgressColor(percent) {
            progressBar.className = 'progress-bar';
            if (percent <= 50) {
                progressBar.classList.add('bg-success');
            } else if (percent <= 80) {
                progressBar.classList.add('bg-warning');
                progressBar.style.color = '#222';
            } else {
                progressBar.classList.add('bg-danger');
            }
        }

        // Export successes from latest known recent_results by calling status once
        exportBtn.addEventListener('click', async () => {
            if (!batchId) return alert('No batch running. Start a job first.');

            try {
                const res = await fetch(`{{ url('/smtp-checker') }}/${batchId}/status`);
                if (!res.ok) throw new Error('Failed to fetch batch status');
                const data = await res.json();
                const recent = data.recent_results ?? [];
                const successes = recent.filter(r => r.status === 'success');

                if (successes.length === 0) {
                    return alert('No successful SMTPs found yet.');
                }

                const lines = successes.map(s => `${s.email}|${s.smtp_host}|${s.port}|${s.password}`);
                const blob = new Blob([lines.join('\n')], { type: 'text/plain' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `smtp_successes_${batchId}.txt`;
                link.click();
            } catch (err) {
                alert('Export failed: ' + (err.message || 'Unknown'));
                console.error(err);
            }
        });

        // simple HTML escape for safety
        function escapeHtml(text) {
            return String(text || '').replace(/[&<>"'`]/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;'
            }[c]));
        }
    </script>
</body>
</html>
