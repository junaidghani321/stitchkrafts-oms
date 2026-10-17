<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreateUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'type' => UserType::Admin,
                'password' => bcrypt('Admin@123'),
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@gmail.com',
                'type' => UserType::Manager,
                'password' => bcrypt('Manager@123'),
            ],
            [
                'name' => 'Employee User',
                'email' => 'employee@gmail.com',
                'type' => UserType::Employee,
                'designation' => 'Digitizer',
                'department' => 'Digitizing',
                'password' => bcrypt('Employee@123'),
            ],
            [
                'name' => 'User',
                'email' => 'user@gmail.com',
                'type' => UserType::Customer,
                'password' => bcrypt('User@123'),
            ],
        ];

        // forceCreate, not create: `type` is a privilege column and is
        // deliberately excluded from the model's fillable list.
        foreach ($users as $user) {
            User::forceCreate($user);
        }
    }
}
