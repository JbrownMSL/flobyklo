<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['name', 'slug', 'sort', 'active'];

    public function active(): array
    {
        return $this->where('active', 1)->orderBy('sort', 'ASC')->orderBy('name', 'ASC')->findAll();
    }

    public function bySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }
}
