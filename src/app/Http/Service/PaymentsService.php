<?php


namespace App\Http\Service;


use App\Events\MoneyExchangeEvent;
use App\Http\Controllers\PaymentsController;
use App\Repository\UserRepository;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class PaymentsService
{
    public const AUTHORIZATION_URL = 'https://run.mocky.io/v3/8fafdd68-a090-496f-8c9a-3442cf30dae6';
    public const AUTHORIZED_MESSAGE = 'Autorizado';
    public const GENERIC_ERROR_MESSAGE = 'Operação não pode ser realizada';
    public const PAYMENT_SERVICE_TAG = "[PAYMENT_SERVICE] - ";

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentsController constructor.
     * @param UserRepository $userRepository
     * @param EventDispatcher $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        UserRepository $userRepository,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param string $payerId
     * @param string $payeeId
     * @param float $value
     * @return bool
     */
    public function performTransaction(string $payerId, string $payeeId, float $value)
    {

        try {
            /** @var User $user */
            $payer = $this->userRepository->findUserById($payerId);
            $payee = $this->userRepository->findUserById($payeeId);

            if ($payee !== null && $payer !== null && $this->isPayerAblePerformToTransaction($payer, $payee, $value)) {
                DB::beginTransaction();

                $this->userRepository->performDebitForUser($payer, $value);
                $this->userRepository->performCreditForUser($payee, $value);

                if ($this->isAuthorized()) {
                    DB::commit();
                    $this->sendNotificationToAnUser($payer, $payee, $value);
                    return true;
                }
            }

            return false;
        } catch (Exception $exception) {
            DB::rollBack();
            $this->logger->error(self::PAYMENT_SERVICE_TAG.$exception->getMessage());
            //Throw a sentry
            throw new HttpException(503, self::GENERIC_ERROR_MESSAGE);
        }
    }

    /**
     * Checks if the incoming payer user is able to perform the transaction.
     *
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
     * Asks a external service for authorization.
     *
     * @return bool
     */
    private function isAuthorized()
    {
        $authorization = Http::get(self::AUTHORIZATION_URL)->json();

        return $authorization[PaymentsController::MESSAGE_KEY] === self::AUTHORIZED_MESSAGE;
    }

    /**
     * Throws an event to send notification to the receiver.
     *
     * @param User $payer
     * @param User $payee
     * @param float $value
     */
    private function sendNotificationToAnUser(User $payer, User $payee, float $value)
    {
        $event = new MoneyExchangeEvent($payer, $payee, $value);
        $this->eventDispatcher->dispatch($event, MoneyExchangeEvent::MONEY_EXCHANGE_EVENT);
    }
}
