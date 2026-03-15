<?php

namespace App\Models;

use App\Support\ApplicationStatusReasoner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_year_id',
        'learner_full_name',
        'grade_level',
        'with_lrn',
        'lrn',
        'returning_learner',
        'psa_birth_certificate_no',
        'last_name',
        'first_name',
        'middle_name',
        'birthdate',
        'place_of_birth',
        'mother_tongue',
        'has_ip_affiliation',
        'ip_affiliation',
        'is_4ps_beneficiary',
        'four_ps_household_id',
        'is_lwd',
        'disability_types',
        'disability_specify',
        'current_house_no',
        'current_street',
        'current_barangay',
        'current_municipality',
        'current_province',
        'current_country',
        'current_zip_code',
        'permanent_house_no',
        'permanent_street',
        'permanent_barangay',
        'permanent_municipality',
        'permanent_province',
        'permanent_country',
        'permanent_zip_code',
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_contact_number',
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_contact_number',
        'guardian_last_name',
        'guardian_first_name',
        'guardian_middle_name',
        'guardian_contact_number',
        'gender',
        'status',
        'submitted_at',
        'reviewed_at',
        'finalized_at',
        'reviewed_by',
        'finalized_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'finalized_at' => 'datetime',
            'birthdate' => 'date',
            'with_lrn' => 'boolean',
            'returning_learner' => 'boolean',
            'has_ip_affiliation' => 'boolean',
            'is_4ps_beneficiary' => 'boolean',
            'is_lwd' => 'boolean',
            'disability_types' => 'array',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function schoolYear() { return $this->belongsTo(SchoolYear::class); }
    public function documents() { return $this->hasMany(Document::class); }
    public function statusLogs() { return $this->hasMany(ApplicationStatusLog::class); }

    /**
     * @return array<int, string>
     */
    public function allowedNextStatuses(): array
    {
        return ApplicationStatusReasoner::allowedNextStatuses((string) $this->status);
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        return ApplicationStatusReasoner::canTransition((string) $this->status, $nextStatus);
    }

    /**
     * @return array<int, string>
     */
    public function allowedFinalDecisionStatuses(): array
    {
        return ApplicationStatusReasoner::allowedFinalDecisionStatuses((string) $this->status);
    }

    public function canReceiveFinalDecision(): bool
    {
        return $this->allowedFinalDecisionStatuses() !== [];
    }

    public function canBeReviewedByAdmin(): bool
    {
        return ApplicationStatusReasoner::canBeReviewedByAdmin((string) $this->status);
    }

    public function canBeEditedByApplicant(): bool
    {
        return ApplicationStatusReasoner::canBeEditedByApplicant((string) $this->status);
    }
}
