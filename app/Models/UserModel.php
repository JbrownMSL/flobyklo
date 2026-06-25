<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

/**
 * App user model — extends Shield's only to persist the extra
 * users.phone_number column (Shield's allowedFields doesn't include it).
 *
 * Login resolution is unchanged from Shield: the login identifier (email or
 * E.164 phone) is matched against the email_password identity's `secret`, so
 * findByCredentials needs no override.
 */
class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields[] = 'phone_number';
    }
}
