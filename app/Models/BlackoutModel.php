<?php

namespace App\Models;

use CodeIgniter\Model;

class BlackoutModel extends Model
{
    protected $table         = 'unit_blackouts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['equipment_id', 'qty', 'start_date', 'end_date', 'reason', 'reservation_id'];

    /**
     * Units already committed (booked OR in maintenance) for an item over a range.
     * Overlap = NOT (existing.end < start OR existing.start > end).
     */
    public function committedQty(int $equipmentId, string $start, string $end): int
    {
        $row = $this->db->table('unit_blackouts')
            ->selectSum('qty')
            ->where('equipment_id', $equipmentId)
            ->where('start_date <=', $end)
            ->where('end_date >=', $start)
            ->get()->getRowArray();
        return (int) ($row['qty'] ?? 0);
    }
}
