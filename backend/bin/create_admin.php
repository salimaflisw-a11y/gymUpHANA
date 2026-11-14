<?php

use App\Auth\Entity\User;
use App\Kernel;
use App\Shared\Enum\UserRole;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$factory = new PasswordHasherFactory([
    PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto'],
]);
$hasher = new UserPasswordHasher($factory);

$email = 'admin@gymup.com';
$plainPassword = 'AdminPassword123!';
$fullName = 'GymUp Administrator';

$repo = $em->getRepository(User::class);
$user = $repo->findOneBy(['email' => $email]);

if (!$user) {
    $user = new User();
    $user->setEmail($email);
    $user->setFullName($fullName);
    $user->setRole(UserRole::ADMIN);
    $user->setStatus('ACTIVE');
    $user->setPassword($hasher->hashPassword($user, $plainPassword));
    $em->persist($user);
    $em->flush();
    echo "SUCCESS: Admin user created.\n";
} else {
    $user->setFullName($fullName);
    $user->setRole(UserRole::ADMIN);
    $user->setStatus('ACTIVE');
    $user->setPassword($hasher->hashPassword($user, $plainPassword));
    $em->flush();
    echo "SUCCESS: Existing user updated to Admin.\n";
}

echo "EMAIL: {$email}\n";
echo "PASSWORD: {$plainPassword}\n";
