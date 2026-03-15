@extends('layouts.master-admin')

@section('page_title', 'School Year Control')
@section('page_subtitle', 'One active school year at a time, with enrollment controls for that active year only')

@section('content')
<section class="panel schoolyear-panel">
    <div class="panel-head">
        <h3>School Year Governance</h3>
        <p class="muted">Use "Set Active" to switch school year. Enrollment controls apply only to the active year.</p>
    </div>

    <div class="panel mt-12">
        <div class="panel-head">
            <h4>Create Incoming School Year</h4>
            <p class="muted">Incoming school years stay inactive for setup and cannot accept enrollment until activated.</p>
        </div>
        <form method="POST" action="{{ route('master.school-years.store') }}" class="action-inline">
            @csrf
            <div style="min-width: 220px;">
                <label>School Year</label>
                <input type="text" name="year" placeholder="2027-2028" required>
            </div>
            @if($supportsEnrollmentWindow ?? false)
                <div style="min-width: 240px;">
                    <label>Enrollment Start (optional)</label>
                    <input type="datetime-local" name="enrollment_start_at">
                </div>
                <div style="min-width: 240px;">
                    <label>Enrollment End (optional)</label>
                    <input type="datetime-local" name="enrollment_end_at">
                </div>
            @endif
            <div style="align-self: end;">
                <button class="btn" type="submit">Create Year</button>
            </div>
        </form>
    </div>

    <div class="sy-main">
        <div>
            <p>Active School Year</p>
            <span class="big-badge">{{ $activeSchoolYear?->year ?? $activeSchoolYear?->name ?? 'No Active Year' }}</span>
        </div>
        <div>
            <p>Current Enrollment Access</p>
            <span class="big-badge {{ $activeSchoolYear && $activeSchoolYear->isEnrollmentOpenNow() ? 'approved' : 'rejected' }}">
                {{ $activeSchoolYear && $activeSchoolYear->isEnrollmentOpenNow() ? 'OPEN NOW' : 'CLOSED NOW' }}
            </span>
        </div>
        @if($activeSchoolYear)
            <form method="POST" action="{{ route('master.school-years.toggle', $activeSchoolYear) }}">
                @csrf
                <button class="btn {{ $activeSchoolYear->isEnrollmentSwitchOpen() ? 'btn-danger' : 'btn-secondary' }}" type="submit">
                    {{ $activeSchoolYear->isEnrollmentSwitchOpen() ? 'Set Closed' : 'Set Open' }}
                </button>
            </form>
        @endif
    </div>

    @if($activeSchoolYear && ($supportsEnrollmentWindow ?? false))
        <div class="panel mt-12">
            <div class="panel-head">
                <h4>Active Year Enrollment Window</h4>
                <p class="muted">Optional date range. When set, enrollment is open only within this window.</p>
            </div>
            <form method="POST" action="{{ route('master.school-years.enrollment-window', $activeSchoolYear) }}" class="action-inline">
                @csrf
                @method('PUT')
                <div style="min-width: 240px;">
                    <label>Enrollment Start</label>
                    <input type="datetime-local" lang="en-US" name="enrollment_start_at" value="{{ optional($activeSchoolYear->enrollment_start_at)->format('Y-m-d\\TH:i') }}">
                </div>
                <div style="min-width: 240px;">
                    <label>Enrollment End</label>
                    <input type="datetime-local" lang="en-US" name="enrollment_end_at" value="{{ optional($activeSchoolYear->enrollment_end_at)->format('Y-m-d\\TH:i') }}">
                </div>
                <div style="align-self: end;">
                    <button class="btn" type="submit">Save Window</button>
                </div>
            </form>
        </div>
    @endif

    <div class="table-wrap mt-12">
        <table>
            <thead>
            <tr>
                <th>School Year</th>
                <th>Active</th>
                <th>Locked</th>
                <th>Enrollment Switch</th>
                <th>Enrollment Window</th>
                <th>Has Approved Enrollees</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($schoolYears as $sy)
                <tr>
                    <td>{{ $sy->year ?? $sy->name }}</td>
                    <td>
                        <span class="badge {{ $sy->is_active ? 'approved' : 'rejected' }}">
                            {{ $sy->is_active ? 'ACTIVE' : 'INACTIVE' }}
                        </span>
                    </td>
                    <td>
                        @if($supportsLocking ?? false)
                            <span class="badge {{ $sy->is_locked ? 'reviewed' : 'approved' }}">
                                {{ $sy->is_locked ? 'LOCKED' : 'UNLOCKED' }}
                            </span>
                        @else
                            <span class="muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($sy->is_active)
                            <span class="badge {{ $sy->isEnrollmentSwitchOpen() ? 'reviewed' : 'rejected' }}">
                                {{ $sy->isEnrollmentSwitchOpen() ? 'OPEN' : 'CLOSED' }}
                            </span>
                        @else
                            <span class="muted">N/A (inactive year)</span>
                        @endif
                    </td>
                    <td>
                        {{ optional($sy->enrollment_start_at)->format('m/d/Y h:i A') ?? 'N/A' }}
                        <br>
                        {{ optional($sy->enrollment_end_at)->format('m/d/Y h:i A') ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="badge {{ ($sy->approved_applications_count ?? 0) > 0 ? 'reviewed' : 'approved' }}">
                            {{ ($sy->approved_applications_count ?? 0) > 0 ? 'YES' : 'NO' }}
                        </span>
                    </td>
                    <td class="action-row">
                        @if($sy->is_active)
                            
                        @elseif(($supportsLocking ?? false) && $sy->is_locked)
                            <span class="muted">Locked year cannot be activated.</span>
                        @else
                            <form method="POST" action="{{ route('master.school-years.set-active', $sy) }}">
                                @csrf
                                <button class="btn" type="submit">Set Active</button>
                            </form>
                        @endif

                        @if(($supportsLocking ?? false) && !$sy->is_active && !$sy->is_locked)
                            <form method="POST" action="{{ route('master.school-years.lock', $sy) }}">
                                @csrf
                                <button class="btn btn-secondary" type="submit">Lock</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No school years found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
