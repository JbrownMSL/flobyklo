<?php

namespace App\Models;

use CodeIgniter\Model;

class PlaidConnectionModel extends Model
{
    protected $table         = 'plaid_connections';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['item_id', 'access_token', 'account_id', 'name', 'mask', 'created_at'];
}
