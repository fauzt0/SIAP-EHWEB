<?php
declare(strict_types=1);
namespace App\Models\Users;
use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'first_name',
            'last_name',
            'phone_number',
            'mobile_number',
            'role',
            'avatar',
        ];
    }
}
