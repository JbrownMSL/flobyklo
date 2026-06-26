<?php

namespace App\Models;

use CodeIgniter\Model;

class RecipeModel extends Model
{
    protected $table          = 'recipes';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'type', 'labor_minutes', 'notes'];

    /**
     * Compute total cost for a recipe given its stem rows.
     * cost = Σ(stem.qty × stem.unit_cost) + (labor_minutes / 60) × laborRate
     */
    public function computeCost(array $recipe, array $stems): float
    {
        $stemCost = 0.0;
        foreach ($stems as $s) {
            $stemCost += (float) $s['qty'] * (float) $s['unit_cost'];
        }
        $laborRate = (float) config('Fbk')->laborRate;
        $laborCost = ((float) $recipe['labor_minutes'] / 60.0) * $laborRate;
        return round($stemCost + $laborCost, 2);
    }

    /**
     * Convenience method for quote-building: load a recipe's stems and return its cost.
     */
    public function costForQuote(int $recipeId): float
    {
        $recipe = $this->find($recipeId);
        if (! $recipe) {
            return 0.0;
        }
        $stems = (new RecipeStemModel())->forRecipe($recipeId);
        return $this->computeCost($recipe, $stems);
    }
}
