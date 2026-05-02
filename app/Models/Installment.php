<?php

namespace App\Models;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Models\Financing;
use App\Models\InstallmentPaymentSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenor',
        'financing_id',
    ];

    public function financing()
    {
        return $this->belongsTo(Financing::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(InstallmentPaymentSchedule::class);
    }

    public function generatePaymentSchedules(int $tenor, $akadDate)
    {
        $akadDate = $akadDate instanceof Carbon
            ? $akadDate
            : Carbon::parse($akadDate);

        foreach (range(1, $tenor) as $month) {
            InstallmentPaymentSchedule::create([
                'installment_id' => $this->id,
                'installment_number' => $month,
                'due_date' => $akadDate->copy()->addMonths($month),
                'installment_schedule_status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);
        }
    }
}
