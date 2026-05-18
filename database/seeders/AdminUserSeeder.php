<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('slug', 'super-admin')->firstOrFail();

        $name  = 'Bechir Elbechir';
        $email = 'bechir@pishift.co';
        $pass  = 'NPQP72@CTF';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($pass),
                'role_id'  => $role->id,
                'color'    => $role->color ?? '#E74C3C',
                'initials' => strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $name), 0, 2)))),
            ]
        );

        // Ensure role is up to date even if the user already existed
        if ($user->role_id !== $role->id) {
            $user->update(['role_id' => $role->id]);
        }

        $this->command->info("Admin user ready: {$user->email}");
    }
}
