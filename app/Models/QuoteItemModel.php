<?php

namespace App\Models;

use CodeIgniter\Model;

class QuoteItemModel extends Model
{
    protected $table         = 'quote_items';
    protected $allowedFields = ['quote_id', 'recipe_id', 'description', 'qty', 'unit_price', 'line_total', 'cost'];
    protected $useTimestamps = false;
}
