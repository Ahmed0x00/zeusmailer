<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - All SMTP Results</title>
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

        table th {
            background: var(--primary);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .success { background: #dcfce7 !important; }
        .failed { background: #fee2e2 !important; }
        .pending { background: #e0e7ff !important; }

        .search-box {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="fs-4 m-0"><i class="fas fa-database me-2"></i> ZeusMailer - All SMTP Results</h1>
        <a href="{{ url('/smtp/batches') }}" class="btn btn-light fw-semibold">
            <i class="fas fa-arrow-left me-1"></i> Back to Batches
        </a>
    </div>
</div>

<!-- CONTENT -->
<div class="container pb-5">
    <div class="card">
        <div class="card-header bg-primary bg-gradient text-white fw-semibold">
            <i class="fas fa-list me-2"></i> All Checked SMTPs
        </div>

        <div class="card-body">
            <!-- Search / Filter -->
            <form method="GET" action="{{ url('/smtp/results') }}" class="mb-3">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                            placeholder="Search by email, host, or provider">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- All Statuses --</option>
                            <option value="success" {{ request('status')=='success'?'selected':'' }}>Success</option>
                            <option value="failed" {{ request('status')=='failed'?'selected':'' }}>Failed</option>
                            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ url('/smtp/results/export') }}" class="btn btn-success w-100">
                            <i class="fas fa-file-export me-1"></i> Export
                        </a>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Response</th>
                            <th>Checked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $res)
                            <tr class="{{ $res->status }}">
                                <td><code>{{ $res->batch_id }}</code></td>
                                <td>{{ $res->email }}</td>
                                <td><code>{{ $res->password }}</code></td>
                                <td>{{ $res->host }}</td>
                                <td>{{ $res->port }}</td>
                                <td>{{ $res->provider }}</td>
                                <td>
                                    @if($res->status == 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif($res->status == 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td><small>{{ $res->response ?? '-' }}</small></td>
                                <td>{{ $res->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-lg mb-2"></i><br>
                                    No SMTP results found yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $results->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
