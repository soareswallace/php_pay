<?php


namespace App\Repository;


use App\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public const USERS_TABLE_NAME = 'users';
    public const ID_COLUMN_NAME = 'id';
    public const BALANCE_COLUMN_NAME = 'balance';


    /**
     * @param User $user
     * @param float $value
     */
    public function performDebitForUser(User $user, float $value)
    {
        DB::table(self::USERS_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME, $user->id)->update([self::BALANCE_COLUMN_NAME => $user->balance - $value]);
    }

    /**
     * @param User $user
     * @param float $value
     */
    public function performCreditForUser(User $user, float $value)
    {
        DB::table(self::USERS_TABLE_NAME)
            ->where(self::ID_COLUMN_NAME, $user->id)->update([self::BALANCE_COLUMN_NAME => $user->balance + $value]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findUserById($id)
    {
        return User::find($id);
    }
}
