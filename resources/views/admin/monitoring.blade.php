@extends('layouts.admin')

@section('page_title', 'Monitoring')
@section('page_subtitle', 'Enrollment application records grouped by grade level')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h3>Filter by Learner Name</h3>
        <p class="muted">Search by full name, first name, middle name, or last name. You can also filter by Pending or Reviewed.</p>
    </div>
    <form method="GET" action="{{ route('admin.monitoring') }}" class="action-inline">
        @if(!($showAllGrades ?? false))
            <input type="hidden" name="grade" value="{{ $selectedGrade ?? 'Kindergarten' }}">
        @endif
        <input type="text" name="name" value="{{ $nameFilter ?? '' }}" placeholder="Enter learner name..." style="max-width: 360px;">
        <select name="status" style="max-width: 220px;">
            <option value="all" {{ ($selectedStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="pending" {{ ($selectedStatus ?? 'all') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="reviewed" {{ ($selectedStatus ?? 'all') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
        </select>
        <button class="btn" type="submit">Search</button>
        @if(!empty($nameFilter) || (($selectedStatus ?? 'all') !== 'all'))
            <a class="btn btn-secondary" href="{{ route('admin.monitoring', ['grade' => $selectedGrade ?? 'Kindergarten', 'status' => 'all']) }}">Clear</a>
        @endif
    </form>
    @if(!empty($nameFilter) || (($selectedStatus ?? 'all') !== 'all'))
        <p class="muted mt-10">
            {{ $matchedCount ?? 0 }} result(s)
            @if(!empty($nameFilter))
                found for "{{ $nameFilter }}"
            @endif
            @if(($selectedStatus ?? 'all') !== 'all')
                with status "{{ strtoupper($selectedStatus ?? 'all') }}"
            @endif
            .
        </p>
    @endif
</section>

@if((($hasFilter ?? false) || (($selectedStatus ?? 'all') !== 'all')) && ($matchedCount ?? 0) === 0)
    <section class="panel">
        <p class="muted">
            @if(!empty($nameFilter))
                No learner record found for "{{ $nameFilter }}".
            @else
                No applications found for status "{{ strtoupper($selectedStatus ?? 'all') }}".
            @endif
        </p>
    </section>
@endif

<section class="panel grade-quick-nav-panel grade-quick-nav-panel--priority">
    <div class="panel-head">
        <h3>Grade Level Navigation</h3>
        <p class="muted">
            @if($showAllGrades ?? false)
                Showing all matching applications across all grade levels.
            @else
                Open one grade level at a time.
            @endif
        </p>
    </div>
    <nav class="grade-quick-nav" aria-label="Grade level navigation">
        @foreach(($gradeLevels ?? []) as $grade)
            <a
                class="grade-quick-nav-link {{ !($showAllGrades ?? false) && ($selectedGrade ?? '') === $grade ? 'is-active' : '' }}"
                href="{{ route('admin.monitoring', array_filter(['grade' => $grade, 'name' => $nameFilter ?? null, 'status' => ($selectedStatus ?? 'all') !== 'all' ? ($selectedStatus ?? 'all') : null])) }}"
            >
                {{ $grade }}
            </a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('admin.monitoring') }}" class="grade-mobile-select">
        @if(!empty($nameFilter))
            <input type="hidden" name="name" value="{{ $nameFilter }}">
        @endif
        @if(($selectedStatus ?? 'all') !== 'all')
            <input type="hidden" name="status" value="{{ $selectedStatus }}">
        @endif
        <label for="admin_monitoring_grade_mobile">Quick Grade Jump</label>
        <select id="admin_monitoring_grade_mobile" name="grade" onchange="this.form.submit()">
            @foreach(($gradeLevels ?? []) as $grade)
                <option value="{{ $grade }}" {{ ($selectedGrade ?? '') === $grade ? 'selected' : '' }}>{{ $grade }}</option>
            @endforeach
        </select>
    </form>
</section>

@foreach($applicationsByGrade as $grade => $items)
    <section class="panel">
        <div class="panel-head">
            <h3>{{ $grade }}</h3>
            <p class="muted">{{ $items->count() }} application(s)</p>
        </div>
        <div class="table-wrap table-wrap-wide">
            <table class="table-wide">
                <thead>
                <tr>
                    <th>Application #</th>
                    <th>Learner Name</th>
                    <th>Grade Level</th>
                    <th>Information</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $application)
                    <tr>
                        <td>{{ $application->id }}</td>
                        <td>{{ $application->learner_full_name }}</td>
                        <td>{{ $application->grade_level }}</td>
                        <td>
                            <strong>LRN:</strong> {{ $application->lrn ?: 'N/A' }}<br>
                            <strong>Gender:</strong> {{ ucfirst($application->gender ?? '-') }}<br>
                            <strong>Returning Learner:</strong> {{ $application->returning_learner ? 'Yes' : 'No' }}<br>
                            <strong>Guardian Contact:</strong> {{ $application->guardian_contact_number ?: 'N/A' }}
                        </td>
                        <td><span class="badge {{ $application->status }}">{{ \App\Support\StatusLabel::for($application->status) }}</span></td>
                        <td>{{ optional($application->submitted_at)->format('m/d/Y h:i A') ?? '-' }}</td>
                        <td class="action-row">
                            <a class="btn" href="{{ route('admin.monitoring.show', $application) }}">View Enrollment Form</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No applications in {{ $grade }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endforeach
@endsection
