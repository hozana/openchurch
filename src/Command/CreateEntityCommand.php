<?php

namespace App\Command;

use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Model\Community;
use App\Place\Domain\Model\Place;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\ByteString;

#[AsCommand(name: 'app:create:entity', description: 'Create a new entity')]
class CreateEntityCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityClassName = $io->choice('Which entity?', [
            Agent::class,
            Community::class,
            Place::class,
        ]);

        $entity = new $entityClassName();

        if ($entityClassName === Agent::class) {
            assert($entity instanceof Agent);
            // Ask some more questions
            $entity->name = $io->ask('Name?');
            $entity->apiKey = ByteString::fromRandom(64)->toString();
        }

        $this->em->persist($entity);
        $this->em->flush();

        return self::SUCCESS;
    }
}
