<?php

namespace App\Controllers\Admin;

class Recipes extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $recipes = db_connect()->table('recipes')->orderBy('name')->get()->getResultArray();
        return view('admin/recipes/index', ['title' => 'Recipes', 'recipes' => $recipes]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $db     = db_connect();
        $recipe = $id ? $db->table('recipes')->where('id', $id)->get()->getRowArray() : null;
        $stems  = $id ? $db->table('recipe_stems')->where('recipe_id', $id)->get()->getResultArray() : [];
        return view('admin/recipes/form', [
            'title'  => $recipe ? 'Edit Recipe' : 'New Recipe',
            'recipe' => $recipe,
            'stems'  => $stems,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement save
        return redirect()->to('/admin/recipes')->with('msg', 'Saved.');
    }

    public function saveStem(int $recipeId)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement stem save
        return redirect()->back()->with('msg', 'Stem saved.');
    }

    public function deleteStem(int $stemId)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement stem delete
        return redirect()->back()->with('msg', 'Stem removed.');
    }
}
