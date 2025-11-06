<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeusMailer - Campaigns</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
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

        /* Header */
        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 3rem;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .btn-new {
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white;
            font-weight: 600;
            padding: 0.6rem 1.1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-new:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
        }

        /* Campaign Card */
        .campaign-card {
            border: none;
            border-radius: 1rem;
            background: white;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #e9efff, #f2e9ff);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .campaign-id {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .stat-badge {
            background: rgba(102, 126, 234, 0.15);
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }

        .campaign-subject {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            word-break: break-word;
        }

        /* Progress */
        .progress {
            height: 24px;
            border-radius: 12px;
            background: #edf2f7;
            overflow: hidden;
        }

        .progress-bar {
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.5s ease;
        }

        .progress-bar.completed {
            background: linear-gradient(90deg, var(--success), #48bb78);
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 0.6rem;
            margin-top: auto;
        }

        .btn-action {
            flex: 1;
            font-size: 0.85rem;
            padding: 0.45rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: center;
            border: 1.5px solid transparent;
        }

        .btn-view {
            color: var(--primary);
            border-color: var(--primary);
            background: transparent;
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        .btn-delete {
            color: var(--danger);
            border-color: var(--danger);
            background: transparent;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        /* Empty State */
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

        .empty-state h4 {
            color: var(--text-dark);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-bolt"></i> ZeusMailer</h1>
                <a href="{{ route('campaign.create') }}" class="btn-new">
                    <i class="fas fa-plus me-1"></i> New Campaign
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
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
                        <div class="campaign-card">
                            <div class="card-header-custom">
                                <span class="campaign-id">#{{ $c->id }}</span>
                                <span class="stat-badge">
                                    <i class="fas fa-envelope"></i> {{ $c->total_emails }}
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="campaign-subject">{{ $c->subject }}</h5>
                                <div class="progress">
                                    <div class="progress-bar {{ $c->sent >= $c->total_emails ? 'completed' : '' }}"
                                        style="width: {{ $c->total_emails > 0 ? ($c->sent / $c->total_emails * 100) : 0 }}%">
                                        {{ $c->sent }} / {{ $c->total_emails }}
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <a href="{{ route('campaign.show', $c->id) }}" class="btn-action btn-view">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <form action="{{ route('campaign.destroy', $c->id) }}" method="POST" class="d-inline w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action btn-delete w-100"
                                            onclick="return confirm('Delete campaign #{{ $c->id }}?')">
                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
