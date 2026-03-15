@extends('layouts.master-admin')

@section('page_title', 'Enrollment History')
@section('page_subtitle', 'All enrollment submissions linked to registered parent/student accounts')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h3>Search Enrollment History</h3>
        <p class="muted">Filter by account name, learner name, username, or email.</p>
    </div>
    <form method="GET" action="{{ route('master.enrollment-history') }}" class="action-inline">
        <input type="text" name="name" value="{{ $nameFilter ?? '' }}" placeholder="Enter name..." style="max-width: 360px;">
        <button class="btn" type="submit">Search</button>
        @if(!empty($nameFilter))
            <a class="btn btn-secondary" href="{{ route('master.enrollment-history') }}">Clear</a>
        @endif
    </form>
    @if(!empty($nameFilter))
        <p class="muted mt-10">{{ $matchedCount ?? 0 }} result(s) found for "{{ $nameFilter }}".</p>
    @endif
</section>

<section class="panel">
    <div class="table-wrap table-wrap-compact">
        <table class="table-compact">
            <thead>
            <tr>
                <th>Registered Account</th>
                <th>Account Type</th>
                <th>Enrolled Learner</th>
                <th>Grade Level</th>
                <th>School Year</th>
                <th>Status</th>
                <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            @forelse($history as $application)
                <tr>
                    <td>
                        {{ $application->user?->full_name ?? 'Unknown Account' }}<br>
                        <span class="muted">{{ $application->user?->username ?? 'N/A' }} | {{ $application->user?->email ?? 'N/A' }}</span>
                    </td>
                    <td>{{ strtoupper($application->user?->role ?? 'N/A') }}</td>
                    <td>{{ $application->learner_full_name }}</td>
                    <td>{{ $application->grade_level }}</td>
                    <td>{{ $application->schoolYear->year ?? $application->schoolYear->name ?? 'N/A' }}</td>
                    <td><span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::forSuperAdmin($application->status) }}</span></td>
                    <td>{{ optional($application->submitted_at)->format('m/d/Y h:i A') ?? optional($application->created_at)->format('m/d/Y h:i A') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No enrollment history found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $history->links() }}</div>
</section>
@endsection
