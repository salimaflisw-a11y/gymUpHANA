<?php

namespace App\Tests\Unit\Auth;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\AuthService;
use App\Shared\Enum\UserRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepo;
    private UserPasswordHasherInterface&MockObject $hasher;
    private AuthService $service;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->hasher   = $this->createMock(UserPasswordHasherInterface::class);
        $this->service  = new AuthService($this->userRepo, $this->hasher);
    }

    // ── Helper ──────────────────────────────────────────────────────────────

    private function makeUser(string $email = 'alice@gym.com', string $hash = 'hashed'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hash);
        $user->setRole(UserRole::MEMBER);
        $user->setStatus('ACTIVE');
        return $user;
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    public function testAuthenticateReturnsUserOnValidCredentials(): void
    {
        $user = $this->makeUser();

        $this->userRepo
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'alice@gym.com'])
            ->willReturn($user);

        $this->hasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'secret')
            ->willReturn(true);

        $result = $this->service->authenticate('alice@gym.com', 'secret');

        $this->assertSame($user, $result);
    }

    public function testAuthenticateNormalisesEmailToLowercase(): void
    {
        $this->userRepo
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'alice@gym.com'])
            ->willReturn(null);

        $result = $this->service->authenticate('  ALICE@GYM.COM  ', 'any');

        $this->assertNull($result, 'Unknown user should return null');
    }

    public function testAuthenticateReturnsNullWhenUserNotFound(): void
    {
        $this->userRepo->method('findOneBy')->willReturn(null);

        $result = $this->service->authenticate('nobody@gym.com', 'pass');

        $this->assertNull($result);
    }

    public function testAuthenticateReturnsNullOnWrongPassword(): void
    {
        $user = $this->makeUser();

        $this->userRepo->method('findOneBy')->willReturn($user);
        $this->hasher->method('isPasswordValid')->willReturn(false);

        $result = $this->service->authenticate('alice@gym.com', 'wrong');

        $this->assertNull($result);
    }
}
