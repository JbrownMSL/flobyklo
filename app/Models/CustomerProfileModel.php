<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerProfileModel extends Model
{
    protected $table         = 'customer_profiles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'full_name', 'phone', 'address', 'city', 'state', 'zip',
        'age_attested', 'square_customer_id',
    ];

    public function forUser(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }
}
