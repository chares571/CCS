<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusLog;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardController extends Controller
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

    public function index()
    {
        $byGrade = array_fill_keys(self::GRADE_LEVELS, 0);
        foreach (Application::query()
            ->selectRaw('grade_level, COUNT(*) as total')
            ->whereIn('grade_level', self::GRADE_LEVELS)
            ->groupBy('grade_level')
            ->pluck('total', 'grade_level')
            ->all() as $grade => $total) {
            $byGrade[(string) $grade] = (int) $total;
        }

        $byGradeGender = [];
        foreach (self::GRADE_LEVELS as $grade) {
            $byGradeGender[$grade] = ['male' => 0, 'female' => 0];
        }

        foreach (Application::query()
            ->selectRaw("grade_level, COALESCE(NULLIF(gender, ''), 'unspecified') as gender, COUNT(*) as total")
            ->whereIn('grade_level', self::GRADE_LEVELS)
            ->groupBy('grade_level', 'gender')
            ->get()
            ->all() as $row) {
            $grade = (string) $row->grade_level;
            $gender = (string) $row->gender;
            $total = (int) $row->total;

            if (!isset($byGradeGender[$grade])) {
                continue;
            }

            if ($gender === 'male') {
                $byGradeGender[$grade]['male'] += $total;
                continue;
            }

            if ($gender === 'female') {
                $byGradeGender[$grade]['female'] += $total;
            }
        }

        $stats = [
            'total' => Application::count(),
            'pending' => Application::where('status', 'pending')->count(),
            'reviewed' => Application::where('status', 'reviewed')->count(),
            'approved' => Application::where('status', 'approved')->count(),
            'by_grade' => $byGrade,
            'by_grade_gender' => $byGradeGender,
        ];

        $genderStats = Application::selectRaw("COALESCE(NULLIF(gender, ''), 'unspecified') as gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $notificationCount = (int) $stats['pending'];

        return view('admin.dashboard', compact('stats', 'genderStats', 'notificationCount'));
    }

    public function applications(Request $request)
    {
        $nameFilter = trim((string) $request->input('name', ''));
        $hasFilter = $nameFilter !== '';

        $applicationsQuery = Application::query()->where('status', 'reviewed');
        $this->applyLearnerNameFilter($applicationsQuery, $nameFilter);

        $applications = $applicationsQuery
            ->latest('reviewed_at')
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $matchedCount = $applications->total();

        return view('admin.applications', compact('applications', 'nameFilter', 'hasFilter', 'matchedCount'));
    }

    public function monitoring(Request $request)
    {
        $nameFilter = $this->normalizeFilterInput((string) $request->input('name', ''));
        $selectedGrade = $this->resolveSelectedGrade((string) $request->input('grade', ''));
        $selectedStatus = (string) $request->input('status', 'all');
        if (!in_array($selectedStatus, ['all', 'pending', 'reviewed'], true)) {
            $selectedStatus = 'all';
        }
        $hasFilter = $nameFilter !== '';
        $statusForcesAllGrades = in_array($selectedStatus, ['pending', 'reviewed'], true);
        $showAllGrades = $hasFilter || $statusForcesAllGrades;

        $applicationsQuery = Application::query()
            ->whereIn('grade_level', self::GRADE_LEVELS);

        if ($selectedStatus !== 'all') {
            $applicationsQuery->where('status', $selectedStatus);
        }

        if (!$showAllGrades) {
            $applicationsQuery->where('grade_level', $selectedGrade);
        }

        if ($hasFilter) {
            $this->applyLearnerNameFilter($applicationsQuery, $nameFilter);
        }

        $applications = $applicationsQuery
            ->latest('submitted_at')
            ->latest('created_at')
            ->get();

        $visibleGrades = $showAllGrades ? self::GRADE_LEVELS : [$selectedGrade];
        $applicationsByGrade = collect($visibleGrades)
            ->mapWithKeys(fn ($grade) => [$grade => $applications->where('grade_level', $grade)->values()]);

        if ($showAllGrades) {
            $applicationsByGrade = $applicationsByGrade->filter(fn ($items) => $items->isNotEmpty());
        }

        if ($showAllGrades && $applicationsByGrade->isNotEmpty() && !$applicationsByGrade->has($selectedGrade)) {
            $selectedGrade = (string) $applicationsByGrade->keys()->first();
        }

        $matchedCount = $applications->count();
        $gradeLevels = self::GRADE_LEVELS;

        return view('admin.monitoring', compact('applicationsByGrade', 'nameFilter', 'matchedCount', 'hasFilter', 'selectedGrade', 'gradeLevels', 'selectedStatus', 'showAllGrades'));
    }

    public function showMonitoringApplication(Application $application)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.monitoring-show', compact('application'));
    }

    public function enrolledStudents(Request $request)
    {
        $nameFilter = $this->normalizeFilterInput((string) $request->input('name', ''));
        $selectedGrade = $this->resolveSelectedGrade((string) $request->input('grade', ''));
        $hasFilter = $nameFilter !== '';

        $gradeOrderCase = "CASE grade_level
            WHEN 'Kindergarten' THEN 0
            WHEN 'Grade 1' THEN 1
            WHEN 'Grade 2' THEN 2
            WHEN 'Grade 3' THEN 3
            WHEN 'Grade 4' THEN 4
            WHEN 'Grade 5' THEN 5
            WHEN 'Grade 6' THEN 6
            ELSE 99
        END";

        $sexOrderCase = "CASE
            WHEN gender = 'male' THEN 1
            WHEN gender = 'female' THEN 2
            ELSE 3
        END";

        $approvedApplicationsQuery = Application::query()
            ->where('status', 'approved')
            ->whereIn('grade_level', self::GRADE_LEVELS)
            ->orderByRaw($gradeOrderCase)
            ->orderByRaw($sexOrderCase)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('learner_full_name');

        if (!$hasFilter) {
            $approvedApplicationsQuery->where('grade_level', $selectedGrade);
        }

        $this->applyLearnerNameFilter($approvedApplicationsQuery, $nameFilter);
        $approvedApplications = $approvedApplicationsQuery->get();
        $matchedCount = $approvedApplications->count();

        $visibleGrades = $hasFilter ? self::GRADE_LEVELS : [$selectedGrade];
        $enrolledByGrade = collect($visibleGrades)
            ->mapWithKeys(function (string $grade) use ($approvedApplications) {
                $gradeItems = $approvedApplications->where('grade_level', $grade)->values();

                return [$grade => [
                    'male' => $gradeItems->where('gender', 'male')->values(),
                    'female' => $gradeItems->where('gender', 'female')->values(),
                    'other' => $gradeItems->whereNotIn('gender', ['male', 'female'])->values(),
                ]];
            })
            ->filter(fn (array $group) => $group['male']->isNotEmpty() || $group['female']->isNotEmpty() || $group['other']->isNotEmpty());

        if ($hasFilter && $enrolledByGrade->isNotEmpty() && !$enrolledByGrade->has($selectedGrade)) {
            $selectedGrade = (string) $enrolledByGrade->keys()->first();
        }

        $gradeLevels = self::GRADE_LEVELS;

        return view('admin.enrolled-students', compact('enrolledByGrade', 'nameFilter', 'hasFilter', 'matchedCount', 'selectedGrade', 'gradeLevels'));
    }

    public function createOfflineEnrollment()
    {
        $activeSchoolYear = SchoolYear::query()->where('is_active', true)->first();
        if (!$activeSchoolYear || !$activeSchoolYear->isEnrollmentOpenNow()) {
            return redirect()
                ->route('admin.monitoring')
                ->withErrors(['application' => 'Enrollment is currently closed for the active school year.']);
        }

        $accounts = User::query()
            ->whereIn('role', ['parent', 'student'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'role', 'username', 'email']);

        return view('admin.offline-enrollment-create', compact('accounts', 'activeSchoolYear'));
    }

    public function storeOfflineEnrollment(Request $request)
    {
        $activeSchoolYear = SchoolYear::query()->where('is_active', true)->first();
        if (!$activeSchoolYear || !$activeSchoolYear->isEnrollmentOpenNow()) {
            return redirect()
                ->route('admin.monitoring')
                ->withErrors(['application' => 'Enrollment is currently closed for the active school year.']);
        }

        $validated = $request->validate(array_merge([
            'account_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ], $this->enrollmentFormRules()));

        $accountId = (int) ($validated['account_user_id'] ?? 0);
        $account = null;

        if ($accountId > 0) {
            $account = User::query()
                ->whereKey($accountId)
                ->whereIn('role', ['parent', 'student'])
                ->where('is_active', true)
                ->first();
        }

        if (!$account) {
            $account = User::query()
                ->whereIn('role', ['parent', 'student'])
                ->where('is_active', true)
                ->orderBy('full_name')
                ->first();
        }

        if (!$account) {
            return back()->withErrors(['application' => 'No active parent/student account available for automatic linking.'])->withInput();
        }

        $application = DB::transaction(function () use ($validated, $account, $activeSchoolYear) {
            $created = Application::query()->create(array_merge(
                $this->payloadFromValidated($validated),
                [
                    'user_id' => $account->id,
                    'school_year_id' => $activeSchoolYear->id,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]
            ));

            ApplicationStatusLog::create([
                'application_id' => $created->id,
                'changed_by' => auth()->id(),
                'status' => 'pending',
                'remarks' => 'Encoded by admin from hardcopy enrollment form.',
                'changed_at' => now(),
            ]);

            return $created;
        });

        AuditLogger::log('application_encoded_by_admin', 'application', $application->id, [
            'encoded_for_user_id' => $account->id,
            'encoded_for_role' => $account->role,
            'school_year_id' => $activeSchoolYear->id,
        ]);

        return redirect()
            ->route('admin.monitoring.show', $application)
            ->with('success', 'Hardcopy enrollment form encoded successfully.');
    }

    private function applyLearnerNameFilter(Builder $query, string $nameFilter): void
    {
        $nameFilter = $this->normalizeFilterInput($nameFilter);
        if ($nameFilter === '') {
            return;
        }

        $like = '%'.$nameFilter.'%';
        $nameTokens = array_values(array_filter(preg_split('/[\s,]+/', $nameFilter) ?: []));

        $query->where(function ($innerQuery) use ($like, $nameTokens) {
            $innerQuery->where('learner_full_name', 'like', $like)
                ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", [$like])
                ->orWhereRaw("TRIM(CONCAT_WS(' ', last_name, first_name, middle_name)) LIKE ?", [$like])
                ->orWhere('last_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('lrn', 'like', $like);

            foreach ($nameTokens as $token) {
                $tokenLike = '%'.$token.'%';
                $innerQuery->orWhere(function ($tokenQuery) use ($token) {
                    $tokenLike = '%'.$token.'%';
                    $tokenQuery->where('learner_full_name', 'like', $tokenLike)
                        ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", [$tokenLike])
                        ->orWhereRaw("TRIM(CONCAT_WS(' ', last_name, first_name, middle_name)) LIKE ?", [$tokenLike])
                        ->orWhere('last_name', 'like', $tokenLike)
                        ->orWhere('first_name', 'like', $tokenLike)
                        ->orWhere('middle_name', 'like', $tokenLike)
                        ->orWhere('lrn', 'like', $tokenLike);
                });
            }
        });
    }

    private function normalizeFilterInput(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function resolveSelectedGrade(string $grade): string
    {
        $normalized = trim($grade);
        if (in_array($normalized, self::GRADE_LEVELS, true)) {
            return $normalized;
        }

        return self::GRADE_LEVELS[0];
    }

    private function enrollmentFormRules(): array
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
