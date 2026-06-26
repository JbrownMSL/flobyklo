<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (function_exists('auth') && auth()->loggedIn() && fbk_is_admin()) {
            return redirect()->to('/admin');
        }
        return view('home', ['title' => 'Flora by Klo — Admin']);
    }
}
