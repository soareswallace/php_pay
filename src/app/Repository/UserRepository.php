<?php


namespace App\Repository;


use App\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function performDebitForUser(User $user, float $value)
    {
        DB::table('users')->where('id', $user->id)->update(['balance' => $user->balance - $value]);
    }

    public function performCreditForUser(User $user, float $value)
    {
        DB::table('users')->where('id', $user->id)->update(['balance' => $user->balance + $value]);
    }

    public function findUserById($id)
    {
        return User::find($id);
    }
}
