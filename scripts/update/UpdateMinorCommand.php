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
        $git = new Git(__DIR__ . '/../../');
        $branch = $git->getCurrentBranch();
        if (!strstr($branch, 'version-')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("You're not running a stable version; are you sure you want to continue? (y/n) ", false);
            $answer = $helper->ask($input, $output, $question);
            if ($answer !== true) {
                return;
            }
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git pull --rebase https://github.com/CORE-POS/IS4C.git {$branch}</comment>");
        }
        $success = $git->pull('https://github.com/CORE-POS/IS4C.git', $branch);

        if ($success) {
            $output->writeln('<info>Update complete</info>');
        } else {
            $output->writeln('<error>Update failed</error>');
        }
    }
}

