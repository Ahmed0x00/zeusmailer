<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - SMTP Tester</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #5a67d8;
            --secondary: #805ad5;
            --success: #38a169;
            --danger: #e53e3e;
        }

        body {
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2.5rem;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        pre {
            white-space: pre-wrap;
            background: #f9fafb;
            border-radius: 0.5rem;
        }

        .badge {
            font-size: 0.85rem;
        }

        #html_body {
            font-family: monospace;
        }

        #downloadSuccessBtn {
            display: none;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="fs-3"><i class="fas fa-vial me-2"></i> ZeusMailer - SMTP Tester</h1>
            <a href="{{ route('campaign.index') }}" class="btn btn-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to Campaigns
            </a>
        </div>
    </div>

    <div class="container pb-5">
        <div class="card">
            <div class="card-header bg-primary bg-gradient text-white fw-semibold">
                <i class="fas fa-envelope me-2"></i> SMTP Test Tool
            </div>

            <div class="card-body">
                <form id="smtpTestForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-at me-1"></i> Recipient Email</label>
                        <input type="email" id="test_email" name="test_email" class="form-control" placeholder="recipient@example.com" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-user me-1"></i> From Name</label>
                            <input type="text" id="from_name" name="from_name" class="form-control" placeholder="e.g. ZeusMailer" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-heading me-1"></i> Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. SMTP Test Delivery" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-file-code me-1"></i> HTML Body</label>
                        <textarea id="html_body" name="html_body" rows="6" class="form-control" placeholder="<h1>SMTP Test</h1><p>This is a test email from ZeusMailer.</p>"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-server me-1"></i> SMTP Accounts</label>
                        <textarea id="smtp_input" name="smtp_input" rows="6" class="form-control"
                            placeholder="host|port|username|password|from@example.com" required></textarea>
                    </div>

                    <button type="submit" id="runTestBtn" class="btn btn-primary w-100 fw-semibold">
                        <i class="fas fa-rocket me-2"></i> Run SMTP Test
                    </button>
                </form>

                <div id="resultsContainer" class="mt-5 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold"><i class="fas fa-list-check me-2"></i> Live Results</h5>
                        <button id="downloadSuccessBtn" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i> Download Successful SMTPs
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="resultsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Host</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Debug Log</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="statusModalLabel"><i class="fas fa-info-circle me-2"></i>Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center fs-5" id="statusModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Log Modal -->
    <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="logModalLabel"><i class="fas fa-terminal me-2"></i>Debug Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="logContent" class="p-3"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let batchId = null;
        let polling = null;
        let running = false;
        let successful = [];
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        const logModal = new bootstrap.Modal(document.getElementById('logModal'));

        const statusModalBody = document.getElementById('statusModalBody');
        const logContent = document.getElementById('logContent');
        const downloadBtn = document.getElementById('downloadSuccessBtn');

        document.getElementById('smtpTestForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const smtp_input = document.getElementById('smtp_input').value;
            const test_email = document.getElementById('test_email').value;
            const from_name = document.getElementById('from_name').value;
            const subject = document.getElementById('subject').value;
            const html_body = document.getElementById('html_body').value;

            const res = await fetch('/smtp/start/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ smtp_input, test_email, from_name, subject, html_body })
            });

            const data = await res.json();
            if (!data.batch_id) {
                showStatus('❌ Error starting SMTP test.');
                return;
            }

            batchId = data.batch_id;
            document.getElementById('resultsContainer').classList.remove('d-none');
            document.querySelector('#resultsTable tbody').innerHTML = '';
            running = true;
            successful = [];
            downloadBtn.style.display = 'none';

            polling = setInterval(pollResults, 2000);
            runLoop(test_email, from_name, subject, html_body);
        });

        async function pollResults() {
            if (!batchId) return;
            const res = await fetch(`/smtp/poll/${batchId}`);
            const data = await res.json();

            const tbody = document.querySelector('#resultsTable tbody');
            tbody.innerHTML = '';

            data.results.forEach(r => {
                if (r.status === 'success' && !successful.includes(r)) {
                    successful.push(r);
                }

                tbody.innerHTML += `
                    <tr class="${r.status === 'success' ? 'table-success' : (r.status === 'failed' ? 'table-danger' : '')}">
                        <td>${r.host}</td>
                        <td>${r.username}</td>
                        <td>
                            <span class="badge bg-${r.status === 'success' ? 'success' : (r.status === 'failed' ? 'danger' : 'warning text-dark')}">
                                ${r.status.toUpperCase()}
                            </span>
                        </td>
                        <td>
                            ${r.debug ? `<button class="btn btn-sm btn-outline-secondary" onclick="showLog(\`${escapeHtml(r.debug)}\`)">
                                View Log
                            </button>` : ''}
                        </td>
                    </tr>
                `;
            });
        }

        async function runLoop(test_email, from_name, subject, html_body) {
            while (running) {
                const res = await fetch('/smtp/run-next', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ batch_id: batchId, test_email, from_name, subject, html_body })
                });

                const data = await res.json();
                if (data.status === 'done') {
                    running = false;
                    clearInterval(polling);
                    pollResults();
                    showStatus('✅ All SMTP tests completed successfully!');
                    if (successful.length > 0) downloadBtn.style.display = 'inline-block';
                }
            }
        }

        function showStatus(message) {
            statusModalBody.textContent = message;
            statusModal.show();
        }

        function showLog(logText) {
            logContent.textContent = unescapeHtml(logText);
            logModal.show();
        }

        downloadBtn.addEventListener('click', function() {
            if (successful.length === 0) return;
            const lines = successful.map(r => `${r.host}|${r.username}|${r.status}`);
            const blob = new Blob([lines.join('\n')], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'successful_smtps.txt';
            link.click();
        });

        function escapeHtml(text) {
            return text.replace(/[&<>'"]/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
            }[c]));
        }

        function unescapeHtml(text) {
            return text.replace(/&amp;/g, '&').replace(/&lt;/g, '<')
                       .replace(/&gt;/g, '>').replace(/&#39;/g, "'").replace(/&quot;/g, '"');
        }
    </script>
</body>
</html>
