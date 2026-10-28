<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'holding_expires_at',
        'expired_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'holding_expires_at' => 'datetime',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function successfulPayment()
    {
        return $this->hasOne(Payment::class)->where('status', 'PAID')->latestOfMany();
    }

    public function refunds()
    {
        return $this->hasManyThrough(Refund::class, Payment::class);
    }

    /**
     * Lấy thời gian hết hạn giữ chỗ (hỗ trợ cả holding_expires_at và expired_at)
     */
    public function getHoldingExpiresAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : ($this->expired_at ? Carbon::parse($this->expired_at) : null);
    }

    /**
     * Kiểm tra xem đơn hàng còn trong thời gian giữ chỗ và có thể thanh toán không
     */
    public function canPay(): bool
    {
        if ($this->status !== 'PENDING') {
            return false;
        }

        $expiresAt = $this->holding_expires_at;
        return $expiresAt ? $expiresAt->isFuture() : true;
    }

    /**
     * Kiểm tra xem đơn có thể hủy không (khi chưa thanh toán)
     */
    public function canCancel(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Lấy suất chiếu sớm nhất trong đơn
     */
    public function earliestShowtime(): ?Showtime
    {
        $firstTicket = $this->ticketDetails()->with('showtime')->first();
        return $firstTicket?->showtime;
    }

    /**
     * Kiểm tra xem đơn có đủ điều kiện yêu cầu hoàn tiền không:
     * - Trạng thái Booking là PAID
     * - Có giao dịch thanh toán thành công
     * - Chưa có yêu cầu hoàn tiền đang chờ (PENDING) hoặc đã hoàn (COMPLETED)
     * - Thời điểm hiện tại cách giờ bắt đầu chiếu >= 2 tiếng (120 phút)
     */
    public function canRequestRefund(): bool
    {
        if ($this->status !== 'PAID') {
            return false;
        }

        // Kiểm tra xem đã có refund đang PENDING hoặc COMPLETED chưa
        $hasActiveRefund = $this->refunds()
            ->whereIn('refunds.status', ['PENDING', 'COMPLETED'])
            ->exists();

        if ($hasActiveRefund) {
            return false;
        }

        // Kiểm tra điều kiện thời gian: trước giờ chiếu ít nhất 2 tiếng (120 phút)
        $showtime = $this->earliestShowtime();
        if (!$showtime || !$showtime->start_time) {
            return false;
        }

        $startTime = Carbon::parse($showtime->start_time);
        return now()->diffInMinutes($startTime, false) >= 120;
    }
}
