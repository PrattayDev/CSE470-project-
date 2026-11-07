<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabEquipment extends Model
{
    protected $table = 'lab_equipments';

    protected $fillable = [
        'asset_code', 'equipment_name', 'category',
        'total_quantity', 'available_quantity',
        'daily_late_fine_bdt', 'condition_status', 'condition_notes'
    ];

    public function requisitions()
    {
        return $this->hasMany(EquipmentRequisition::class, 'equipment_id');
    }

    public function activeLoanQuantity(): int
    {
        return (int) $this->requisitions()
            ->whereIn('status', ['issued', 'overdue'])
            ->sum('quantity');
    }

    public function derivedAvailableQuantity(): int
    {
        return max(0, (int) $this->total_quantity - $this->activeLoanQuantity());
    }

    public function isLowStock(): bool
    {
        return $this->derivedAvailableQuantity() <= 2 || $this->condition_status !== 'excellent' && $this->derivedAvailableQuantity() <= 3;
    }
}
