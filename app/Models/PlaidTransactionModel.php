<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaidTransactionModel extends Model
{
    protected $table         = 'plaid_transactions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['plaid_connection_id', 'txn_id', 'date', 'amount', 'name', 'category', 'pending', 'created_at'];
}
