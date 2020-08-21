<?php

namespace App\Http\Controllers;

use App\Events\MoneyExchangeEvent;
use App\Repository\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UsersController
 * @package App\Http\Controllers
 */
class UsersController extends Controller
{
    const AUTHORIZATION_URL = 'https://run.mocky.io/v3/8fafdd68-a090-496f-8c9a-3442cf30dae6';
    const PAYER_KEY = 'payer';
    const PAYEE_KEY = 'payee';
    const VALUE_KEY = 'value';
    const MESSAGE_KEY = 'message';
    const AUTHORIZED_MESSAGE = 'Autorizado';
    const EXTERNAL_SERVICE_ERROR = 'Serviço externo não autorizou operação.';
    const OPERATION_NOT_ALLOWED = 'Operação não permitida';
    const EXCEPTION_ERROR_MSG = 'Erro na operação';

    private $userRepository;

    private $eventDispatcher;

    /**
     * UsersController constructor.
     * @param UserRepository $userRepository
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(UserRepository $userRepository, EventDispatcher $eventDispatcher)
    {
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function printPayerName(Request $request)
    {
        $payerId = $request->input('payer');
        $user = User::find($payerId)->toJson(JSON_PRETTY_PRINT);


        return response()->json([
            $user
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function isPJ(Request $request)
    {
        $payerId = $request->input('id');

        $user = User::find($payerId);

        return response()->json([
            $user->isPJ ? "true" : "false"
        ], 200);
    }

    /**
     * @param Request $httpRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function transaction(Request $httpRequest)
    {
        try {
            $request = $httpRequest->json()->all();

            $payerId = $request[self::PAYER_KEY];
            $payeeId = $request[self::PAYEE_KEY];
            $value = $request[self::VALUE_KEY];

            /** @var User $user */
            $payer = $this->userRepository->findUserById($payerId);
            $payee = $this->userRepository->findUserById($payeeId);

            if ($this->isAblePerformToTransaction($payer, $payee, $value)) {
                DB::beginTransaction();

                $this->userRepository->performDebitForUser($payer, $value);
                $this->userRepository->perforCreditForUser($payee, $value);

                if ($this->isAuthorized()) {
                    DB::commit();
                    $this->sendNotificationToTheUser($payer, $payee, $value);
                    return response()->json([self::AUTHORIZED_MESSAGE], 200);
                } else {
                    throw new HttpException(403, self::EXTERNAL_SERVICE_ERROR);
                }
            } else {
                return response()->json([self::OPERATION_NOT_ALLOWED], 403);
            }
        } catch (\Exception $exception) {
            DB::rollback();
            //Logar e lançar um Sentry com a mensagem de erro que vem na exception
            return response()->json([self::EXCEPTION_ERROR_MSG], $exception->getStatusCode());
        }
    }

    /**
     * @param User $payer
     * @param User $payee
     * @param float $value
     * @return bool
     */
    private function isAblePerformToTransaction(User $payer, User $payee, float $value)
    {
        return ($payee->id !== $payer->id) && (!$payer->isPJ) && ($payer->balance - $value >= 0);
    }

    /**
     * @return bool
     */
    private function isAuthorized()
    {
        $authorization = Http::get(self::AUTHORIZATION_URL)->json();

        return $authorization[self::MESSAGE_KEY] === 'Autorizado';
    }

    /**
     * @param User $payer
     * @param User $payee
     * @param float $value
     */
    private function sendNotificationToTheUser(User $payer, User $payee, float $value)
    {
        $event = new MoneyExchangeEvent($payer, $payee, $value);
        $this->eventDispatcher->dispatch($event, MoneyExchangeEvent::EVENT_NAME);
    }
}
