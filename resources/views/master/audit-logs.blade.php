@extends('layouts.master-admin')

@section('page_title', 'System Activity Logs')
@section('page_subtitle', 'System-sensitive activity records')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h3>Audit Trail</h3>
        <p class="muted">Search and filter logs, then navigate pages faster.</p>
    </div>
    <form method="GET" action="{{ route('master.audit-logs.index') }}" class="action-inline">
        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search action, entity, IP, user ID..." style="min-width: 260px; max-width: 380px;">
        <select name="action" style="max-width: 220px;">
            <option value="">All Actions</option>
            @foreach($actionOptions as $option)
                <option value="{{ $option }}" {{ ($action ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
        <select name="entity_type" style="max-width: 220px;">
            <option value="">All Entities</option>
            @foreach($entityTypeOptions as $option)
                <option value="{{ $option }}" {{ ($entityType ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
        <select name="per_page" style="max-width: 120px;">
            @foreach([20, 50, 100] as $size)
                <option value="{{ $size }}" {{ (int) ($perPage ?? 20) === $size ? 'selected' : '' }}>{{ $size }}/page</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Apply</button>
        <a class="btn btn-secondary" href="{{ route('master.audit-logs.index') }}">Reset</a>
    </form>

    <p class="muted mt-10">
        Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} log entries.
    </p>

    <div class="table-wrap table-wrap-compact">
        <table class="table-compact">
            <thead>
            <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Entity ID</th>
                <th>User ID</th>
                <th>IP Address</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('m/d/Y h:i A') }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->entity_type }}</td>
                    <td>{{ $log->entity_id ?? '-' }}</td>
                    <td>{{ $log->user_id ?? '-' }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No logs found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        {{ $logs->onEachSide(1)->links() }}
    </div>
</section>
@endsection
