<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign #{{ $campaign->id }} - {{ $campaign->subject }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(120deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-success,
        .btn-warning,
        .btn-danger,
        .btn-info {
            border: none;
        }

        .progress {
            height: 36px;
            border-radius: 18px;
            background: #e9ecef;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: width 0.6s ease;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .log-line {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .log-line.success {
            color: #28a745;
        }

        .log-line.failed {
            color: #dc3545;
        }

        .log-line.info {
            color: #6c757d;
        }

        #simple-log {
            background: #f8f9fa;
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }

        .emoji {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        .debug-btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0 text-white">
                        Campaign #{{ $campaign->id }}
                    </h3>
                    <small class="text-white-50">{{ $campaign->subject }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('campaign.index') }}" class="btn btn-light btn-sm">
                        Back
                    </a>

                    @if($campaign->status !== 'completed')
                        <form action="{{ route('campaign.toggle', $campaign->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit"
                                class="btn btn-sm {{ $campaign->status === 'running' ? 'btn-warning' : 'btn-success' }}">
                                @if($campaign->status === 'running') Pause @else Resume @endif
                            </button>
                        </form>
                    @endif

                    @if($campaign->status === 'paused')
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#updateSmtpModal">
                            SMTPs
                        </button>
                    @endif

                    <form action="{{ route('campaign.destroy', $campaign->id) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete campaign?')">
                            Delete
                        </button>
                    </form>

                    <div id="failed-download-container" style="display: none;">
                        <a href="{{ route('campaign.export-failed', $campaign->id) }}" class="btn btn-danger btn-sm">
                            Failed (<span id="failed-count-live">0</span>)
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card border-primary">
                            <div class="emoji">Total</div>
                            <h4 class="mb-0">{{ $campaign->total_emails }}</h4>
                            <small class="text-muted">Emails</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card border-success">
                            <div class="emoji">Sent</div>
                            <h4 class="mb-0" id="sent">{{ $campaign->sent }}</h4>
                            <small class="text-muted">Delivered</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card border-danger">
                            <div class="emoji">Failed</div>
                            <h4 class="mb-0" id="failed">{{ $campaign->failed }}</h4>
                            <small class="text-muted">Bounced</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card border-info">
                            <div class="emoji">Processed</div>
                            <h4 class="mb-0" id="processed">{{ $campaign->sent + $campaign->failed }}</h4>
                            <small class="text-muted">Done</small>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="progress">
                        <div id="progress-bar"
                            class="progress-bar bg-gradient {{ $campaign->status === 'completed' ? 'bg-success' : 'bg-primary' }}"
                            style="width: {{ $campaign->total_emails > 0 ? (($campaign->sent + $campaign->failed) / $campaign->total_emails * 100) : 0 }}%">
                            <span id="progress-text">
                                {{ $campaign->sent + $campaign->failed }} / {{ $campaign->total_emails }}
                            </span>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted" id="status-text">
                            @if($campaign->status === 'running') Sending...
                            @elseif($campaign->status === 'paused') Paused
                            @elseif($campaign->status === 'completed') Completed
                            @else Starting...
                            @endif
                        </small>
                    </div>
                </div>

                <!-- Log -->
                <div class="bg-white rounded-3 shadow-sm p-3">
                    <h5 class="mb-3">Activity Log</h5>
                    <div id="simple-log">
                        @forelse($campaign->log ?? [] as $line)
                            @php
                                preg_match('/To: ([^\s|]+)/', $line, $matches);
                                $email = $matches[1] ?? null;
                                $debugEntry = $email ? ($debugLogs[$email] ?? null) : null;

                                $isSuccess = str_contains($line, 'SUCCESS');
                                $isFailed = str_contains($line, 'FAILED');
                                $class = $isSuccess ? 'success' : ($isFailed ? 'failed' : 'info');
                                $icon = $isSuccess ? 'check-circle' : ($isFailed ? 'times-circle' : 'info-circle');
                            @endphp
                            <div class="log-line {{ $class }}">
                                <span>{{ $line }}</span>
                                @if($debugEntry && $debugEntry['debug'])
                                    <button class="btn btn-sm btn-outline-info debug-btn" data-bs-toggle="modal"
                                        data-bs-target="#debugModal{{ $debugEntry['id'] }}">
                                        Debug
                                    </button>

                                    <!-- Debug Modal -->
                                    <div class="modal fade" id="debugModal{{ $debugEntry['id'] }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">SMTP Debug: {{ $email }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <pre class="bg-dark text-light p-3 rounded"
                                                        style="max-height: 60vh; overflow: auto; font-size: 0.8em; white-space: pre-wrap;">
                                            {{ $debugEntry['debug'] }}
                                                                        </pre>
                                                </div>
                                                <div class="modal-footer">
                                                    <small class="text-muted">
                                                        SMTP: {{ $debugEntry['smtp_username'] }} |
                                                        Status: <strong>{{ ucfirst($debugEntry['status']) }}</strong> |
                                                        {{ \Carbon\Carbon::parse($debugEntry['created_at'])->format('H:i:s') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">No debug</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                <p>No activity yet...</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update SMTP Modal -->
    <div class="modal fade" id="updateSmtpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('campaign.update-smtps', $campaign->id) }}">
                @csrf
                <div class="modal-content rounded-3">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Update SMTP Accounts</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <textarea name="smtp_input" class="form-control" rows="8" required
                            placeholder="host | port | user | pass | from@email.com | From Name">{{ $campaign->smtp_input_updated ?? $campaign->smtp_input }}</textarea>
                        <small class="text-muted">One SMTP per line. Use | to separate fields.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save & Use on Resume</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let polling = true;
    const totalEmails = {{ $campaign->total_emails }};
    let lastLogCount = {{ count($campaign->log ?? []) }};

    function updateUI(data) {
        // Update stats
        document.getElementById('sent').textContent = data.sent;
        document.getElementById('failed').textContent = data.failed;
        document.getElementById('processed').textContent = data.sent + data.failed;

        // Progress
        const progress = totalEmails > 0 ? ((data.sent + data.failed) / totalEmails * 100) : 0;
        const bar = document.getElementById('progress-bar');
        bar.style.width = progress + '%';
        document.getElementById('progress-text').textContent = (data.sent + data.failed) + ' / ' + totalEmails;

        // Status
        const statusText = document.getElementById('status-text');
        if (data.status === 'completed') {
            statusText.innerHTML = '<span class="text-success">Completed</span>';
            bar.className = 'progress-bar bg-success';
            polling = false;
        } else if (data.status === 'running') {
            statusText.textContent = 'Sending...';
        } else if (data.status === 'paused') {
            statusText.textContent = 'Paused';
        }

        // === ONLY ADD NEW LOG LINES (DON'T REPLACE) ===
        const logContainer = document.getElementById('simple-log');
        const currentLines = logContainer.children.length;
        if (data.logHtml.length > currentLines) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.logHtml;
            const newLines = Array.from(tempDiv.children).slice(currentLines);
            newLines.forEach(line => logContainer.appendChild(line));
        }

        // Show download button
        if (data.failed > 0 && data.status === 'completed') {
            const container = document.getElementById('failed-download-container');
            container.style.display = 'inline-block';
            document.getElementById('failed-count-live').textContent = data.failed;
        }
    }

    function poll() {
        if (!polling) return;

        fetch("{{ route('campaign.show', $campaign->id) }}?ajax=1", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            updateUI(data);
            if (data.sent + data.failed >= totalEmails) {
                setTimeout(poll, 1000); // Final poll
            }
        })
        .catch(() => polling = false);
    }

    setInterval(poll, 2000);
    poll();
</script>
</body>

</html>

@if(request()->ajax())
    @php
        // Re-fetch the campaign fresh from DB
        $campaign = \App\Models\Campaign::find($campaign->id);

        // Reload debug logs for all emails of this campaign
        $debugLogs = \App\Models\DebugLog::where('campaign_id', $campaign->id)
            ->get()
            ->keyBy('email')
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'email' => $log->email,
                    'smtp_username' => $log->smtp_username,
                    'status' => $log->status,
                    'debug' => $log->debug,
                    'created_at' => $log->created_at,
                ];
            })
            ->toArray();

        $logLines = '';
        foreach (($campaign->log ?? []) as $line) {
            preg_match('/To: ([^\s|]+)/', $line, $matches);
            $email = $matches[1] ?? null;
            $debugEntry = $email ? ($debugLogs[$email] ?? null) : null;

            $isSuccess = str_contains($line, 'SUCCESS');
            $isFailed = str_contains($line, 'FAILED');
            $class = $isSuccess ? 'success' : ($isFailed ? 'failed' : 'info');

            $debugBtn = '';
            if ($debugEntry && $debugEntry['debug']) {
                $debugBtn = "
                    <button class='btn btn-sm btn-outline-info debug-btn' data-bs-toggle='modal'
                        data-bs-target='#debugModal{$debugEntry['id']}'>Debug</button>
                    <div class='modal fade' id='debugModal{$debugEntry['id']}' tabindex='-1'>
                        <div class='modal-dialog modal-lg'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <h5 class='modal-title'>SMTP Debug: {$email}</h5>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                </div>
                                <div class='modal-body'>
                                    <pre class='bg-dark text-light p-3 rounded' style='max-height: 60vh; overflow: auto; font-size: 0.8em; white-space: pre-wrap;'>"
                                    . htmlspecialchars($debugEntry['debug']) .
                                    "</pre>
                                </div>
                                <div class='modal-footer'>
                                    <small class='text-muted'>
                                        SMTP: {$debugEntry['smtp_username']} |
                                        Status: <strong>" . ucfirst($debugEntry['status']) . "</strong> |
                                        " . \Carbon\Carbon::parse($debugEntry['created_at'])->format('H:i:s') . "
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>";
            } else {
                $debugBtn = "<span class='text-muted small'>No debug</span>";
            }

            $logLines .= "<div class='log-line {$class}'><span>{$line}</span>{$debugBtn}</div>";
        }
    @endphp

    {!! response()->json([
        'sent' => $campaign->sent,
        'failed' => $campaign->failed,
        'status' => $campaign->status,
        'logHtml' => $logLines ?: '<div class="text-center text-muted py-4"><p>No activity yet...</p></div>'
    ])->getContent() !!}
    @php exit; @endphp
@endif
