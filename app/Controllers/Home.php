<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\EquipmentModel;

class Home extends BaseController
{
    public function index(): string
    {
        return view('home', [
            'title'       => 'Equipment & Tool Rental in Bluffdale, UT',
            'description' => 'Rent quality tools, trailers, tractors, and skid-steer attachments by the day or week in Bluffdale, Utah. Check real-time availability and reserve online — pickup or delivery from Weekend Tool Rentals.',
            'categories'  => (new CategoryModel())->active(),
            'featured'    => array_slice((new EquipmentModel())->listing(), 0, 8),
        ]);
    }
}
