<?php


namespace Tests\Unit\Http\Service;


use App\Events\MoneyExchangeEvent;
use App\Http\Service\PaymentsService;
use App\Repository\UserRepository;
use App\User;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $userRepositoryMock;

    private $eventDispatcherMock;

    private $service;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userRepositoryMock = \Mockery::mock(UserRepository::class);
        $this->eventDispatcherMock = \Mockery::mock(EventDispatcher::class);
        $this->service = new PaymentsService($this->userRepositoryMock, $this->eventDispatcherMock);
    }

    /**
     * @test
     */
    public function testHappyPath()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;

        $payer = $this->createUser($payerId, 100);
        $payee = $this->createUser($payeeId, 10);
        $event = new MoneyExchangeEvent($payer, $payee, $value);


        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn($payer);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);
        $this->userRepositoryMock->expects('performDebitForUser')->with($payer, $value);
        $this->userRepositoryMock->expects('performCreditForUser')->with($payee, $value);

        $this->eventDispatcherMock->expects('dispatch');

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertTrue($processed);
    }

    /**
     * @test
     */
    public function testOnDatabaseException()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;
        $exception = new Exception();

        $payer = $this->createUser($payerId, 100);
        $payee = $this->createUser($payeeId, 10);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn($payer);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);
        $this->userRepositoryMock->expects('performDebitForUser')->with($payer, $value);
        $this->userRepositoryMock->expects('performCreditForUser')->with($payee, $value)
            ->andThrow($exception);
        $this->expectExceptionMessage(PaymentsService::GENERIC_ERROR_MESSAGE);

        $this->service->performTransaction($payerId, $payeeId, $value);
    }

    /**
     * @test
     */
    public function testPayeeNotFound()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;

        $payer = $this->createUser($payerId, 100);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn($payer);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn(null);

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertFalse($processed);
    }

    /**
     * @test
     */
    public function testPayerNotFound()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;

        $payee = $this->createUser($payeeId, 100);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn(null);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertFalse($processed);
    }

    /**
     * @test
     */
    public function testOnSameIds()
    {
        $payerId = '1';
        $payeeId = '1';
        $value = 50.0;

        $payee = $this->createUser($payeeId, 100);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn(null);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertFalse($processed);
    }

    /**
     * @test
     */
    public function testOnPayerIsPJ()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;

        $payer = $this->createUser($payerId, 100, true);
        $payee = $this->createUser($payeeId, 10);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn($payer);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertFalse($processed);
    }

    /**
     * @test
     */
    public function testOnInsufficientBalance()
    {
        $payerId = '1';
        $payeeId = '2';
        $value = 50.0;

        $payer = $this->createUser($payerId, 2);
        $payee = $this->createUser($payeeId, 10);

        $this->userRepositoryMock->expects('findUserById')->with($payerId)->andReturn($payer);
        $this->userRepositoryMock->expects('findUserById')->with($payeeId)->andReturn($payee);

        $processed = $this->service->performTransaction($payerId, $payeeId, $value);

        $this->assertFalse($processed);
    }

    private function createUser($id, $balance, $isPJ = false)
    {
        $user = new User();
        $user->id = $id;
        $user->balance = $balance;
        $user->isPJ = $isPJ;

        return $user;
    }
}
