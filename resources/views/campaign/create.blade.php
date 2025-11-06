<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - Launch Campaign</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        /* Card */
        .card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.3rem;
        }

        .card-body {
            background: white;
            padding: 2rem;
        }

        label.form-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        input.form-control,
        textarea.form-control {
            border-radius: 0.5rem;
            border: 1px solid #cbd5e0;
            transition: all 0.2s ease;
        }

        input.form-control:focus,
        textarea.form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(90, 103, 216, 0.2);
        }

        .btn-success {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        small.text-muted {
            color: #6b7280 !important;
        }

        /* Alert */
        .alert {
            border-radius: 0.5rem;
            font-weight: 500;
        }

        /* Responsive tweaks */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.25rem;
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
                <a href="{{ route('campaign.index') }}" class="btn btn-light fw-semibold">
                    <i class="fas fa-arrow-left me-1"></i> Back to Campaigns
                </a>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="container pb-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-paper-plane me-2"></i> Launch Campaign
            </div>
            <div class="card-body">
                
                @if(session('success'))
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('campaign.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-users me-1"></i> 1. Recipients (.txt file)</label>
                        <input type="file" name="emails" class="form-control" accept=".txt" required>
                        <small class="text-muted">One email per line</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-server me-1"></i> 2. SMTP Accounts</label>
                        <textarea name="smtp_input" class="form-control" rows="4"
                            placeholder="smtp.gmail.com|587|user@gmail.com|pass|From Name" required></textarea>
                        <small class="text-muted">Format: host|port|user|pass|from|name (one per line)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-heading me-1"></i> 3. Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Enter your campaign subject" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user-tag me-1"></i> From Name</label>
                        <input type="text" name="from_name" class="form-control" value="ZeusMailer" required>
                        <small class="text-muted">e.g., Your Brand or Company Name</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-code me-1"></i> 4. HTML Body</label>
                        <textarea name="html_body" class="form-control" rows="6" placeholder="Paste your HTML email content here..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-clock me-1"></i> Delay Between Emails (seconds)</label>
                        <input type="number" name="delay" class="form-control" min="0" max="60" value="1">
                        <small class="text-muted">0 = no delay, 1 = 1 second, etc.</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-rocket me-2"></i> Launch Campaign
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
