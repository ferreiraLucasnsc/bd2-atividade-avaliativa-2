<?php

namespace Database\Seeders;

use App\Models\Biblioteca;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BibliotecaAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with library administrators.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Administrador Principal',
                'email' => 'admin@biblioteca.local',
                'password' => 'secret123',
            ],
            [
                'name' => 'Administrador Secundário',
                'email' => 'admin2@biblioteca.local',
                'password' => 'secret123',
            ],
        ];

        foreach ($admins as $adminData) {
            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => $adminData['password'],
                'role' => 'admin',
            ]);

            $biblioteca = Biblioteca::create([
                'created_by' => $user->id,
                'nome' => $user->name . ' Biblioteca',
                'endereco' => 'Rua Exemplo, 123',
                'telefone' => '(31) 4002-8922',
                'email' => $user->email,
            ]);

            $user->bibliotecas()->attach($biblioteca->id, [
                'role' => 'owner',
            ]);
        }
    }
}
