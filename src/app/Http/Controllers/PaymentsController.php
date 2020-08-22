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
    public const BAD_REQUEST_MESSAGE = 'Formato Inválido';

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
        $isAValidRequest = $this->validateFields($request);

        if ($isAValidRequest) {
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
        } else {
            return response()->json([self::MESSAGE_KEY => self::BAD_REQUEST_MESSAGE], 400);
        }
    }

    /**
     * @param $request
     * @return bool
     */
    private function validateFields($request)
    {
        return $this->validateCorrectKeys($request) &&
            $this->hasValuesNumerics($request) &&
            $this->hasPositiveValues($request);
    }

    /**
     * @param $request
     * @return bool
     */
    private function validateCorrectKeys($request)
    {
        return array_key_exists(self::VALUE_KEY, $request) &&
            array_key_exists(self::PAYEE_KEY, $request) &&
            array_key_exists(self::PAYER_KEY, $request);
    }

    /**
     * @param $request
     * @return bool
     */
    private function hasValuesNumerics($request)
    {
        return is_numeric($request[self::PAYER_KEY]) &&
            is_numeric($request[self::PAYEE_KEY]) &&
            is_numeric($request[self::VALUE_KEY]);
    }

    /**
     * @param $request
     * @return bool
     */
    private function hasPositiveValues($request)
    {
        return $request[self::PAYER_KEY] > 0 &&
            $request[self::PAYEE_KEY] > 0 &&
            $request[self::VALUE_KEY] > 0;
    }
}
