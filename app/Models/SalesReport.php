<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'month',
        'year',
        'status',
        'breeder',
        'code',
        'photo',
        'name',
        'qty',
        'sell_jpy',
        'sell_idr',
        'margin',
        'fee',
        'stock_jpy',
        'stock_idr',
        'sell_to',
        'profit',
        'user_id',
    ];

    protected $casts = [
        'day'       => 'integer',
        'month'     => 'integer',
        'year'      => 'integer',
        'qty'       => 'integer',
        'sell_jpy'  => 'decimal:2',
        'sell_idr'  => 'decimal:2',
        'margin'    => 'decimal:2',
        'fee'       => 'decimal:2',
        'stock_jpy' => 'decimal:2',
        'stock_idr' => 'decimal:2',
        'profit'    => 'decimal:2',
    ];

    protected $appends = ['photo_url', 'date_formatted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function getDateFormattedAttribute(): string
    {
        return sprintf('%02d/%02d/%04d', $this->day, $this->month, $this->year);
    }

    /**
     * Auto-calculate profit before saving
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (SalesReport $report) {
            $report->profit = ($report->sell_idr - $report->stock_idr) - $report->fee + $report->margin;
        });
    }

    /**
     * Scope: filter by year
     */
    public function scopeOfYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: filter by month
     */
    public function scopeOfMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope: filter by status
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}