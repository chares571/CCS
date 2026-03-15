@extends('layouts.enduser')

@section('page_title', 'Enrollment Status Timeline')
@section('page_subtitle', 'Track every status update for your submitted enrollment form.')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="timeline" /> Application Status Timeline</span></h2>
        <span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::for($application->status) }}</span>
    </div>

    <p class="mt-10"><a class="btn btn-secondary" href="{{ route('homepage.enrollment') }}">Back to Enrollment</a></p>

    <p>Learner Name: <strong>{{ $application->learner_full_name }}</strong></p>
    <p>Grade Level: <strong>{{ $application->grade_level }}</strong></p>
    <p>Gender: <strong>{{ ucfirst($application->gender ?? 'Unspecified') }}</strong></p>

    @if($application->documents->count())
        <div class="panel" style="margin-top:10px;">
            <h3> Uploaded Documents</h3>
            @foreach($application->documents as $doc)
                <p>
                    <a href="{{ '/storage/'.ltrim($doc->file_path, '/') }}" target="_blank">{{ $doc->original_name }}</a>
                </p>
            @endforeach
        </div>
    @endif

    <div class="table-wrap table-wrap--stack" style="margin-top:10px;">
        <table class="enduser-table--stack">
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
                    <td data-label="Date">{{ optional($log->changed_at)->timezone(config('app.timezone'))->format('m/d/Y h:i A') ?? '-' }}</td>
                    <td data-label="Status"><span class="badge {{ $log->status }}">{{ \App\Support\StatusLabel::for($log->status) }}</span></td>
                    <td data-label="Remarks">{{ $log->remarks ?: '-' }}</td>
                    <td data-label="Changed By">{{ optional($log->changedBy)->full_name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No timeline entries yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
