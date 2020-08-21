<?php

namespace App\Http\Controllers;

use App\Http\Service\PaymentsService;
use Illuminate\Http\Request;
use Exception;

/**
 * Class PaymentsController
 * @package App\Http\Controllers
 */
class PaymentsController extends Controller
{
    public const PAYER_KEY = 'payer';
    public const PAYEE_KEY = 'payee';
    public const VALUE_KEY = 'value';
    public const MESSAGE_KEY = 'message';
    public const AUTHORIZED_MESSAGE = 'Autorizado';
    public const OPERATION_NOT_ALLOWED = 'Operação não permitida';

    private $paymentService;

    /**
     * PaymentsController constructor.
     * @param PaymentsService $paymentService
     */
    public function __construct(PaymentsService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @param Request $httpRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function transaction(Request $httpRequest)
    {
        $request = $httpRequest->json()->all();

        try {
            $payerId = $request[self::PAYER_KEY];
            $payeeId = $request[self::PAYEE_KEY];
            $value = $request[self::VALUE_KEY];

            $processed = false;

            if ($payerId !== $payeeId) {
                $processed = $this->paymentService->performTransaction($payerId, $payeeId, $value);
            }

            if ($processed) {
                return response()->json([self::MESSAGE_KEY => self::AUTHORIZED_MESSAGE], 200);
            }

            return response()->json([self::MESSAGE_KEY => self::OPERATION_NOT_ALLOWED], 403);
        } catch (Exception $exception) {
            return response()->json([self::MESSAGE_KEY => $exception->getMessage()], $exception->getStatusCode());
        }
    }
}
