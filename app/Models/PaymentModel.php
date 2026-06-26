<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table        = 'payments';
    protected $primaryKey   = 'id';
    protected $returnType   = 'array';

    // payments table only has created_at
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = '';

    protected $allowedFields = [
        'invoice_id', 'amount', 'method', 'kind', 'square_ref', 'status', 'paid_at',
    ];
}
