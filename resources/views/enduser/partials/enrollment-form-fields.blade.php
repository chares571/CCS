@php
    $application = $application ?? null;
    $readonly = $readonly ?? false;
    $disabled = $readonly ? 'disabled' : '';
    $gradeLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
    $selectedDisabilities = old('disability_types', $application->disability_types ?? []);
    if (!is_array($selectedDisabilities)) {
        $selectedDisabilities = [];
    }
@endphp

<div class="enrollment-instruction-line">
    <strong>INSTRUCTIONS:</strong> Print legibly all information required in <strong>CAPITAL</strong> letters.
</div>

<div class="enrollment-form-body">
<div class="enrollment-form-grid enrollment-form-two">
    <div>
        <label>Grade Level</label>
        <select name="grade_level" {{ $disabled }} required>
            <option value="">Select Grade</option>
            @foreach($gradeLevels as $level)
                <option value="{{ $level }}" {{ old('grade_level', $application->grade_level ?? '') === $level ? 'selected' : '' }}>{{ $level }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>PSA Birth Certificate No. (optional)</label>
        <input type="text" name="psa_birth_certificate_no" value="{{ old('psa_birth_certificate_no', $application->psa_birth_certificate_no ?? '') }}" {{ $disabled }}>
    </div>
</div>

<div class="enrollment-inline-options enrollment-inline-options--two">
    <div class="enrollment-inline-stack">
        <div class="enrollment-radio-line">
            <span>With LRN?</span>
            <label><input type="radio" name="with_lrn" value="1" {{ (string) old('with_lrn', isset($application) ? (int) $application->with_lrn : '') === '1' ? 'checked' : '' }} {{ $disabled }} required> Yes</label>
            <label><input type="radio" name="with_lrn" value="0" {{ (string) old('with_lrn', isset($application) ? (int) $application->with_lrn : '') === '0' ? 'checked' : '' }} {{ $disabled }} required> No</label>
        </div>
        <div class="enrollment-inline-field">
            <label>LRN (if applicable)</label>
            <input type="text" name="lrn" value="{{ old('lrn', $application->lrn ?? '') }}" {{ $disabled }}>
        </div>
    </div>
    <div class="enrollment-radio-line">
        <span>Returning Learner?</span>
        <label><input type="radio" name="returning_learner" value="1" {{ (string) old('returning_learner', isset($application) ? (int) $application->returning_learner : '') === '1' ? 'checked' : '' }} {{ $disabled }} required> Yes</label>
        <label><input type="radio" name="returning_learner" value="0" {{ (string) old('returning_learner', isset($application) ? (int) $application->returning_learner : '') === '0' ? 'checked' : '' }} {{ $disabled }} required> No</label>
    </div>
</div>

<div class="enrollment-section-title">Learner Information</div>
<div class="enrollment-form-grid enrollment-form-three">
    <div>
        <label>Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $application->last_name ?? '') }}" {{ $disabled }} required>
    </div>
    <div>
        <label>First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $application->first_name ?? '') }}" {{ $disabled }} required>
    </div>
    <div>
        <label>Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name', $application->middle_name ?? '') }}" {{ $disabled }}>
    </div>
</div>

<div class="enrollment-form-grid enrollment-form-three">
    <div>
        <label>Birthdate</label>
        <input type="date" lang="en-US" name="birthdate" value="{{ old('birthdate', optional($application->birthdate ?? null)->format('Y-m-d')) }}" {{ $disabled }} required>
    </div>
    <div>
        <label>Sex</label>
        <select name="gender" {{ $disabled }} required>
            <option value="">Select</option>
            <option value="male" {{ old('gender', $application->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
            <option value="female" {{ old('gender', $application->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
            <option value="other" {{ old('gender', $application->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>
    <div>
        <label>Mother Tongue</label>
        <input type="text" name="mother_tongue" value="{{ old('mother_tongue', $application->mother_tongue ?? '') }}" {{ $disabled }}>
    </div>
</div>

<div class="enrollment-form-grid">
    <div>
        <label>Place of Birth</label>
        <input type="text" name="place_of_birth" value="{{ old('place_of_birth', $application->place_of_birth ?? '') }}" {{ $disabled }} required>
    </div>
</div>

<div class="enrollment-inline-options enrollment-inline-options--two">
    <div class="enrollment-inline-stack">
        <div class="enrollment-radio-line">
            <span>Belonging to IP Community?</span>
            <label><input type="radio" name="has_ip_affiliation" value="1" {{ (string) old('has_ip_affiliation', isset($application) ? (int) $application->has_ip_affiliation : '') === '1' ? 'checked' : '' }} {{ $disabled }} required> Yes</label>
            <label><input type="radio" name="has_ip_affiliation" value="0" {{ (string) old('has_ip_affiliation', isset($application) ? (int) $application->has_ip_affiliation : '') === '0' ? 'checked' : '' }} {{ $disabled }} required> No</label>
        </div>
        <div class="enrollment-inline-field">
            <label>IP Affiliation (if Yes)</label>
            <input type="text" name="ip_affiliation" value="{{ old('ip_affiliation', $application->ip_affiliation ?? '') }}" {{ $disabled }}>
        </div>
    </div>
    <div class="enrollment-inline-stack">
        <div class="enrollment-radio-line">
            <span>4Ps Beneficiary?</span>
            <label><input type="radio" name="is_4ps_beneficiary" value="1" {{ (string) old('is_4ps_beneficiary', isset($application) ? (int) $application->is_4ps_beneficiary : '') === '1' ? 'checked' : '' }} {{ $disabled }} required> Yes</label>
            <label><input type="radio" name="is_4ps_beneficiary" value="0" {{ (string) old('is_4ps_beneficiary', isset($application) ? (int) $application->is_4ps_beneficiary : '') === '0' ? 'checked' : '' }} {{ $disabled }} required> No</label>
        </div>
        <div class="enrollment-inline-field">
            <label>4Ps Household ID No.</label>
            <input type="text" name="four_ps_household_id" value="{{ old('four_ps_household_id', $application->four_ps_household_id ?? '') }}" {{ $disabled }}>
        </div>
    </div>
</div>

<div class="enrollment-section-title">Learner with Disability</div>
<div class="enrollment-inline-options">
    <div class="enrollment-radio-line">
        <span>Is the child a learner with disability?</span>
        <label><input type="radio" name="is_lwd" value="1" {{ (string) old('is_lwd', isset($application) ? (int) $application->is_lwd : '') === '1' ? 'checked' : '' }} {{ $disabled }} required> Yes</label>
        <label><input type="radio" name="is_lwd" value="0" {{ (string) old('is_lwd', isset($application) ? (int) $application->is_lwd : '') === '0' ? 'checked' : '' }} {{ $disabled }} required> No</label>
    </div>
</div>

<div class="enrollment-checkbox-grid">
    @php($showDisabilitySpecify = in_array('other_disability', $selectedDisabilities, true))
    @foreach([
        'visual_impairment' => 'Visual Impairment',
        'hearing_impairment' => 'Hearing Impairment',
        'learning_disability' => 'Learning Disability',
        'intellectual_disability' => 'Intellectual Disability',
        'autism_spectrum_disorder' => 'Autism Spectrum Disorder',
        'emotional_behavioral_disorder' => 'Emotional Behavioral Disorder',
        'orthopedic_physical_handicap' => 'Orthopedic/Physical Handicap',
        'speech_language_disorder' => 'Speech/Language Disorder',
        'cerebral_palsy' => 'Cerebral Palsy',
        'special_health_problem' => 'Special Health Problem/Chronic Disease',
        'multiple_disorder' => 'Multiple Disorder',
        'other_disability' => 'Others',
    ] as $value => $label)
        @if($value === 'other_disability')
            <label class="enrollment-checkbox-other">
                <input type="checkbox" name="disability_types[]" value="{{ $value }}"
                    {{ in_array($value, $selectedDisabilities, true) ? 'checked' : '' }} {{ $disabled }}>
                <span>{{ $label }}</span>
                <span class="enrollment-checkbox-specify" data-disability-specify-wrap {{ $showDisabilitySpecify ? '' : 'hidden' }}>
                    <span class="enrollment-checkbox-specify-label">Specify:</span>
                    <input type="text" name="disability_specify" value="{{ old('disability_specify', $application->disability_specify ?? '') }}"
                        {{ $disabled }} {{ $showDisabilitySpecify ? '' : 'disabled' }}>
                </span>
            </label>
        @else
            <label>
                <input type="checkbox" name="disability_types[]" value="{{ $value }}"
                    {{ in_array($value, $selectedDisabilities, true) ? 'checked' : '' }} {{ $disabled }}>
                {{ $label }}
            </label>
        @endif
    @endforeach
</div>

<div class="enrollment-section-title">Current Address</div>
<div class="enrollment-form-grid enrollment-form-four">
    <div><label>House No.</label><input type="text" name="current_house_no" value="{{ old('current_house_no', $application->current_house_no ?? '') }}" {{ $disabled }}></div>
    <div><label>Street</label><input type="text" name="current_street" value="{{ old('current_street', $application->current_street ?? '') }}" {{ $disabled }}></div>
    <div><label>Barangay</label><input type="text" name="current_barangay" value="{{ old('current_barangay', $application->current_barangay ?? '') }}" {{ $disabled }}></div>
    <div><label>Municipality/City</label><input type="text" name="current_municipality" value="{{ old('current_municipality', $application->current_municipality ?? '') }}" {{ $disabled }}></div>
    <div><label>Province</label><input type="text" name="current_province" value="{{ old('current_province', $application->current_province ?? '') }}" {{ $disabled }}></div>
    <div><label>Country</label><input type="text" name="current_country" value="{{ old('current_country', $application->current_country ?? '') }}" {{ $disabled }}></div>
    <div><label>Zip Code</label><input type="text" name="current_zip_code" value="{{ old('current_zip_code', $application->current_zip_code ?? '') }}" {{ $disabled }}></div>
</div>

<div class="enrollment-section-title enrollment-section-title--with-toggle">
    <span>Permanent Address</span>
    <label class="enrollment-section-toggle print-hide">
        <input type="checkbox" name="permanent_same_as_current" value="1" {{ $disabled }}>
        <span>Same as Current Address</span>
    </label>
</div>
<div class="enrollment-form-grid enrollment-form-four">
    <div><label>House No.</label><input type="text" name="permanent_house_no" value="{{ old('permanent_house_no', $application->permanent_house_no ?? '') }}" {{ $disabled }}></div>
    <div><label>Street</label><input type="text" name="permanent_street" value="{{ old('permanent_street', $application->permanent_street ?? '') }}" {{ $disabled }}></div>
    <div><label>Barangay</label><input type="text" name="permanent_barangay" value="{{ old('permanent_barangay', $application->permanent_barangay ?? '') }}" {{ $disabled }}></div>
    <div><label>Municipality/City</label><input type="text" name="permanent_municipality" value="{{ old('permanent_municipality', $application->permanent_municipality ?? '') }}" {{ $disabled }}></div>
    <div><label>Province</label><input type="text" name="permanent_province" value="{{ old('permanent_province', $application->permanent_province ?? '') }}" {{ $disabled }}></div>
    <div><label>Country</label><input type="text" name="permanent_country" value="{{ old('permanent_country', $application->permanent_country ?? '') }}" {{ $disabled }}></div>
    <div><label>Zip Code</label><input type="text" name="permanent_zip_code" value="{{ old('permanent_zip_code', $application->permanent_zip_code ?? '') }}" {{ $disabled }}></div>
</div>

<div class="enrollment-section-title">Parent/Guardian Information</div>

<div class="enrollment-subtitle">Father's Name</div>
<div class="enrollment-form-grid enrollment-form-four">
    <div><label>Last Name</label><input type="text" name="father_last_name" value="{{ old('father_last_name', $application->father_last_name ?? '') }}" {{ $disabled }}></div>
    <div><label>First Name</label><input type="text" name="father_first_name" value="{{ old('father_first_name', $application->father_first_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Middle Name</label><input type="text" name="father_middle_name" value="{{ old('father_middle_name', $application->father_middle_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Contact Number</label><input type="text" name="father_contact_number" value="{{ old('father_contact_number', $application->father_contact_number ?? '') }}" {{ $disabled }}></div>
</div>

<div class="enrollment-subtitle">Mother's Maiden Name</div>
<div class="enrollment-form-grid enrollment-form-four">
    <div><label>Last Name</label><input type="text" name="mother_last_name" value="{{ old('mother_last_name', $application->mother_last_name ?? '') }}" {{ $disabled }}></div>
    <div><label>First Name</label><input type="text" name="mother_first_name" value="{{ old('mother_first_name', $application->mother_first_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Middle Name</label><input type="text" name="mother_middle_name" value="{{ old('mother_middle_name', $application->mother_middle_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Contact Number</label><input type="text" name="mother_contact_number" value="{{ old('mother_contact_number', $application->mother_contact_number ?? '') }}" {{ $disabled }}></div>
</div>

<div class="enrollment-subtitle">Legal Guardian's Name</div>
<div class="enrollment-form-grid enrollment-form-four">
    <div><label>Last Name</label><input type="text" name="guardian_last_name" value="{{ old('guardian_last_name', $application->guardian_last_name ?? '') }}" {{ $disabled }}></div>
    <div><label>First Name</label><input type="text" name="guardian_first_name" value="{{ old('guardian_first_name', $application->guardian_first_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Middle Name</label><input type="text" name="guardian_middle_name" value="{{ old('guardian_middle_name', $application->guardian_middle_name ?? '') }}" {{ $disabled }}></div>
    <div><label>Contact Number</label><input type="text" name="guardian_contact_number" value="{{ old('guardian_contact_number', $application->guardian_contact_number ?? '') }}" {{ $disabled }}></div>
</div>
</div>
