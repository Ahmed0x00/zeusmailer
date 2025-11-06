<!DOCTYPE html>
<html>

<head>
    <title>ZeusMailer - Campaigns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>ZeusMailer</h1>
            <a href="{{ route('campaign.create') }}" class="btn btn-success">+ New Campaign</a>
        </div>

        @if($campaigns->isEmpty())
            <div class="alert alert-info">No campaigns yet. <a href="{{ route('campaign.create') }}">Create one</a></div>
        @else
            <div class="row">
                @foreach($campaigns as $c)
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Campaign #{{ $c->id }} - {{ $c->subject }}</h5>
                                <p class="text-muted">{{ $c->total_emails }} emails</p>
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar {{ $c->completed ? 'bg-success' : 'bg-primary' }}"
                                        style="width: {{ $c->total_emails > 0 ? ($c->sent / $c->total_emails * 100) : 0 }}%">
                                        {{ $c->sent }} / {{ $c->total_emails }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('campaign.show', $c->id) }}"
                                        class="btn btn-sm btn-outline-primary">View</a>
                                    <form action="{{ route('campaign.destroy', $c->id) }}" method="POST" class="d-inline">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete campaign #{{ $c->id }}?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>

</html>