<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

if (!class_exists('Git')) {
    include(__DIR__ . '/Git.php');
}

class UpdateMajorCommand extends Command
{
    protected function configure()
    {
        $this->setName('update:major')
            ->setDescription('Update to a new release version')
            ->addArgument('version', InputArgument::REQUIRED, 'Version number (e.g., 2.1');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->configValue("projectPath");
        if ($path && $path[0] != "/") {
            $path = __DIR__ . "/" . $path;
        }
        $git = new Git($path);
        $branch = $git->getCurrentBranch();
        $repo = $this->getApplication()->configValue('repo');
        if (!strstr($branch, 'version-')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("You're not running a stable version; are you sure you want to continue? (y/n) ", false);
            $answer = $helper->ask($input, $output, $question);
            if ($answer !== true) {
                return;
            }
        }
    
        try {
            // verify upstream is a remte
            $upstream = $git->remote('upstream');
        } catch (Exception $ex) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("Running: <comment>git remote add upstream {$repo}</comment>");
            }
            $git->addRemote('upstream', $repo);
        }

        $version = 'version-' . $input->getArgument('version');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git checkout upstream/{$version}</comment>");
        }
        try {
            $git->checkout($version);
            $output->writeln('<info>Update complete</info>');
        } catch (Exception $ex) {
            $output->writeln('<error>Update failed</error>');
            $output->writeln($ex->getMessage());
        }
    }
}

