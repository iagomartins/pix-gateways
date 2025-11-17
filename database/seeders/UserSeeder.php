<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Busca o primeiro gateway disponível
        $gatewayA = Gateway::where('type', 'subadq_a')->first();
        $gatewayB = Gateway::where('type', 'subadq_b')->first();

        // Usuário A - SubadqA
        if ($gatewayA) {
            User::updateOrCreate(
                ['email' => 'usuario.a@example.com'],
                [
                    'name' => 'Usuário A',
                    'email' => 'usuario.a@example.com',
                    'password' => Hash::make('password'),
                    'gateway_id' => $gatewayA->id,
                ]
            );
        }

        // Usuário B - SubadqA
        if ($gatewayA) {
            User::updateOrCreate(
                ['email' => 'usuario.b@example.com'],
                [
                    'name' => 'Usuário B',
                    'email' => 'usuario.b@example.com',
                    'password' => Hash::make('password'),
                    'gateway_id' => $gatewayA->id,
                ]
            );
        }

        // Usuário C - SubadqB
        if ($gatewayB) {
            User::updateOrCreate(
                ['email' => 'usuario.c@example.com'],
                [
                    'name' => 'Usuário C',
                    'email' => 'usuario.c@example.com',
                    'password' => Hash::make('password'),
                    'gateway_id' => $gatewayB->id,
                ]
            );
        }
    }
}

