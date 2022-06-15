<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:create-user';

    protected function execute(InputInterface $input, OutputInterface $output,): int
    {
        // ... put here the code to create the user

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('$2y$13$FCQCSeAu3za2uPZQqVJK4OqvY/UIiV5Lcv5MrK1DPy83VQt7SbAwe');
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $user = new User();
        $user->setUsername('user1');
        $user->setPassword('$2y$13$FCQCSeAu3za2uPZQqVJK4OqvY/UIiV5Lcv5MrK1DPy83VQt7SbAwe');
        $user->setRoles(['ROLE_USER']);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $user = new User();
        $user->setUsername('user2');
        $user->setPassword('$2y$13$FCQCSeAu3za2uPZQqVJK4OqvY/UIiV5Lcv5MrK1DPy83VQt7SbAwe');
        $user->setRoles(['ROLE_USER']);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $user = new User();
        $user->setUsername('user3');
        $user->setPassword('$2y$13$FCQCSeAu3za2uPZQqVJK4OqvY/UIiV5Lcv5MrK1DPy83VQt7SbAwe');
        $user->setRoles(['ROLE_USER']);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}