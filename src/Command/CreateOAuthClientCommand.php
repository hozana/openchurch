<?php

namespace App\Command;

use App\Entity\Client;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateOAuthClientCommand extends Command
{
    protected $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        $this->clientManager = $clientManager;

        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('oauth:client:create')
            ->setDescription('Create OAuth Client')
            ->addArgument(
                'grantType',
                InputArgument::REQUIRED,
                'Grant Type?'
            )
            ->addArgument(
                'redirectUri',
                InputArgument::OPTIONAL,
                'Redirect URI?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redirectUri = $input->getArgument('redirectUri');
        $grantType = $input->getArgument('grantType');

        /** @var Client $client */
        $client = $this->clientManager->createClient();
        $client->setRedirectUris($redirectUri ? [$redirectUri] : []);
        $client->setAllowedGrantTypes([$grantType]);
        $this->clientManager->updateClient($client);

        $output->writeln(sprintf('<info>The client <comment>%s</comment> was created with <comment>%s</comment> as public id and <comment>%s</comment> as secret</info>',
            $client->getId(),
            $client->getPublicId(),
            $client->getSecret()
        ));

        return 0;
    }
}
