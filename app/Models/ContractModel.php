<?php

namespace App\Models;

use CodeIgniter\Model;

class ContractModel extends Model
{
    protected $table         = 'contracts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['version', 'title', 'body_html', 'effective_at', 'active'];

    public function current(): ?array
    {
        return $this->where('active', 1)->orderBy('id', 'DESC')->first();
    }
}
