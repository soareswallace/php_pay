<?php


namespace App\Http\Service;


use App\Events\MoneyExchangeEvent;
use App\Http\Controllers\PaymentsController;
use App\Repository\UserRepository;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class PaymentsService
{
    public const AUTHORIZATION_URL = 'https://run.mocky.io/v3/8fafdd68-a090-496f-8c9a-3442cf30dae6';
    public const AUTHORIZED_MESSAGE = 'Autorizado';
    public const GENERIC_ERROR_MESSAGE = 'Operação não pode ser realizada';

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * PaymentsController constructor.
     * @param UserRepository $userRepository
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        UserRepository $userRepository,
        EventDispatcher $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $payerId
     * @param string $payeeId
     * @param float $value
     * @return bool
     */
    public function performTransaction(string $payerId, string $payeeId, float $value)
    {
        /** @var User $user */
        $payer = $this->userRepository->findUserById($payerId);
        $payee = $this->userRepository->findUserById($payeeId);

        try {
            if ($payee !== null && $payer !== null && $this->isPayerAblePerformToTransaction($payer, $payee, $value)) {
                DB::beginTransaction();

                $this->userRepository->performDebitForUser($payer, $value);
                $this->userRepository->perforCreditForUser($payee, $value);

                if ($this->isAuthorized()) {
                    DB::commit();
                    $this->sendNotificationToTheUser($payer, $payee, $value);
                    return true;
                }
            }

            return false;
        } catch (Exception $exception) {
            DB::rollBack();
            //Logar e lançar um Sentry com a mensagem de erro que vem na exception
            throw new HttpException(403, self::GENERIC_ERROR_MESSAGE);
        }
    }

    /**
     * @param User $payer
     * @param User $payee
     * @param float $value
     * @return bool
     */
    private function isPayerAblePerformToTransaction(User $payer, User $payee, float $value)
    {
        return ($payee->id !== $payer->id) && (!$payer->isPJ) && ($payer->balance - $value >= 0);
    }

    /**
     * @return bool
     */
    private function isAuthorized()
    {
        $authorization = Http::get(self::AUTHORIZATION_URL)->json();

        return $authorization[PaymentsController::MESSAGE_KEY] === self::AUTHORIZED_MESSAGE;
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
