<!DOCTYPE html>
<html>
<head>
    <title>Campaign #{{ $campaign->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #debug-log { font-family: 'Courier New', monospace; font-size: 0.85em; white-space: pre-wrap; }
        .progress { height: 30px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Campaign #{{ $campaign->id }} - {{ $campaign->subject }}</h4>
            <div>
                <a href="{{ route('campaign.index') }}" class="btn btn-sm btn-secondary">Back</a>
                <form action="{{ route('campaign.destroy', $campaign->id) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 text-center">
                <div class="col">
                    <strong>Total:</strong> {{ $campaign->total_emails }}
                </div>
                <div class="col">
                    <strong>Sent:</strong> <span id="sent">{{ $campaign->sent }}</span>
                </div>
                <div class="col">
                    <strong>Failed:</strong> <span id="failed">{{ $campaign->failed }}</span>
                </div>
            </div>

            <div class="progress mb-4">
                <div id="progress-bar" class="progress-bar {{ $campaign->completed ? 'bg-success' : 'bg-primary' }}"
                     style="width: {{ $campaign->total_emails > 0 ? ($campaign->sent / $campaign->total_emails * 100) : 0 }}%">
                    <span id="progress-text">{{ $campaign->sent }} / {{ $campaign->total_emails }}</span>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button">Simple Log</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="debug-tab" data-bs-toggle="tab" data-bs-target="#debug" type="button">Full SMTP Debug</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="log">
                    <div id="simple-log" class="border p-3 bg-white" style="height: 250px; overflow-y: auto; font-family: monospace; font-size: 0.9em;">
                        @foreach($campaign->log ?? [] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </div>
                </div>
                <div class="tab-pane fade" id="debug">
                    <div id="debug-log" class="border p-3 bg-dark text-light" style="height: 400px; overflow-y: auto;">
                        {!! nl2br(e($campaign->debug_log ?? 'No debug output yet...')) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let polling = true;

function poll() {
    if (!polling) return;

    fetch("{{ route('campaign.show', $campaign->id) }}")
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');

            // Update numbers
            ['sent', 'failed', 'progress-text'].forEach(id => {
                document.getElementById(id).textContent = doc.getElementById(id).textContent;
            });

            // Update progress bar
            const pb = doc.getElementById('progress-bar');
            document.getElementById('progress-bar').style.width = pb.style.width;
            document.getElementById('progress-bar').className = pb.className;

            // Update logs
            document.getElementById('simple-log').innerHTML = doc.getElementById('simple-log').innerHTML;
            document.getElementById('debug-log').innerHTML = doc.getElementById('debug-log').innerHTML;

            // Auto-scroll
            const debugDiv = document.getElementById('debug-log');
            debugDiv.scrollTop = debugDiv.scrollHeight;

            // Check if campaign is done
            const sent = parseInt(doc.getElementById('sent').textContent);
            const total = {{ $campaign->total_emails }};
            if (sent + parseInt(doc.getElementById('failed').textContent) >= total) {
                polling = false;
                document.getElementById('progress-bar').classList.replace('bg-primary', 'bg-success');
            }
        })
        .catch(() => polling = false);
}

// Start polling
const interval = setInterval(poll, 2000);
poll(); // First load
</script>
</body>
</html>