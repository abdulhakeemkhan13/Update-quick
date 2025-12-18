<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'due_in_days',
        'day_of_month',
        'cutoff_days',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'due_in_days' => 'integer',
        'day_of_month' => 'integer',
        'cutoff_days' => 'integer',
    ];

    /**
     * Calculate due date based on issue date and term rules
     *
     * @param Carbon|string $issueDate
     * @return Carbon
     */
    public function calculateDueDate($issueDate): Carbon
    {
        $issueDate = Carbon::parse($issueDate);

        switch ($this->type) {
            case 'fixed_days':
                // Due date = issue_date + N days
                return $issueDate->copy()->addDays($this->due_in_days ?? 0);

            case 'day_of_month':
                // Due on specific day of month
                $targetDay = $this->day_of_month ?? 1;
                $dueDate = $issueDate->copy()->day($targetDay);
                
                // If target day has already passed this month, move to next month
                if ($dueDate->lte($issueDate)) {
                    $dueDate->addMonth();
                }
                
                return $dueDate;

            case 'next_month_if_within':
                // Due on specific day, but bump to next month if issued within N days of due date
                $targetDay = $this->day_of_month ?? 1;
                $cutoffDays = $this->cutoff_days ?? 0;
                
                $baseDue = $issueDate->copy()->day($targetDay);
                
                // If target day has already passed this month, move to next month
                if ($baseDue->lte($issueDate)) {
                    $baseDue->addMonth();
                }
                
                // Check if issue date is within cutoff days of due date
                $daysBetween = $issueDate->diffInDays($baseDue, false);
                
                if ($daysBetween <= $cutoffDays) {
                    // Bump to next month
                    return $baseDue->addMonth();
                }
                
                return $baseDue;

            default:
                return $issueDate->copy();
        }
    }

    /**
     * Get display text for the term type
     */
    public function getTypeDisplayAttribute(): string
    {
        switch ($this->type) {
            case 'fixed_days':
                return "Net {$this->due_in_days}";
            case 'day_of_month':
                return "Due on {$this->day_of_month}th";
            case 'next_month_if_within':
                return "Due on {$this->day_of_month}th (next month if within {$this->cutoff_days} days)";
            default:
                return $this->name;
        }
    }

    /**
     * Scope for active terms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for current user's terms
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('created_by', \Auth::user()->creatorId());
    }
}
