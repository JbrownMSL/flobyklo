<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationItemModel extends Model
{
    protected $table         = 'reservation_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'reservation_id', 'equipment_id', 'qty', 'days', 'rate_snapshot',
        'line_subtotal', 'tax_class', 'tax_amount', 'is_trailer', 'auto_added',
    ];
}
