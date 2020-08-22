<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\PaymentsController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use UsersTableSeeder;

class PaymentsControllerTest extends TestCase
{
    private const PAYER_ID = 10;
    private const PAYEE_ID = 12;
    private const PJ_ID = UsersTableSeeder::NUMBER_OF_INSTANCES;
    private const VALUE = 1;

    /**
     * @test
     */
    public function testOnHappyPath()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => self::PAYER_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::AUTHORIZED_MESSAGE]);
    }

    /**
     * @test
     */
    public function testOnSameIds()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => self::PAYER_ID,
            'payee' => self::PAYER_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::OPERATION_NOT_ALLOWED]);
    }

    /**
     * @test
     */
    public function testOnPJAsPayer()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => self::PJ_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::OPERATION_NOT_ALLOWED]);
    }

    /**
     * @test
     */
    public function testOnNoBalance()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => 100000000000000000,
            'payer' => self::PJ_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::OPERATION_NOT_ALLOWED]);
    }

    /**
     * @test
     */
    public function testOnPayerIdInvalid()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => 101,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::OPERATION_NOT_ALLOWED]);
    }

    /**
     * @test
     */
    public function testOnPayeeIdInvalid()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => self::PAYER_ID,
            'payee' => 101
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::OPERATION_NOT_ALLOWED]);
    }

    /**
     * @test
     */
    public function testOnPayeeKeyMissing()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payer' => self::PAYER_ID,
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::BAD_REQUEST_MESSAGE]);
    }

    /**
     * @test
     */
    public function testOnPayerKeyMissing()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => self::VALUE,
            'payee' => self::PAYEE_ID,
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::BAD_REQUEST_MESSAGE]);
    }

    /**
     * @test
     */
    public function testOnValueKeyMissing()
    {
        $response = $this->postJson('/api/transaction', [
            'payee' => self::PAYEE_ID,
            'payer' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::BAD_REQUEST_MESSAGE]);
    }

    /**
     * @test
     */
    public function testOnNotNumbers()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => 'x',
            'payer' => self::PAYER_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::BAD_REQUEST_MESSAGE]);
    }

    /**
     * @test
     */
    public function testOnNegativeNumbers()
    {
        $response = $this->postJson('/api/transaction', [
            'value' => -10,
            'payer' => self::PAYER_ID,
            'payee' => self::PAYEE_ID
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([PaymentsController::MESSAGE_KEY => PaymentsController::BAD_REQUEST_MESSAGE]);
    }
}
