<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BoutiqueSeeder::class,
            ClientSeeder::class,
            EmployeSeeder::class,
            LivreurSeeder::class,
            CommandeSeeder::class,
            FactureSeeder::class,
        ]);
    }
}