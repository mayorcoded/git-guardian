<?php

namespace Gioffreda\Component\GitGuardian\Command;

use Gioffreda\Component\GitGuardian\Adapter\BitBucketRemote;
use Gioffreda\Component\GitGuardian\Adapter\GitHubRemote;
use Gioffreda\Component\GitGuardian\Event\GitRepositoryEvent;
use Gioffreda\Component\GitGuardian\GitGuardian;
use League\Event\EmitterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GitGuardianCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('git:guardian:clone-all')
            ->setDescription('Fetches all the repositories for the given users')
            ->addArgument('owner', InputArgument::IS_ARRAY, 'The owner or owners of the repositories')
            ->addOption('adapter', null, InputOption::VALUE_REQUIRED, 'The adapter to use', 'BitBucket')
            ->addOption('client-id', null, InputOption::VALUE_REQUIRED, 'The client ID (BitBucket only)')
            ->addOption('client-secret', null, InputOption::VALUE_REQUIRED, 'The client secret (BitBucket only)')
            ->addOption('personal-token', null, InputOption::VALUE_REQUIRED, 'The personal access token (GitHub only)')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'The destination where to clone to', '.cloned')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!in_array($input->getOption('adapter'), ['BitBucket', 'GitHub'])) {
            throw new InvalidArgumentException(sprintf(
                'The given adapter "%s" is not supported (yet)',
                $input->getOption('adapter')
            ));
        }

        $guardian = new GitGuardian();
        $emitter = $guardian->getEmitter();

        if ('GitHub' === $input->getOption('adapter')) {
            $users = $input->getArgument('owner');
            if (count($users) && !strstr($users[0], '/')) {
                $username = array_shift($users);
                $gitHubCredentials = [
                    'personal_token' => $input->getOption('personal-token'),
                    'username' => $username
                ];
                $remote = new GitHubRemote();
                $remote->setEmitter($emitter);
                $remote->setOptions($gitHubCredentials);
                $guardian->addRemote($remote);
            }

            foreach ($users as $owner) {
                $remote = new GitHubRemote();
                $remote->setEmitter($emitter);
                $remote->setUser($owner);
                $guardian->addRemote($remote);
            }
        } else {
            $bitBucketCredentials = [
                'client_id' => $input->getOption('client-id'),
                'client_secret' => $input->getOption('client-secret')
            ];

            foreach ($input->getArgument('owner') as $owner) {
                $remote = new BitBucketRemote();
                $remote->setEmitter($emitter);
                $remote->setOptions($bitBucketCredentials);
                $remote->setUser($owner);
                $guardian->addRemote($remote);
            }
        }

        $this->setupListeners($emitter, $output);
        $guardian->cloneAll($input->getOption('destination'));
    }

    // internal stuff

    protected function setupListeners(EmitterInterface $emitter, OutputInterface $output)
    {
        if (!$output->isVerbose()) {
            return;
        }

        $emitter->addListener('git_remote.repository_discovery', function ($event) use ($output) {
            /** @var GitRepositoryEvent $event */
            $output->writeln(sprintf(
                'Discovered repository <info>%s</info> at <comment>%s</comment>',
                $event->getRepository()->getName(),
                $event->getRepository()->getAnonymousUri()
            ));
            if ($output->isVeryVerbose()) {
                $data = $event->getData();
                $output->writeln(sprintf(
                    'Repository definition: %s',
                    json_encode($data['definition'], JSON_PRETTY_PRINT)
                ));
            }
        });

        $emitter->addListener('git_guardian.pre_clone_repository', function ($event) use ($output) {
            /** @var GitRepositoryEvent $event */
            $data = $event->getData();
            $output->write(sprintf(
                'Cloning <info>%s</info> into <comment>%s</comment> ',
                $event->getRepository()->getName(),
                $data['path']
            ));
        });

        $emitter->addListener('git_guardian.create_git', function ($event) use ($output) {
            /** @var GitRepositoryEvent $event */
            $output->write(sprintf(
                'Preparing Git for <info>%s</info> in <comment>%s</comment> ',
                $event->getRepository()->getName(),
                $event->getGit()->getPath()
            ));
        });

        $emitter->addListener('git_guardian.exception_repository', function ($event) use ($output) {
            /** @var GitRepositoryEvent $event */
            $data = $event->getData();
            $output->writeln(sprintf('[<error>Errored: %s</error>]', $data['exception']->getMessage()));
        });

        $emitter->addListener('git_guardian.config_skip_repository', function () use ($output) {
            /** @var GitRepositoryEvent $event */
            $output->writeln('[<info>Skipped</info>]');
        });

        $emitter->addListener('git_guardian.post_fetch_repository', function () use ($output) {
            /** @var GitRepositoryEvent $event */
            $output->writeln('[<info>Fetched</info>]');
        });
    }
}
