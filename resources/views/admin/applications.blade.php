@extends('layouts.admin')

@section('page_title', 'Applications')
@section('page_subtitle', 'Reviewed enrollees list with search filter')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h3>Reviewed Enrollees</h3>
        <p class="muted">Filter reviewed records by learner name.</p>
    </div>
    <div class="reviewed-meta-row">
        <span class="badge reviewed">TOTAL REVIEWED: {{ $applications->total() }}</span>
        @if(!empty($nameFilter))
            <span class="badge approved">FILTERED: {{ $matchedCount ?? 0 }}</span>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.applications.index') }}" class="action-inline reviewed-search-row">
        <input type="text" name="name" value="{{ $nameFilter ?? '' }}" placeholder="Enter learner name..." class="reviewed-search-input">
        <button class="btn" type="submit">Search</button>
        @if(!empty($nameFilter))
            <a class="btn btn-secondary" href="{{ route('admin.applications.index') }}">Clear</a>
        @endif
    </form>

    @if(!empty($nameFilter))
        <p class="muted mt-10">{{ $matchedCount ?? 0 }} result(s) found for "{{ $nameFilter }}".</p>
    @endif

    @if($applications->count() === 0)
        <div class="reviewed-empty">
            <h4>No reviewed enrollees found.</h4>
            <p class="muted">
                {{ !empty($nameFilter) ? 'Try a different learner name or clear the filter.' : 'Reviewed applications will appear here once Admin marks records as reviewed.' }}
            </p>
        </div>
    @else
        <div class="table-wrap table-wrap-compact">
            <table class="table-compact">
                <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Grade Level</th>
                    <th>Status</th>
                    <th>Reviewed</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($applications as $app)
                    <tr>
                        <td>
                            <div class="applicant-cell">
                                <div class="app-avatar">{{ strtoupper(substr($app->learner_full_name, 0, 1)) }}</div>
                                <div>
                                    <strong>{{ $app->learner_full_name }}</strong>
                                    <p class="muted">Application #{{ $app->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $app->grade_level }}</td>
                        <td><span class="badge {{ $app->status }}">{{ \App\Support\StatusLabel::for($app->status) }}</span></td>
                        <td>{{ optional($app->reviewed_at)->format('m/d/Y h:i A') ?? '-' }}</td>
                        <td><a class="btn" href="{{ route('admin.monitoring.show', $app) }}">View Enrollment Form</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="pagination-wrap">
        {{ $applications->onEachSide(1)->links() }}
    </div>
</section>
@endsection
