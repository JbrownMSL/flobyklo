<?php

namespace App\Models;

use CodeIgniter\Model;

class RecipeStemModel extends Model
{
    protected $table         = 'recipe_stems';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['recipe_id', 'stem_name', 'qty', 'unit_cost'];

    public function forRecipe(int $recipeId): array
    {
        return $this->where('recipe_id', $recipeId)->orderBy('id')->findAll();
    }
}
