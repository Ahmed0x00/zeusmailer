<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeusMailer - Dashboard</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #5a67d8;
            --secondary: #805ad5;
            --success: #38a169;
            --danger: #e53e3e;
            --light: #f8f9fa;
            --text-dark: #2d3748;
            --muted: #718096;
        }

        body {
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
        }

        .nav-buttons a {
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            font-weight: 600;
            padding: 0.65rem 1.2rem;
            border-radius: 0.6rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .nav-buttons a:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .progress {
            height: 20px;
            border-radius: 10px;
            background: #edf2f7;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .progress-bar.completed {
            background: linear-gradient(90deg, var(--success), #48bb78);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            flex: 1;
            border: 1.5px solid transparent;
            border-radius: 0.5rem;
            padding: 0.45rem 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .btn-view {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        .btn-delete {
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        .tools-section .card {
            background: linear-gradient(135deg, #f9faff, #eef2ff);
            border-left: 4px solid var(--primary);
        }

        .tools-section .card i {
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h1><i class="fas fa-bolt me-2"></i> ZeusMailer Dashboard</h1>
            <div class="nav-buttons d-flex flex-wrap gap-2">
                <a href="{{ route('campaign.create') }}"><i class="fas fa-plus me-1"></i> New Campaign</a>
                <a href="{{ route('html.preview') }}"><i class="fas fa-code me-1"></i> HTML Preview</a>
                <a href="{{ route('smtp.test') }}"><i class="fas fa-server me-1"></i> SMTP Tester</a>
                <a href="{{ route('smtp.create') }}"><i class="fas fa-server me-1"></i> SMTP Checker</a>
                <a href="{{ url('/verifier') }}" class="btn btn-lg btn-info fw-semibold shadow-sm px-4 py-2">
                    <i class="fas fa-envelope-circle-check me-2"></i>Email Verifier
                </a>

            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container pb-5">
        <h4 class="section-title"><i class="fas fa-envelope me-2"></i> Email Campaigns</h4>

        @if($campaigns->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body empty-state">
                    <i class="fas fa-envelope-open-text"></i>
                    <h4>No campaigns yet</h4>
                    <p>Get started by creating your first email campaign.</p>
                    <a href="{{ route('campaign.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Campaign
                    </a>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach($campaigns as $c)
                    <div class="col-md-6 col-lg-4">
                        <div class="card p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-muted">#{{ $c->id }}</span>
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-envelope me-1"></i> {{ $c->total_emails }}
                                </span>
                            </div>
                            <h5 class="fw-bold">{{ $c->subject }}</h5>
                            <div class="progress my-2">
                                <div class="progress-bar {{ $c->sent >= $c->total_emails ? 'completed' : '' }}"
                                    style="width: {{ $c->total_emails > 0 ? ($c->sent / $c->total_emails * 100) : 0 }}%">
                                    {{ $c->sent }} / {{ $c->total_emails }}
                                </div>
                            </div>
                            <small>Status: <strong>{{ ucfirst($c->status) }}</strong></small>
                            <div class="action-buttons mt-3">
                                <a href="{{ route('campaign.show', $c->id) }}" class="btn-action btn-view">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <form action="{{ route('campaign.destroy', $c->id) }}" method="POST" class="d-inline w-100">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete"
                                        onclick="return confirm('Delete campaign #{{ $c->id }}?')">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- SMTP Tools Section -->
        <div class="tools-section mt-5">
            <h4 class="section-title"><i class="fas fa-tools me-2"></i> SMTP Tools</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card p-3">
                        <h6 class="fw-semibold"><i class="fas fa-server me-2"></i> SMTP Checker</h6>
                        <p class="small text-muted mb-2">Upload email:password combos and check live results.</p>
                        <a href="{{ route('smtp.create') }}" class="btn btn-primary w-100">Start New Batch</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3">
                        <h6 class="fw-semibold"><i class="fas fa-list me-2"></i> View Checker Batches</h6>
                        <p class="small text-muted mb-2">View progress, pause/resume or export batch results.</p>
                        <a href="{{ route('smtp.batches') }}" class="btn btn-primary w-100">View Batches</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3">
                        <h6 class="fw-semibold"><i class="fas fa-database me-2"></i> All SMTP Results</h6>
                        <p class="small text-muted mb-2">Browse all saved SMTP success results from all batches.</p>
                        <a href="{{ route('smtp.results') }}" class="btn btn-primary w-100">Browse Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>