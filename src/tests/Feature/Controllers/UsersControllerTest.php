<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\UsersController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsersControllerTest extends TestCase
{
    const PAYER_ID = '98';
    const PAYEE_ID = '99';
    const PJ_ID = '100';

    public function testOnHappyPath()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '1',
            'payer' => self::PAYER_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([UsersController::AUTHORIZED_MESSAGE]);
    }

    public function testOnSameIds()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '1',
            'payer' => self::PAYER_ID,
            'payee' => self::PAYER_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([UsersController::OPERATION_NOT_ALLOWED]);
    }

    public function testOnPJAsPayer()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '1',
            'payer' => self::PJ_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([UsersController::OPERATION_NOT_ALLOWED]);
    }

    public function testOnNoBalance()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '100000000000000000',
            'payer' => self::PJ_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([UsersController::OPERATION_NOT_ALLOWED]);
    }

    public function testOnPayerNotFound()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '1',
            'payer' => 'NotAValidId',
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([UsersController::OPERATION_NOT_ALLOWED]);
    }

    public function testOnPayeeNotFound()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => '1',
            'payer' => self::PAYER_ID,
            'payee' => 'NotAValidId'
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([UsersController::OPERATION_NOT_ALLOWED]);
    }
}
