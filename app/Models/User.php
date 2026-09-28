<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'academic_year',
        'monthly_allowance',
        'savings_goal',
        'university',
        'program',
        'phone',
        'dob',
        'student_id',
        'campus',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'is_active' => 'boolean',
            'monthly_allowance' => 'decimal:2',
            'savings_goal' => 'decimal:2',
            'dob' => 'date',
        ];
    }

    /**
     * User's transactions.
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * User's custom categories.
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * User's budgets.
     */
    public function budgets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * User's notifications.
     */
    public function notifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    /**
     * User's saving tips.
     */
    public function savingTips(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavingTip::class);
    }

    /**
     * User's AI monthly insights.
     */
    public function aiMonthlyInsights(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiMonthlyInsight::class);
    }

    /**
     * User's created admin tip templates.
     */
    public function adminTipTemplates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdminTipTemplate::class, 'created_by');
    }

    /**
     * User's transaction bookmarks.
     */
    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionBookmark::class);
    }

    /**
     * User's transaction notes.
     */
    public function transactionNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionNote::class);
    }

    /**
     * User's transaction shares.
     */
    public function transactionShares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionShare::class);
    }

    /**
     * User's transaction activities.
     */
    public function transactionActivities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionActivity::class);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a standard student user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user' || empty($this->role);
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->is_active !== false;
    }

    /**
     * Get user initials for avatars (e.g. "Hunzala Khan" -> "HK").
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        return $initials ?: 'CC';
    }
}
