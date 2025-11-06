<!DOCTYPE html>
<html>

<head>
    <title>ZeusMailer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3>ZeusMailer - Launch Campaign</h3>
            </div>
            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('campaign.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">1. Recipients (.txt file)</label>
                        <input type="file" name="emails" class="form-control" accept=".txt" required>
                        <small class="text-muted">One email per line</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">2. SMTP Accounts</label>
                        <textarea name="smtp_input" class="form-control" rows="4"
                            placeholder="smtp.gmail.com|587|user@gmail.com|pass|From Name" required></textarea>
                        <small class="text-muted">Format: host|port|user|pass|from|name (one per line)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">3. Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-control" value="ZeusMailer" required>
                        <small class="text-muted">e.g., Your Brand</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">4. HTML Body</label>
                        <textarea name="html_body" class="form-control" rows="6" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Delay Between Emails (seconds)</label>
                        <input type="number" name="delay" class="form-control" min="0" max="60" value="1">
                        <small class="text-muted">0 = no delay, 1 = 1 second, etc.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg">Launch Campaign</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>