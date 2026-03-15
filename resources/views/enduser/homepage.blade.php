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
                {{ optional($activeSchoolYear->enrollment_start_at)->format('m/d/Y h:i A') ?? 'N/A' }}
                to
                {{ optional($activeSchoolYear->enrollment_end_at)->format('m/d/Y h:i A') ?? 'N/A' }}
            </p>
        @endif
    @endif

    @if($latestApplication)
        <div class="enduser-status-strip">
            <div>
                <p class="muted">Latest Application Status</p>
                <span class="badge {{ $latestApplication->status }}">{{ \App\Support\StatusLabel::for($latestApplication->status) }}</span>
            </div>
            <div>
                <p class="muted">Latest Submitted</p>
                <strong>{{ optional($latestApplication->submitted_at)->format('m/d/Y h:i A') ?? 'Not available' }}</strong>
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
            <section class="enrollment-paper-wrap">
                <article class="enrollment-paper">
                    @include('enduser.partials.enrollment-paper-head', [
                        'application' => null,
                        'activeSchoolYear' => $activeSchoolYear,
                    ])
                    @include('enduser.partials.enrollment-form-fields', [
                        'application' => null,
                        'readonly' => false,
                    ])
                </article>
            </section>

            <div class="enrollment-actions enrollment-actions--center">
                <button class="btn" type="submit">Submit Application</button>
            </div>
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

    <div class="table-wrap table-wrap--stack mt-10">
        <table class="enduser-table--stack">
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
                    <td data-label="Learner">{{ $entry->learner_full_name }}</td>
                    <td data-label="Submitted">{{ optional($entry->submitted_at)->format('m/d/Y h:i A') ?? '-' }}</td>
                    <td data-label="Status"><span class="badge {{ $entry->status }}">{{ \App\Support\StatusLabel::for($entry->status) }}</span></td>
                    <td data-label="Grade Level">{{ $entry->grade_level }}</td>
                    <td data-label="Action"><a class="btn btn-secondary" href="{{ route('applications.show', $entry) }}">Open Timeline</a></td>
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

    const bindDisabilitySpecifyField = (form) => {
        const lwdRadios = form.querySelectorAll('input[name="is_lwd"]');
        const otherCheckbox = form.querySelector('input[name="disability_types[]"][value="other_disability"]');
        const specifyInput = form.querySelector('input[name="disability_specify"]');
        const specifyWrap = form.querySelector('[data-disability-specify-wrap]');

        if (!lwdRadios.length || !otherCheckbox || !specifyInput) {
            return;
        }

        const refresh = () => {
            const lwdChecked = form.querySelector('input[name="is_lwd"]:checked');
            const lwdEnabled = lwdChecked && lwdChecked.value === '1';
            const show = lwdEnabled && otherCheckbox.checked;

            specifyInput.disabled = !show;
            if (!show) {
                specifyInput.value = '';
            }
            if (specifyWrap) {
                specifyWrap.hidden = !show;
            }
        };

        lwdRadios.forEach((radio) => radio.addEventListener('change', refresh));
        otherCheckbox.addEventListener('change', refresh);
        refresh();
    };

    const bindSameAddressField = (form) => {
        const checkbox = form.querySelector('input[name="permanent_same_as_current"]');
        if (!checkbox) {
            return;
        }

        const mappings = [
            ['current_house_no', 'permanent_house_no'],
            ['current_street', 'permanent_street'],
            ['current_barangay', 'permanent_barangay'],
            ['current_municipality', 'permanent_municipality'],
            ['current_province', 'permanent_province'],
            ['current_country', 'permanent_country'],
            ['current_zip_code', 'permanent_zip_code'],
        ];

        const currentFields = mappings
            .map(([from]) => form.querySelector(`input[name="${from}"]`))
            .filter(Boolean);
        const permanentFields = mappings
            .map(([, to]) => form.querySelector(`input[name="${to}"]`))
            .filter(Boolean);

        if (!currentFields.length || permanentFields.length !== mappings.length) {
            return;
        }

        const copy = () => {
            mappings.forEach(([from, to]) => {
                const fromField = form.querySelector(`input[name="${from}"]`);
                const toField = form.querySelector(`input[name="${to}"]`);
                if (!fromField || !toField) {
                    return;
                }
                toField.value = fromField.value;
            });
        };

        const refresh = () => {
            const enabled = checkbox.checked;
            permanentFields.forEach((field) => {
                field.readOnly = enabled;
            });
            if (enabled) {
                copy();
            }
        };

        checkbox.addEventListener('change', refresh);
        currentFields.forEach((field) => {
            field.addEventListener('input', () => {
                if (checkbox.checked) {
                    copy();
                }
            });
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
        bindDisabilitySpecifyField(form);
        bindSameAddressField(form);
    });
})();
</script>
@endsection
