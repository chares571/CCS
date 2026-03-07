@extends('layouts.enduser')

@section('page_title', 'Enrollment Navigation')
@section('page_subtitle', 'Complete and submit the enrollment form, then track status updates.')

@section('content')
<section class="panel">
    <div class="panel-head">
        <h2><span class="icon-inline"><x-icon name="enrollment" /> Enrollment Overview</span></h2>
    </div>

    @if(!$activeSchoolYear || !($isEnrollmentOpen ?? false))
        <div class="alert alert-warning">Enrollment is currently closed.</div>
    @else
        <p>Enrollment is open for school year: <strong>{{ $activeSchoolYear->name }}</strong>.</p>
        @if($activeSchoolYear->enrollment_start_at || $activeSchoolYear->enrollment_end_at)
            <p class="muted">
                Window:
                {{ optional($activeSchoolYear->enrollment_start_at)->format('M d, Y h:i A') ?? 'N/A' }}
                to
                {{ optional($activeSchoolYear->enrollment_end_at)->format('M d, Y h:i A') ?? 'N/A' }}
            </p>
        @endif
    @endif

    @if($latestApplication)
        <div class="enduser-status-strip">
            <div>
                <p class="muted">Latest Application Status</p>
                <span class="badge {{ $latestApplication->status }}">{{ strtoupper($latestApplication->status) }}</span>
            </div>
            <div>
                <p class="muted">Latest Submitted</p>
                <strong>{{ optional($latestApplication->submitted_at)->format('M d, Y h:i A') ?? 'Not available' }}</strong>
            </div>
            <div class="enduser-status-cta">
                <a class="btn btn-secondary" href="{{ route('applications.show', $latestApplication) }}">Open Latest Timeline</a>
            </div>
        </div>
    @endif
</section>

<section class="panel">
    <div class="panel-head">
        <h3><span class="icon-inline"><x-icon name="document" /> New Learner Enrollment Form</span></h3>
    </div>

    @if($activeSchoolYear && ($isEnrollmentOpen ?? false))
        <form method="POST" action="{{ route('applications.store') }}" class="js-enduser-enrollment-form" data-readonly="0">
            @csrf
            <div class="enrollment-paper">
                @include('enduser.partials.enrollment-paper-head', [
                    'application' => null,
                    'activeSchoolYear' => $activeSchoolYear,
                ])
                @include('enduser.partials.enrollment-form-fields', [
                    'application' => null,
                    'readonly' => false,
                ])
            </div>
            <button class="btn" type="submit" style="margin-top:10px;">Submit Application</button>
        </form>
    @else
        <p class="muted">Please wait for the school to open enrollment before submitting a form.</p>
    @endif
</section>

@if(($applications ?? collect())->isNotEmpty())
<section class="panel">
    <div class="panel-head">
        <h3><span class="icon-inline"><x-icon name="timeline" /> Submitted Learner Applications</span></h3>
    </div>

    <div class="table-wrap mt-10">
        <table>
            <thead>
            <tr>
                <th>Learner</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Grade Level</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($applications as $entry)
                <tr>
                    <td>{{ $entry->learner_full_name }}</td>
                    <td>{{ optional($entry->submitted_at)->format('M d, Y h:i A') ?? '-' }}</td>
                    <td><span class="badge {{ $entry->status }}">{{ strtoupper($entry->status) }}</span></td>
                    <td>{{ $entry->grade_level }}</td>
                    <td><a class="btn btn-secondary" href="{{ route('applications.show', $entry) }}">Open Timeline</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No submitted applications yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

<script>
(() => {
    const forms = document.querySelectorAll('.js-enduser-enrollment-form');

    if (!forms.length) {
        return;
    }

    const bindConditionalField = (form, radioName, targetSelector) => {
        const radios = form.querySelectorAll(`input[name="${radioName}"]`);
        const targets = form.querySelectorAll(targetSelector);

        if (!radios.length || !targets.length) {
            return;
        }

        const refresh = () => {
            const checked = form.querySelector(`input[name="${radioName}"]:checked`);
            const enabled = checked && checked.value === '1';

            targets.forEach((target) => {
                target.disabled = !enabled;
                if (!enabled) {
                    if (target.type === 'checkbox' || target.type === 'radio') {
                        target.checked = false;
                    } else {
                        target.value = '';
                    }
                }
            });
        };

        radios.forEach((radio) => {
            radio.addEventListener('change', refresh);
        });

        refresh();
    };

    forms.forEach((form) => {
        if (form.dataset.readonly === '1') {
            return;
        }

        form.querySelectorAll('input[type="text"], textarea').forEach((field) => {
            field.addEventListener('input', () => {
                const cursorPos = field.selectionStart;
                field.value = field.value.toUpperCase();
                if (typeof cursorPos === 'number') {
                    field.setSelectionRange(cursorPos, cursorPos);
                }
            });
        });

        bindConditionalField(form, 'with_lrn', 'input[name="lrn"]');
        bindConditionalField(form, 'has_ip_affiliation', 'input[name="ip_affiliation"]');
        bindConditionalField(form, 'is_4ps_beneficiary', 'input[name="four_ps_household_id"]');
        bindConditionalField(form, 'is_lwd', 'input[name="disability_types[]"]');
    });
})();
</script>
@endsection
