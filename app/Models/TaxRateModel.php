<?php

namespace App\Models;

use CodeIgniter\Model;

class TaxRateModel extends Model
{
    protected $table         = 'tax_rates';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['tax_class', 'label', 'rate', 'active'];

    /** tax_class => rate map (active only). */
    public function map(): array
    {
        $out = [];
        foreach ($this->where('active', 1)->findAll() as $r) {
            $out[$r['tax_class']] = (float) $r['rate'];
        }
        return $out;
    }
}
