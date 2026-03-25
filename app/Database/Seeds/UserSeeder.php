<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usamos el proveedor de usuarios de Shield
        $users = auth()->getProvider();

        $testUsers = [
            [
                'username'   => 'superadmin',
                'email'      => 'superadmin@especialistashosting.com',
                'password'   => 'superadmin123',
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'role'       => 'superadmin'
            ],
            [
                'username'   => 'admin',
                'email'      => 'admin@especialistashosting.com',
                'password'   => 'admin12345',
                'first_name' => 'Administrador',
                'last_name'  => 'Principal',
                'role'       => 'admin'
            ],
            [
                'username'   => 'editor_test',
                'email'      => 'editor@especialistashosting.com',
                'password'   => 'editor12345',
                'first_name' => 'Editor',
                'last_name'  => 'Pérez',
                'role'       => 'editor'
            ],
            [
                'username'   => 'seller_test',
                'email'      => 'seller@especialistashosting.com',
                'password'   => 'seller12345',
                'first_name' => 'Vendedor',
                'last_name'  => 'Gómez',
                'role'       => 'seller'
            ]
        ];

        foreach ($testUsers as $userData) {
            // Verificamos que no exista previamente por email o username para no duplicar en multiples seedings
            $existing = $users->findByCredentials(['email' => $userData['email']]);
            
            if (!$existing) {
                $user = new User([
                    'username'   => $userData['username'],
                    'email'      => $userData['email'],
                    'password'   => $userData['password'],
                    'first_name' => $userData['first_name'],
                    'last_name'  => $userData['last_name'],
                ]);

                $users->save($user);
                
                // Obtenemos el usuario recién guardado para asociarle el grupo
                $savedUser = $users->findById($users->getInsertID());
                
                if ($savedUser) {
                    $savedUser->addGroup($userData['role']);
                }
            }
        }
        
        echo "Usuarios de prueba insertados exitosamente.\n";
    }
}
