<?php

namespace Modules\Expenses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'expense_date',
        'category_id',
        'amount',
        'payment_mode',
        'paid_to',
        'description',
        'reference_id',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Category of this expense.
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    // /**
    //  * User who created the expense.
    //  */
    // public function createdBy()
    // {
    //     return $this->belongsTo(User::class, 'created_by');
    // }

    // /**
    //  * User who approved the expense.
    //  */
    // public function approvedBy()
    // {
    //     return $this->belongsTo(User::class, 'approved_by');
    // }

    // /**
    //  * Optional reference to another entity (e.g. purchase, payroll, fleet, etc.)
    //  */
    // public function reference()
    // {
    //     // this can be dynamically resolved later if you implement polymorphic or typed references
    //     return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    // }
}
