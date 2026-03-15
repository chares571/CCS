<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\Document;
use App\Models\SchoolYear;
use App\Support\ApplicationStatusReasoner;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    private const GRADE_LEVELS = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];

    private const DISABILITY_TYPES = [
        'visual_impairment',
        'hearing_impairment',
        'learning_disability',
        'intellectual_disability',
        'autism_spectrum_disorder',
        'emotional_behavioral_disorder',
        'orthopedic_physical_handicap',
        'speech_language_disorder',
        'cerebral_palsy',
        'special_health_problem',
        'multiple_disorder',
        'other_disability',
    ];

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['parent', 'student'], true), 403);
        $activeSchoolYears = SchoolYear::where('is_active', true)->get();
        if ($activeSchoolYears->count() !== 1) {
            return back()->withErrors(['application' => 'Enrollment is unavailable. Please contact the administrator.']);
        }

        $schoolYear = $activeSchoolYears->first();
        if (!$schoolYear || !$schoolYear->isEnrollmentOpenNow()) {
            return back()->withErrors(['application' => 'Enrollment is closed.']);
        }

        if ($request->user()->role === 'student') {
            $alreadySubmitted = Application::query()
                ->where('user_id', $request->user()->id)
                ->where('school_year_id', $schoolYear->id)
                ->exists();

            if ($alreadySubmitted) {
                return back()->withErrors([
                    'application' => 'You already submitted an enrollment for this school year.',
                ]);
            }
        }

        $validated = $request->validate($this->rules());

        $application = Application::create(array_merge(
            $this->payloadFromValidated($validated),
            [
            'user_id' => $request->user()->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'pending',
            'submitted_at' => now(),
            ]
        ));

        if ($request->hasFile('supporting_image')) {
            $file = $request->file('supporting_image');
            $path = $file->store('applications', 'public');

            Document::create([
                'application_id' => $application->id,
                'type' => 'supporting_image',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
            ]);
        }

        ApplicationStatusLog::create([
            'application_id' => $application->id,
            'changed_by' => $request->user()->id,
            'status' => 'pending',
            'remarks' => 'Application submitted.',
            'changed_at' => now(),
        ]);

        AuditLogger::log('application_submitted', 'application', $application->id);

        return redirect()->route('homepage.enrollment')->with('success', 'Application submitted successfully.');
    }

    public function show(Application $application)
    {
        abort_unless(in_array(auth()->user()->role, ['parent', 'student'], true), 403);
        abort_if($application->user_id !== auth()->id(), 403);
        $application->load('statusLogs.changedBy', 'documents');
        return view('enduser.application-show', compact('application'));
    }

    public function update(Request $request, Application $application)
    {
        abort_unless(in_array($request->user()->role, ['parent', 'student'], true), 403);
        abort_if($application->user_id !== auth()->id(), 403);

        if (!$application->canBeEditedByApplicant()) {
            return back()->withErrors([
                'application' => 'Editing is allowed only while your application is still in '.ApplicationStatusReasoner::PENDING.' status.',
            ]);
        }

        $validated = $request->validate($this->rules());

        $application->update($this->payloadFromValidated($validated));

        if ($request->hasFile('supporting_image')) {
            $file = $request->file('supporting_image');
            $path = $file->store('applications', 'public');

            Document::create([
                'application_id' => $application->id,
                'type' => 'supporting_image',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
            ]);
        }
        AuditLogger::log('application_updated', 'application', $application->id);

        return redirect()->route('homepage.enrollment')->with('success', 'Application updated.');
    }

    private function rules(): array
    {
        return [
            'grade_level' => 'required|in:'.implode(',', self::GRADE_LEVELS),
            'with_lrn' => 'required|boolean',
            'lrn' => 'nullable|string|max:20|required_if:with_lrn,1',
            'returning_learner' => 'required|boolean',
            'psa_birth_certificate_no' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'birthdate' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'mother_tongue' => 'nullable|string|max:255',
            'has_ip_affiliation' => 'required|boolean',
            'ip_affiliation' => 'nullable|string|max:255|required_if:has_ip_affiliation,1',
            'is_4ps_beneficiary' => 'required|boolean',
            'four_ps_household_id' => 'nullable|string|max:255|required_if:is_4ps_beneficiary,1',
            'is_lwd' => 'required|boolean',
            'disability_types' => 'nullable|array',
            'disability_types.*' => 'in:'.implode(',', self::DISABILITY_TYPES),
            'disability_specify' => 'nullable|string|max:255',
            'current_house_no' => 'nullable|string|max:255',
            'current_street' => 'nullable|string|max:255',
            'current_barangay' => 'nullable|string|max:255',
            'current_municipality' => 'nullable|string|max:255',
            'current_province' => 'nullable|string|max:255',
            'current_country' => 'nullable|string|max:255',
            'current_zip_code' => 'nullable|string|max:20',
            'permanent_house_no' => 'nullable|string|max:255',
            'permanent_street' => 'nullable|string|max:255',
            'permanent_barangay' => 'nullable|string|max:255',
            'permanent_municipality' => 'nullable|string|max:255',
            'permanent_province' => 'nullable|string|max:255',
            'permanent_country' => 'nullable|string|max:255',
            'permanent_zip_code' => 'nullable|string|max:20',
            'father_last_name' => 'nullable|string|max:255',
            'father_first_name' => 'nullable|string|max:255',
            'father_middle_name' => 'nullable|string|max:255',
            'father_contact_number' => 'nullable|string|max:50',
            'mother_last_name' => 'nullable|string|max:255',
            'mother_first_name' => 'nullable|string|max:255',
            'mother_middle_name' => 'nullable|string|max:255',
            'mother_contact_number' => 'nullable|string|max:50',
            'guardian_last_name' => 'nullable|string|max:255',
            'guardian_first_name' => 'nullable|string|max:255',
            'guardian_middle_name' => 'nullable|string|max:255',
            'guardian_contact_number' => 'nullable|string|max:50',
            'supporting_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    private function payloadFromValidated(array $validated): array
    {
        $middle = $this->upperOrEmpty($validated['middle_name'] ?? null);
        $fullName = trim(
            $this->upperOrEmpty($validated['last_name'] ?? null).', '.$this->upperOrEmpty($validated['first_name'] ?? null).($middle !== '' ? ' '.$middle : '')
        );

        $disabilityTypes = $validated['is_lwd'] ? ($validated['disability_types'] ?? []) : [];
        $shouldStoreDisabilitySpecify = $validated['is_lwd'] && in_array('other_disability', $disabilityTypes, true);

        return [
            'learner_full_name' => $fullName,
            'grade_level' => $validated['grade_level'],
            'with_lrn' => (bool) $validated['with_lrn'],
            'lrn' => $validated['with_lrn'] ? $this->upperOrNull($validated['lrn'] ?? null) : null,
            'returning_learner' => (bool) $validated['returning_learner'],
            'psa_birth_certificate_no' => $this->upperOrNull($validated['psa_birth_certificate_no'] ?? null),
            'last_name' => $this->upperOrEmpty($validated['last_name'] ?? null),
            'first_name' => $this->upperOrEmpty($validated['first_name'] ?? null),
            'middle_name' => $this->upperOrNull($validated['middle_name'] ?? null),
            'birthdate' => $validated['birthdate'],
            'place_of_birth' => $this->upperOrEmpty($validated['place_of_birth'] ?? null),
            'gender' => $validated['gender'],
            'mother_tongue' => $this->upperOrNull($validated['mother_tongue'] ?? null),
            'has_ip_affiliation' => (bool) $validated['has_ip_affiliation'],
            'ip_affiliation' => $validated['has_ip_affiliation'] ? $this->upperOrNull($validated['ip_affiliation'] ?? null) : null,
            'is_4ps_beneficiary' => (bool) $validated['is_4ps_beneficiary'],
            'four_ps_household_id' => $validated['is_4ps_beneficiary'] ? $this->upperOrNull($validated['four_ps_household_id'] ?? null) : null,
            'is_lwd' => (bool) $validated['is_lwd'],
            'disability_types' => $disabilityTypes,
            'disability_specify' => $shouldStoreDisabilitySpecify ? $this->upperOrNull($validated['disability_specify'] ?? null) : null,
            'current_house_no' => $this->upperOrNull($validated['current_house_no'] ?? null),
            'current_street' => $this->upperOrNull($validated['current_street'] ?? null),
            'current_barangay' => $this->upperOrNull($validated['current_barangay'] ?? null),
            'current_municipality' => $this->upperOrNull($validated['current_municipality'] ?? null),
            'current_province' => $this->upperOrNull($validated['current_province'] ?? null),
            'current_country' => $this->upperOrNull($validated['current_country'] ?? null),
            'current_zip_code' => $this->upperOrNull($validated['current_zip_code'] ?? null),
            'permanent_house_no' => $this->upperOrNull($validated['permanent_house_no'] ?? null),
            'permanent_street' => $this->upperOrNull($validated['permanent_street'] ?? null),
            'permanent_barangay' => $this->upperOrNull($validated['permanent_barangay'] ?? null),
            'permanent_municipality' => $this->upperOrNull($validated['permanent_municipality'] ?? null),
            'permanent_province' => $this->upperOrNull($validated['permanent_province'] ?? null),
            'permanent_country' => $this->upperOrNull($validated['permanent_country'] ?? null),
            'permanent_zip_code' => $this->upperOrNull($validated['permanent_zip_code'] ?? null),
            'father_last_name' => $this->upperOrNull($validated['father_last_name'] ?? null),
            'father_first_name' => $this->upperOrNull($validated['father_first_name'] ?? null),
            'father_middle_name' => $this->upperOrNull($validated['father_middle_name'] ?? null),
            'father_contact_number' => $this->upperOrNull($validated['father_contact_number'] ?? null),
            'mother_last_name' => $this->upperOrNull($validated['mother_last_name'] ?? null),
            'mother_first_name' => $this->upperOrNull($validated['mother_first_name'] ?? null),
            'mother_middle_name' => $this->upperOrNull($validated['mother_middle_name'] ?? null),
            'mother_contact_number' => $this->upperOrNull($validated['mother_contact_number'] ?? null),
            'guardian_last_name' => $this->upperOrNull($validated['guardian_last_name'] ?? null),
            'guardian_first_name' => $this->upperOrNull($validated['guardian_first_name'] ?? null),
            'guardian_middle_name' => $this->upperOrNull($validated['guardian_middle_name'] ?? null),
            'guardian_contact_number' => $this->upperOrNull($validated['guardian_contact_number'] ?? null),
        ];
    }

    private function upperOrNull(?string $value): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return strtoupper($normalized);
    }

    private function upperOrEmpty(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
