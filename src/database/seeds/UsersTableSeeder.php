<?php

use Illuminate\Database\Seeder;
use Faker\Factory;
use \Illuminate\Support\Facades\Hash;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        $faker = Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $fakeCPF_CNPJ = $faker->numerify($faker->numberBetween(11111111111, 99999999999999));
            $password = Hash::make('toptal');

            User::create([
                'Name' => $faker->name,
                'email' => $faker->email,
                'password' => $password,
                'CPF/CNPJ' => $fakeCPF_CNPJ,
                'isCNPJ' => $faker->boolean,
                'saldo' => $faker->randomFloat(2, 0, 100)
            ]);
        }
    }
}
