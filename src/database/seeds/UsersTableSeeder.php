<?php

use Illuminate\Database\Seeder;
use Faker\Factory;
use \Illuminate\Support\Facades\Hash;
use App\User;

class UsersTableSeeder extends Seeder
{
    public const NUMBER_OF_INSTANCES = 30;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        $faker = Faker\Factory::create();

        $numberOfInstances = self::NUMBER_OF_INSTANCES;

        for ($i = 0; $i < $numberOfInstances; $i++) {
            $fakeCPF_CNPJ = $faker->numerify($faker->numberBetween(11111111111, 99999999999999));
            $password = Hash::make('toptal');

            User::create([
                'Name' => $faker->name,
                'email' => $faker->email,
                'password' => $password,
                'CPF/CNPJ' => $fakeCPF_CNPJ,
                'isPJ' => $i === $numberOfInstances - 1,
                'balance' => $faker->randomFloat(2, 1, 100)
            ]);
        }
    }
}
