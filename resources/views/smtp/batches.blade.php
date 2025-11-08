<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - SMTP Batches</title>
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

        .progress {
            height: 18px;
        }

        .status-pill {
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .completed {
            background: #dcfce7;
        }

        .running {
            background: #e0e7ff;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="fs-4 m-0"><i class="fas fa-list me-2"></i> ZeusMailer - SMTP Batches</h1>
            <a href="{{ url('/smtp') }}" class="btn btn-light fw-semibold">
                <i class="fas fa-plus me-1"></i> New Batch
            </a>
            <a href="{{ route('campaign.index') }}" class="btn btn-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to Campaigns
            </a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="container pb-5">
        <div class="card">
            <div class="card-header bg-primary bg-gradient text-white fw-semibold">
                <i class="fas fa-database me-2"></i> All SMTP Check Batches
            </div>

            <div class="card-body">
                <div class="table-responsive border rounded">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Total</th>
                                <th>Processed</th>
                                <th>Success</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                @php
                                    $percent = $batch->total > 0 ? round(($batch->processed / $batch->total) * 100) : 0;
                                    $done = $batch->processed >= $batch->total;
                                @endphp
                                <tr class="{{ $done ? 'completed' : 'running' }}">
                                    <td><code>{{ $batch->id }}</code></td>
                                    <td>{{ $batch->total }}</td>
                                    <td>{{ $batch->processed }}</td>
                                    <td class="text-success fw-semibold">{{ $batch->success }}</td>
                                    <td style="width: 160px;">
                                        <div class="progress">
                                            <div class="progress-bar {{ $done ? 'bg-success' : 'bg-primary' }}"
                                                style="width: {{ $percent }}%">
                                                {{ $percent }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="status-pill {{ $done ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                            {{ $done ? 'Completed' : 'Running' }}
                                        </span>
                                    </td>
                                    <td>{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ url('/smtp/batch/' . $batch->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-lg mb-2"></i><br>
                                        No batches found yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>