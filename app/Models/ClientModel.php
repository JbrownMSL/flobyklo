<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientModel extends Model
{
    protected $table         = 'clients';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'email', 'phone', 'address', 'source', 'status', 'notes'];

    protected $validationRules = [
        'name'   => 'required|max_length[150]',
        'email'  => 'permit_empty|valid_email|max_length[150]',
        'phone'  => 'permit_empty|max_length[30]',
        'source' => 'required|in_list[inquiry,referral,instagram,facebook,google,other]',
        'status' => 'required|in_list[lead,consult,booked,completed,lost]',
    ];

    protected $validationMessages = [
        'name'  => ['required' => 'Client name is required.'],
        'email' => ['valid_email' => 'Please enter a valid email address.'],
    ];

    public static function statusList(): array
    {
        return ['lead', 'consult', 'booked', 'completed', 'lost'];
    }

    public static function sourceList(): array
    {
        return ['inquiry', 'referral', 'instagram', 'facebook', 'google', 'other'];
    }
}
