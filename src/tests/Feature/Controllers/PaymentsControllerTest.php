<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\PaymentsController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentsControllerTest extends TestCase
{
    private const PAYER_ID = 98;
    private const PAYEE_ID = 99;
    private const PJ_ID = 100;
    private const VALUE = 1;

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
}
