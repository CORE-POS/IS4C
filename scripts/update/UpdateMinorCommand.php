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

class UpdateMinorCommand extends Command
{
    protected function configure()
    {
        $this->setName('update:minor')
            ->setDescription('Get minor updates to the current version');
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

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git pull --rebase {$repo} {$branch}</comment>");
        }
        $success = $git->pull($repo, $branch);

        if ($success) {
            $output->writeln('<info>Update complete</info>');
        } else {
            $output->writeln('<error>Update failed</error>');
        }
    }
}

