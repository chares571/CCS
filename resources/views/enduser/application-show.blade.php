@extends('layouts.enduser')

@section('page_title', 'Enrollment Status Timeline')
@section('page_subtitle', 'Track every status update for your submitted enrollment form.')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="timeline" /> Application Status Timeline</span></h2>
        <span class="badge {{ $application->status }}">{{ strtoupper($application->status) }}</span>
    </div>

    <p class="mt-10"><a class="btn btn-secondary" href="{{ route('homepage.enrollment') }}">Back to Enrollment</a></p>

    <p>Learner Name: <strong>{{ $application->learner_full_name }}</strong></p>
    <p>Grade Level: <strong>{{ $application->grade_level }}</strong></p>
    <p>Gender: <strong>{{ ucfirst($application->gender ?? 'Unspecified') }}</strong></p>

    <div class="table-wrap" style="margin-top:10px;">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Changed By</th>
            </tr>
            </thead>
            <tbody>
            @forelse($application->statusLogs()->orderBy('changed_at')->get() as $log)
                <tr>
                    <td>{{ $log->changed_at }}</td>
                    <td><span class="badge {{ $log->status }}">{{ strtoupper($log->status) }}</span></td>
                    <td>{{ $log->remarks ?: '-' }}</td>
                    <td>{{ optional($log->changedBy)->full_name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No timeline entries yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
