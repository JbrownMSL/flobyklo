<?php

namespace App\Controllers;

/**
 * Account controller — Shield auth account management (password change,
 * profile display). WTR reservation/cart functionality removed.
 */
class Account extends BaseController
{
    public function index(): string
    {
        return view('account/index', [
            'title' => 'My Account',
            'email' => auth()->user()?->email ?? '',
        ]);
    }
}
