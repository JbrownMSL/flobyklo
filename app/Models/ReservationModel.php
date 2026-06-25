<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationModel extends Model
{
    protected $table         = 'reservations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'status', 'start_date', 'end_date', 'rental_subtotal', 'waiver_amount',
        'tax_total', 'grand_total', 'amount_paid', 'balance_due', 'damage_deposit',
        'damage_waiver', 'contract_acceptance_id',
    ];

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('start_date', 'DESC')->findAll();
    }

    public function items(int $reservationId): array
    {
        return $this->db->table('reservation_items ri')
            ->select('ri.*, e.name AS equipment_name')
            ->join('equipment e', 'e.id = ri.equipment_id', 'left')
            ->where('ri.reservation_id', $reservationId)
            ->get()->getResultArray();
    }

    /** Admin queues. */
    public function upcoming(): array
    {
        return $this->whereIn('status', ['confirmed'])
            ->where('start_date >=', date('Y-m-d'))
            ->orderBy('start_date', 'ASC')->findAll();
    }

    public function returnsDue(): array
    {
        return $this->where('status', 'picked_up')->orderBy('end_date', 'ASC')->findAll();
    }

    public function overdue(): array
    {
        return $this->where('status', 'picked_up')->where('end_date <', date('Y-m-d'))->findAll();
    }
}
