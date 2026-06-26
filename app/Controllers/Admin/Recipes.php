<?php

namespace App\Controllers\Admin;

use App\Models\RecipeModel;
use App\Models\RecipeStemModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Recipes extends BaseAdmin
{
    private RecipeModel $recipeModel;
    private RecipeStemModel $stemModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->recipeModel = new RecipeModel();
        $this->stemModel   = new RecipeStemModel();
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $recipes = $this->recipeModel->orderBy('type')->orderBy('name')->findAll();
        foreach ($recipes as &$rec) {
            $stems             = $this->stemModel->forRecipe((int) $rec['id']);
            $rec['cost']       = $this->recipeModel->computeCost($rec, $stems);
            $rec['stem_count'] = count($stems);
        }
        unset($rec);
        return view('admin/recipes/index', [
            'title'   => 'Recipes',
            'recipes' => $recipes,
        ]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id     = $id ? (int) $id : null;
        $recipe = $id ? $this->recipeModel->find($id) : null;
        if ($id && ! $recipe) {
            return redirect()->to('/admin/recipes')->with('error', 'Recipe not found.');
        }
        $stems = $id ? $this->stemModel->forRecipe($id) : [];
        $cost  = $recipe ? $this->recipeModel->computeCost($recipe, $stems) : 0.0;

        return view('admin/recipes/form', [
            'title'     => $recipe ? 'Edit Recipe — ' . $recipe['name'] : 'New Recipe',
            'recipe'    => $recipe,
            'stems'     => $stems,
            'cost'      => $cost,
            'laborRate' => (float) config('Fbk')->laborRate,
            'validation' => service('validation'),
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id    = (int) $this->request->getPost('id');
        $rules = [
            'name'          => 'required|max_length[150]',
            'type'          => 'required|in_list[bouquet,centerpiece,install,boutonniere,arch,other]',
            'labor_minutes' => 'required|integer|greater_than_equal_to[0]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = [
            'name'          => $this->request->getPost('name'),
            'type'          => $this->request->getPost('type'),
            'labor_minutes' => (int) $this->request->getPost('labor_minutes'),
            'notes'         => $this->request->getPost('notes') ?: null,
        ];
        if ($id) {
            $this->recipeModel->update($id, $data);
        } else {
            $id = (int) $this->recipeModel->insert($data);
        }
        return redirect()->to('/admin/recipes/' . $id)->with('msg', 'Recipe saved.');
    }

    public function saveStem(int $recipeId)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        if (! $this->recipeModel->find($recipeId)) {
            return redirect()->to('/admin/recipes')->with('error', 'Recipe not found.');
        }
        $stemId   = (int) $this->request->getPost('stem_id');
        $stemName = trim((string) $this->request->getPost('stem_name'));
        if ($stemName === '') {
            return redirect()->to('/admin/recipes/' . $recipeId)->with('error', 'Stem name is required.');
        }
        $data = [
            'recipe_id' => $recipeId,
            'stem_name' => $stemName,
            'qty'       => (float) $this->request->getPost('qty'),
            'unit_cost' => (float) $this->request->getPost('unit_cost'),
        ];
        if ($stemId) {
            $this->stemModel->update($stemId, $data);
        } else {
            $this->stemModel->insert($data);
        }
        return redirect()->to('/admin/recipes/' . $recipeId)->with('msg', 'Stem saved.');
    }

    public function deleteStem(int $stemId)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $stem     = $this->stemModel->find($stemId);
        $recipeId = $stem ? (int) $stem['recipe_id'] : 0;
        $this->stemModel->delete($stemId);
        $back = $recipeId ? '/admin/recipes/' . $recipeId : '/admin/recipes';
        return redirect()->to($back)->with('msg', 'Stem removed.');
    }
}
