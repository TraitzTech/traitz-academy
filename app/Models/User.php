<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_USER = 'user';

    public const ROLE_CTO = 'cto';

    public const ROLE_CEO = 'ceo';

    public const ROLE_PROGRAM_COORDINATOR = 'program_coordinator';

    public const ROLE_ADMIN_LEGACY = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'phone_required_prompted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function sentEmailCampaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'admin_id');
    }

    public function emailCampaignRecipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function interviewResponses(): HasMany
    {
        return $this->hasMany(InterviewResponse::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function recordedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'recorded_by');
    }

    public static function managedRoleOptions(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_CTO,
            self::ROLE_CEO,
            self::ROLE_PROGRAM_COORDINATOR,
        ];
    }

    public function isExecutive(): bool
    {
        return in_array($this->role, [self::ROLE_CTO, self::ROLE_CEO, self::ROLE_ADMIN_LEGACY], true);
    }

    public function isProgramCoordinator(): bool
    {
        return $this->role === self::ROLE_PROGRAM_COORDINATOR;
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isExecutive() || $this->isProgramCoordinator();
    }

    public function canManageUsers(): bool
    {
        return $this->isExecutive();
    }

    public function canManagePaymentRecord(Payment $payment): bool
    {
        if ($this->isExecutive()) {
            return true;
        }

        if (! $this->isProgramCoordinator()) {
            return false;
        }

        if (! $payment->manual_entry) {
            return false;
        }

        $collectorId = $payment->recorded_by ?? $payment->updated_by;

        return (int) $collectorId === (int) $this->id;
    }

    public function canManageExpense(Expense $expense): bool
    {
        if ($this->isExecutive()) {
            return true;
        }

        if (! $this->isProgramCoordinator()) {
            return false;
        }

        return (int) $expense->recorded_by === (int) $this->id;
    }
}
