@php
    $application = $application ?? null;
    $receivedDate = optional($application?->submitted_at)->format('m/d/Y') ?? now()->format('m/d/Y');
    $schoolYear = $application?->schoolYear->year ?? $application?->schoolYear->name ?? 'N/A';
    $grade = $application?->grade_level ?? 'N/A';
    $isChecked = static fn (bool $value): string => $value ? '[x]' : '[ ]';
    $lineValue = static fn (?string $value): string => trim((string) $value) !== '' ? trim((string) $value) : '____________________________';
@endphp

<section class="hardcopy-print-form print-only">
    <div class="hpf-head">
        <div class="hpf-logo">
            <img src="{{ asset('images/branding/CCS_logo.png') }}" alt="School seal logo">
        </div>
        <div class="hpf-title">
            <h1>BASIC EDUCATION ENROLLMENT FORM</h1>
            <p>THIS FORM IS NOT FOR SALE</p>
        </div>
        <div class="hpf-annex">
            <small>Received as of {{ $receivedDate }}</small>
            <strong>ANNEX 1</strong>
        </div>
    </div>

    <div class="hpf-top-grid">
        <div class="hpf-box">
            <label>School Year</label>
            <div>{{ $schoolYear }}</div>
        </div>
        <div class="hpf-box">
            <label>Grade to Enroll</label>
            <div>{{ $grade }}</div>
        </div>
        <div class="hpf-box hpf-wide">
            <label>Check the appropriate box only</label>
            <div>
                1. With LRN? {{ $isChecked((bool) $application?->with_lrn) }} Yes {{ $isChecked(!(bool) $application?->with_lrn) }} No
                &nbsp;&nbsp;&nbsp; 2. Returning (Balik-Aral)? {{ $isChecked((bool) $application?->returning_learner) }} Yes {{ $isChecked(!(bool) $application?->returning_learner) }} No
            </div>
        </div>
    </div>

    <div class="hpf-instruction">
        <strong>INSTRUCTIONS:</strong>
        Print legibly all information required in CAPITAL letters. Submit accomplished form to the school.
    </div>

    <div class="hpf-section-title">LEARNER INFORMATION</div>

    <div class="hpf-row two">
        <div class="hpf-field"><label>PSA Birth Certificate No. (if available upon registration)</label><div>{{ $lineValue($application?->psa_birth_certificate_no) }}</div></div>
        <div class="hpf-field"><label>Learner Reference No. (LRN)</label><div>{{ $lineValue($application?->lrn) }}</div></div>
    </div>

    <div class="hpf-row three">
        <div class="hpf-field"><label>Last Name</label><div>{{ $lineValue($application?->last_name) }}</div></div>
        <div class="hpf-field"><label>Birthdate (mm/dd/yyyy)</label><div>{{ optional($application?->birthdate)->format('m/d/Y') ?? '____________________________' }}</div></div>
        <div class="hpf-field"><label>Place of Birth (Municipality/City)</label><div>{{ $lineValue($application?->place_of_birth) }}</div></div>
    </div>

    <div class="hpf-row three">
        <div class="hpf-field"><label>First Name</label><div>{{ $lineValue($application?->first_name) }}</div></div>
        <div class="hpf-field"><label>Sex</label><div>{{ $isChecked(($application?->gender ?? '') === 'male') }} Male &nbsp; {{ $isChecked(($application?->gender ?? '') === 'female') }} Female</div></div>
        <div class="hpf-field"><label>Mother Tongue</label><div>{{ $lineValue($application?->mother_tongue) }}</div></div>
    </div>

    <div class="hpf-row two">
        <div class="hpf-field"><label>Middle Name</label><div>{{ $lineValue($application?->middle_name) }}</div></div>
        <div class="hpf-field"><label>Belonging to IP Community</label><div>{{ $isChecked((bool) $application?->has_ip_affiliation) }} Yes &nbsp; {{ $isChecked(!(bool) $application?->has_ip_affiliation) }} No &nbsp; If Yes, specify: {{ $lineValue($application?->ip_affiliation) }}</div></div>
    </div>

    <div class="hpf-row two">
        <div class="hpf-field"><label>4Ps Beneficiary</label><div>{{ $isChecked((bool) $application?->is_4ps_beneficiary) }} Yes &nbsp; {{ $isChecked(!(bool) $application?->is_4ps_beneficiary) }} No</div></div>
        <div class="hpf-field"><label>4Ps Household ID Number</label><div>{{ $lineValue($application?->four_ps_household_id) }}</div></div>
    </div>

    <div class="hpf-disability">
        <div class="hpf-field"><label>Is the child a Learner with Disability?</label><div>{{ $isChecked((bool) $application?->is_lwd) }} Yes &nbsp; {{ $isChecked(!(bool) $application?->is_lwd) }} No</div></div>
        <div class="hpf-check-grid">
            @php $types = $application?->disability_types ?? []; @endphp
            <span>{{ $isChecked(in_array('visual_impairment', $types, true)) }} Visual Impairment</span>
            <span>{{ $isChecked(in_array('hearing_impairment', $types, true)) }} Hearing Impairment</span>
            <span>{{ $isChecked(in_array('learning_disability', $types, true)) }} Learning Disability</span>
            <span>{{ $isChecked(in_array('intellectual_disability', $types, true)) }} Intellectual Disability</span>
            <span>{{ $isChecked(in_array('autism_spectrum_disorder', $types, true)) }} Autism Spectrum Disorder</span>
            <span>{{ $isChecked(in_array('emotional_behavioral_disorder', $types, true)) }} Emotional Behavioral Disorder</span>
            <span>{{ $isChecked(in_array('orthopedic_physical_handicap', $types, true)) }} Orthopedic/Physical Handicap</span>
            <span>{{ $isChecked(in_array('speech_language_disorder', $types, true)) }} Speech/Language Disorder</span>
            <span>{{ $isChecked(in_array('cerebral_palsy', $types, true)) }} Cerebral Palsy</span>
            <span>{{ $isChecked(in_array('special_health_problem', $types, true)) }} Special Health Problem/Chronic Disease</span>
            <span>{{ $isChecked(in_array('multiple_disorder', $types, true)) }} Multiple Disorder</span>
            <span>{{ $isChecked(in_array('other_disability', $types, true)) }} Others</span>
        </div>
        <div class="hpf-field"><label>Specify</label><div>{{ $lineValue(in_array('other_disability', $types, true) ? ($application?->disability_specify ?? null) : null) }}</div></div>
    </div>

    <div class="hpf-section-title">CURRENT ADDRESS</div>
    <div class="hpf-row four">
        <div class="hpf-field"><label>House No./Street</label><div>{{ $lineValue(trim(($application?->current_house_no ?? '').' '.($application?->current_street ?? ''))) }}</div></div>
        <div class="hpf-field"><label>Barangay</label><div>{{ $lineValue($application?->current_barangay) }}</div></div>
        <div class="hpf-field"><label>Municipality/City</label><div>{{ $lineValue($application?->current_municipality) }}</div></div>
        <div class="hpf-field"><label>Province / Country / Zip</label><div>{{ $lineValue(trim(($application?->current_province ?? '').' / '.($application?->current_country ?? '').' / '.($application?->current_zip_code ?? ''))) }}</div></div>
    </div>

    <div class="hpf-section-title">PERMANENT ADDRESS</div>
    <div class="hpf-row four">
        <div class="hpf-field"><label>House No./Street</label><div>{{ $lineValue(trim(($application?->permanent_house_no ?? '').' '.($application?->permanent_street ?? ''))) }}</div></div>
        <div class="hpf-field"><label>Barangay</label><div>{{ $lineValue($application?->permanent_barangay) }}</div></div>
        <div class="hpf-field"><label>Municipality/City</label><div>{{ $lineValue($application?->permanent_municipality) }}</div></div>
        <div class="hpf-field"><label>Province / Country / Zip</label><div>{{ $lineValue(trim(($application?->permanent_province ?? '').' / '.($application?->permanent_country ?? '').' / '.($application?->permanent_zip_code ?? ''))) }}</div></div>
    </div>

    <div class="hpf-section-title">PARENT/GUARDIAN'S INFORMATION</div>
    <div class="hpf-row four">
        <div class="hpf-field"><label>Father's Last Name</label><div>{{ $lineValue($application?->father_last_name) }}</div></div>
        <div class="hpf-field"><label>Father's First Name</label><div>{{ $lineValue($application?->father_first_name) }}</div></div>
        <div class="hpf-field"><label>Father's Middle Name</label><div>{{ $lineValue($application?->father_middle_name) }}</div></div>
        <div class="hpf-field"><label>Contact Number</label><div>{{ $lineValue($application?->father_contact_number) }}</div></div>
    </div>
    <div class="hpf-row four">
        <div class="hpf-field"><label>Mother's Last Name</label><div>{{ $lineValue($application?->mother_last_name) }}</div></div>
        <div class="hpf-field"><label>Mother's First Name</label><div>{{ $lineValue($application?->mother_first_name) }}</div></div>
        <div class="hpf-field"><label>Mother's Middle Name</label><div>{{ $lineValue($application?->mother_middle_name) }}</div></div>
        <div class="hpf-field"><label>Contact Number</label><div>{{ $lineValue($application?->mother_contact_number) }}</div></div>
    </div>
    <div class="hpf-row four">
        <div class="hpf-field"><label>Guardian's Last Name</label><div>{{ $lineValue($application?->guardian_last_name) }}</div></div>
        <div class="hpf-field"><label>Guardian's First Name</label><div>{{ $lineValue($application?->guardian_first_name) }}</div></div>
        <div class="hpf-field"><label>Guardian's Middle Name</label><div>{{ $lineValue($application?->guardian_middle_name) }}</div></div>
        <div class="hpf-field"><label>Contact Number</label><div>{{ $lineValue($application?->guardian_contact_number) }}</div></div>
    </div>
</section>
