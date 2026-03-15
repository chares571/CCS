<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ApplicationStatusLog;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MasterDashboardController extends Controller
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
            'pending' => Application::whereIn('status', ['pending', 'reviewed'])->count(),
            'reviewed' => Application::where('status', 'reviewed')->count(),
            'approved' => Application::where('status', 'approved')->count(),
            'by_grade' => $byGrade,
            'by_grade_gender' => $byGradeGender,
        ];

        $genderStats = Application::selectRaw("COALESCE(NULLIF(gender, ''), 'unspecified') as gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $notificationCount = (int) $stats['pending'];

        return view('master.dashboard', compact('stats', 'genderStats', 'notificationCount'));
    }

    public function monitoring(Request $request)
    {
        $nameFilter = $this->normalizeFilterInput((string) $request->input('name', ''));
        $selectedGrade = $this->resolveSelectedGrade((string) $request->input('grade', ''));
        $hasFilter = $nameFilter !== '';
        $showAllGrades = $hasFilter;

        $applicationsQuery = Application::query()
            ->whereIn('grade_level', self::GRADE_LEVELS);

        $applicationsQuery->where(function (Builder $query) {
            $query->whereNull('status')
                ->orWhere('status', '!=', 'approved');
        });

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

        return view('master.monitoring', compact('applicationsByGrade', 'nameFilter', 'matchedCount', 'hasFilter', 'selectedGrade', 'gradeLevels', 'showAllGrades'));
    }

    public function showMonitoringApplication(Application $application)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $canEdit = session()->has($this->unlockSessionKey($application->id));

        return view('master.monitoring-show', compact('application', 'canEdit'));
    }

    public function unlockMonitoringEdit(Request $request, Application $application)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
        $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($request->password, (string) auth()->user()->password)) {
            return back()->withErrors(['password' => 'Invalid password. Edit access denied.']);
        }

        session()->put($this->unlockSessionKey($application->id), true);

        return back()->with('success', 'Edit access granted. You can now update this enrollment form.');
    }

    public function updateMonitoringApplication(Request $request, Application $application)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        if (!session()->has($this->unlockSessionKey($application->id))) {
            return back()->withErrors(['password' => 'Please unlock editing with your password first.']);
        }

        $validated = $request->validate($this->monitoringFormRules());
        $application->update($this->payloadFromValidated($validated));

        session()->forget($this->unlockSessionKey($application->id));

        AuditLogger::log('application_form_updated_by_super_admin', 'application', $application->id, [
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Enrollment form updated successfully.');
    }

    public function toggleEnrollment(SchoolYear $schoolYear)
    {
        $enrollmentColumn = Schema::hasColumn('school_years', 'is_enrollment_open')
            ? 'is_enrollment_open'
            : 'enrollment_open';

        DB::transaction(function () use ($schoolYear, $enrollmentColumn) {
            $lockedYear = SchoolYear::query()
                ->whereKey($schoolYear->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedYear->is_active) {
                abort(422, 'Only the active school year enrollment status can be changed.');
            }

            $current = (bool) ($lockedYear->getAttribute($enrollmentColumn) ?? false);
            try {
                SchoolYear::query()
                    ->whereKey($lockedYear->id)
                    ->update([$enrollmentColumn => !$current]);
            } catch (QueryException $exception) {
                // Backward-compatible fallback for databases that still only have `enrollment_open`.
                SchoolYear::query()
                    ->whereKey($lockedYear->id)
                    ->update(['enrollment_open' => !$current]);
                $enrollmentColumn = 'enrollment_open';
            }
            $lockedYear->refresh();

            AuditLogger::log('school_year_enrollment_toggled', 'school_year', $lockedYear->id, [
                'is_enrollment_open' => (bool) $lockedYear->getAttribute($enrollmentColumn),
            ]);
        });

        return back()->with('success', 'Enrollment status updated.');
    }

    public function setActive(SchoolYear $schoolYear)
    {
        DB::transaction(function () use ($schoolYear) {
            $lockedYears = SchoolYear::query()->lockForUpdate()->get();
            $targetYear = $lockedYears->firstWhere('id', $schoolYear->id);

            if (!$targetYear) {
                abort(404, 'School year not found.');
            }

            if ((bool) ($targetYear->is_locked ?? false)) {
                abort(422, 'This school year is locked and cannot be reactivated.');
            }

            if ($targetYear->is_active) {
                return;
            }

            $currentActive = $lockedYears->firstWhere('is_active', true);

            if ($currentActive && $currentActive->id !== $targetYear->id) {
                $currentActive->is_active = false;
                if (Schema::hasColumn('school_years', 'is_locked')) {
                    $currentActive->is_locked = true;
                }
                $currentActive->save();
            }

            SchoolYear::query()->whereKey($targetYear->id)->update(['is_active' => true]);

            $activeCount = SchoolYear::query()->where('is_active', true)->lockForUpdate()->count();
            if ($activeCount !== 1) {
                abort(409, 'School year activation conflict detected. Please try again.');
            }

            AuditLogger::log('school_year_activated', 'school_year', $targetYear->id, [
                'name' => $targetYear->name,
            ]);
        });

        return back()->with('success', 'Active school year updated.');
    }

    public function storeSchoolYear(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'string', 'max:20'],
            'enrollment_start_at' => ['nullable', 'date'],
            'enrollment_end_at' => ['nullable', 'date', 'after_or_equal:enrollment_start_at'],
        ]);

        DB::transaction(function () use ($validated) {
            $incomingYear = trim($validated['year']);

            $alreadyExists = SchoolYear::query()
                ->where('name', $incomingYear)
                ->when(Schema::hasColumn('school_years', 'year'), function ($query) use ($incomingYear) {
                    $query->orWhere('year', $incomingYear);
                })
                ->exists();

            if ($alreadyExists) {
                abort(422, 'School year already exists.');
            }

            $payload = [
                'name' => $incomingYear,
                'is_active' => false,
            ];

            if (Schema::hasColumn('school_years', 'year')) {
                $payload['year'] = $incomingYear;
            }

            if (Schema::hasColumn('school_years', 'is_enrollment_open')) {
                $payload['is_enrollment_open'] = false;
            } else {
                $payload['enrollment_open'] = false;
            }

            if (Schema::hasColumn('school_years', 'is_locked')) {
                $payload['is_locked'] = false;
            }

            if (Schema::hasColumn('school_years', 'enrollment_start_at')) {
                $payload['enrollment_start_at'] = $validated['enrollment_start_at'] ?? null;
            }
            if (Schema::hasColumn('school_years', 'enrollment_end_at')) {
                $payload['enrollment_end_at'] = $validated['enrollment_end_at'] ?? null;
            }

            $schoolYear = SchoolYear::query()->create($payload);

            AuditLogger::log('school_year_created', 'school_year', $schoolYear->id, [
                'year' => $incomingYear,
            ]);
        });

        return back()->with('success', 'School year created for preparation. Activate it when ready.');
    }

    public function lockSchoolYear(SchoolYear $schoolYear)
    {
        if (!Schema::hasColumn('school_years', 'is_locked')) {
            return back()->withErrors(['school_year' => 'Locking is unavailable until latest migration is applied.']);
        }

        DB::transaction(function () use ($schoolYear) {
            $lockedYear = SchoolYear::query()
                ->whereKey($schoolYear->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedYear->is_active) {
                abort(422, 'Active school year cannot be locked.');
            }

            $lockedYear->is_locked = true;
            $lockedYear->save();

            AuditLogger::log('school_year_locked', 'school_year', $lockedYear->id, [
                'name' => $lockedYear->name,
            ]);
        });

        return back()->with('success', 'School year locked successfully.');
    }

    public function updateEnrollmentWindow(Request $request, SchoolYear $schoolYear)
    {
        if (!Schema::hasColumn('school_years', 'enrollment_start_at') || !Schema::hasColumn('school_years', 'enrollment_end_at')) {
            return back()->withErrors(['school_year' => 'Enrollment window fields are not available yet. Run latest migrations first.']);
        }

        $validated = $request->validate([
            'enrollment_start_at' => ['nullable', 'date'],
            'enrollment_end_at' => ['nullable', 'date', 'after_or_equal:enrollment_start_at'],
        ]);

        DB::transaction(function () use ($schoolYear, $validated) {
            $lockedYear = SchoolYear::query()
                ->whereKey($schoolYear->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedYear->is_active) {
                abort(422, 'Enrollment window can only be updated for the active school year.');
            }

            $lockedYear->enrollment_start_at = $validated['enrollment_start_at'] ?? null;
            $lockedYear->enrollment_end_at = $validated['enrollment_end_at'] ?? null;
            $lockedYear->save();

            AuditLogger::log('school_year_enrollment_window_updated', 'school_year', $lockedYear->id, [
                'enrollment_start_at' => $lockedYear->enrollment_start_at,
                'enrollment_end_at' => $lockedYear->enrollment_end_at,
            ]);
        });

        return back()->with('success', 'Enrollment window updated.');
    }

    public function enrollment()
    {
        $applications = Application::where('status', 'reviewed')->latest()->paginate(10);

        return view('master.enrollment', compact('applications'));
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
        $duplicateApplicationIds = $this->detectDuplicateApplicationIds($approvedApplications);

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

        return view('master.enrolled-students', compact('enrolledByGrade', 'nameFilter', 'hasFilter', 'matchedCount', 'duplicateApplicationIds', 'selectedGrade', 'gradeLevels'));
    }

    public function enrollmentHistory(Request $request)
    {
        $nameFilter = $this->normalizeFilterInput((string) $request->input('name', ''));
        $hasFilter = $nameFilter !== '';
        $nameTokens = array_values(array_filter(preg_split('/[\s,]+/', $nameFilter) ?: []));

        $historyQuery = Application::query()
            ->with([
                'user:id,full_name,role,username,email',
                'schoolYear:id,name',
            ])
            ->whereNotNull('submitted_at');

        if ($hasFilter) {
            $historyQuery->where(function ($query) use ($nameFilter, $nameTokens) {
                $query->where('learner_full_name', 'like', '%'.$nameFilter.'%')
                    ->orWhere('last_name', 'like', '%'.$nameFilter.'%')
                    ->orWhere('first_name', 'like', '%'.$nameFilter.'%')
                    ->orWhere('middle_name', 'like', '%'.$nameFilter.'%')
                    ->orWhereHas('user', function ($userQuery) use ($nameFilter) {
                        $userQuery->where('full_name', 'like', '%'.$nameFilter.'%')
                            ->orWhere('username', 'like', '%'.$nameFilter.'%')
                            ->orWhere('email', 'like', '%'.$nameFilter.'%')
                            ->orWhere('role', 'like', '%'.$nameFilter.'%');
                    });

                foreach ($nameTokens as $token) {
                    $query->orWhere(function ($tokenQuery) use ($token) {
                        $tokenQuery->where('learner_full_name', 'like', '%'.$token.'%')
                            ->orWhere('last_name', 'like', '%'.$token.'%')
                            ->orWhere('first_name', 'like', '%'.$token.'%')
                            ->orWhere('middle_name', 'like', '%'.$token.'%')
                            ->orWhereHas('user', function ($userQuery) use ($token) {
                                $userQuery->where('full_name', 'like', '%'.$token.'%')
                                    ->orWhere('username', 'like', '%'.$token.'%')
                                    ->orWhere('email', 'like', '%'.$token.'%')
                                    ->orWhere('role', 'like', '%'.$token.'%');
                            });
                    });
                }
            });
        }

        $history = $historyQuery
            ->latest('submitted_at')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $matchedCount = $history->total();

        return view('master.enrollment-history', compact('history', 'nameFilter', 'hasFilter', 'matchedCount'));
    }

    public function destroyDuplicateEnrollee(Application $application)
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        if ($application->status !== 'approved') {
            return back()->withErrors(['application' => 'Only approved enrollees can be deleted as duplicates.']);
        }

        $isDuplicate = $this->isDuplicateApprovedEnrollee($application);
        if (!$isDuplicate) {
            return back()->withErrors(['application' => 'Selected enrollee is not detected as duplicate.']);
        }

        DB::transaction(function () use ($application) {
            Student::query()->where('application_id', $application->id)->delete();

            ApplicationStatusLog::create([
                'application_id' => $application->id,
                'changed_by' => auth()->id(),
                'status' => 'rejected',
                'remarks' => 'Duplicate enrollee record removed by Super Admin.',
                'changed_at' => now(),
            ]);

            $application->delete();
        });

        AuditLogger::log('duplicate_enrollee_deleted', 'application', $application->id, [
            'deleted_by' => auth()->id(),
            'school_year_id' => $application->school_year_id,
            'learner_full_name' => $application->learner_full_name,
        ]);

        return back()->with('success', 'Duplicate enrollee removed successfully.');
    }

    public function schoolYears()
    {
        $orderColumn = Schema::hasColumn('school_years', 'year') ? 'year' : 'name';
        $supportsEnrollmentWindow = Schema::hasColumn('school_years', 'enrollment_start_at')
            && Schema::hasColumn('school_years', 'enrollment_end_at');

        $schoolYears = SchoolYear::query()
            ->withCount([
                'applications as approved_applications_count' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderByDesc($orderColumn)
            ->orderByDesc('created_at')
            ->get();
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        $supportsLocking = Schema::hasColumn('school_years', 'is_locked');

        return view('master.school-years', compact('schoolYears', 'activeSchoolYear', 'supportsEnrollmentWindow', 'supportsLocking'));
    }

    public function backup()
    {
        $recentAuditLogs = AuditLog::latest()->take(8)->get();

        return view('master.backup', compact('recentAuditLogs'));
    }

    private function unlockSessionKey(int $applicationId): string
    {
        return 'monitoring_edit_unlocked_'.$applicationId;
    }

    private function monitoringFormRules(): array
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

    private function isDuplicateApprovedEnrollee(Application $application): bool
    {
        $duplicateQuery = Application::query()
            ->where('status', 'approved')
            ->where('school_year_id', $application->school_year_id)
            ->where('id', '!=', $application->id);

        $lrn = trim((string) ($application->lrn ?? ''));
        if ($lrn !== '') {
            return $duplicateQuery->where('lrn', $lrn)->exists();
        }

        $duplicateQuery
            ->whereRaw('LOWER(COALESCE(last_name, "")) = ?', [strtolower((string) $application->last_name)])
            ->whereRaw('LOWER(COALESCE(first_name, "")) = ?', [strtolower((string) $application->first_name)])
            ->whereRaw('LOWER(COALESCE(middle_name, "")) = ?', [strtolower((string) $application->middle_name)]);

        if ($application->birthdate) {
            $duplicateQuery->whereDate('birthdate', $application->birthdate);
        } else {
            $duplicateQuery->whereNull('birthdate');
        }

        return $duplicateQuery->exists();
    }

    private function detectDuplicateApplicationIds($applications): array
    {
        $groups = [];

        foreach ($applications as $application) {
            $lrn = trim((string) ($application->lrn ?? ''));
            if ($lrn !== '') {
                $key = 'lrn:'.$application->school_year_id.':'.strtolower($lrn);
            } else {
                $key = 'name:'.$application->school_year_id.':'
                    .strtolower(trim((string) $application->last_name)).'|'
                    .strtolower(trim((string) $application->first_name)).'|'
                    .strtolower(trim((string) $application->middle_name)).'|'
                    .optional($application->birthdate)->format('Y-m-d');
            }

            $groups[$key][] = $application->id;
        }

        $duplicateIds = [];
        foreach ($groups as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicateIds[] = $id;
                }
            }
        }

        return array_values(array_unique($duplicateIds));
    }
}
