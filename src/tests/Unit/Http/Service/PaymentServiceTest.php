<?php


namespace Tests\Unit\Http\Service;


use App\Http\Service\PaymentsService;
use App\Repository\UserRepository;
use App\User;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var UserRepository|\Mockery\LegacyMockInterface|\Mockery\MockInterface  */
    private $userRepositoryMock;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|EventDispatcher  */
    private $eventDispatcherMock;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface  */
    private $loggerInterFaceMock;

    private $service;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userRepositoryMock = \Mockery::mock(UserRepository::class);
        $this->eventDispatcherMock = \Mockery::mock(EventDispatcher::class);
        $this->loggerInterFaceMock = \Mockery::mock(LoggerInterface::class);
        $this->service = new PaymentsService(
            $this->userRepositoryMock,
            $this->eventDispatcherMock,
            $this->loggerInterFaceMock
        );
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
    public function testOnException()
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

        $this->loggerInterFaceMock->expects('error')->with(
            PaymentsService::PAYMENT_SERVICE_TAG.$exception->getMessage()
        );

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
