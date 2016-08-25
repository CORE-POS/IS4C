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

class UpdateAutoCommand extends Command
{
    protected function configure()
    {
        $this->setName('update:auto')
            ->setDescription('Update to the latest version');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = new Git(__DIR__ . '/../../');
        $branch = $git->getCurrentBranch();
        $revs = $git->getRevisions();
        $last = array_pop($revs); 
    
        try {
            // verify upstream is a remte
            $upstream = $git->remote('upstream');
        } catch (Exception $ex) {
            $repo = $this->getApplication()->configValue('repo');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("Running: <comment>git remote add upstream {$repo}</comment>");
            }
            $git->addRemote('upstream', $repo);
        }
        $git->fetch('upstream');
        $git->tags('upstream');

        /*
        $version = 'version-' . $input->getArgument('version');
        
        $test_branch = 'test-' . $version . '-' . date('YmdHis');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git branch {$test_branch} {$branch}</comment>");
        }
        $git->branch($test_branch, $branch);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git checkout {$test_branch}</comment>");
        }
        $git->checkout($test_branch);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git pull --rebase https://github.com/CORE-POS/IS4C.git {$version}</comment>");
        }
        $git->pull('https://github.com/CORE-POS/IS4C.git', $version);

        $output->writeln("<info>You're on a new branch named</info> <error>{$test_branch}</error>");
        $output->writeln("\nTo get back to your previous environment run:");
        $output->writeln("<comment>git checkout {$branch}</comment>");
        $output->writeln("<comment>git branch -D {$test_branch}</comment>");
        $output->writeln("\nTo merge these changes into your previous environment run:");
        $output->writeln("<comment>git checkout {$branch}</comment>");
        $output->writeln("<comment>git merge {$test_branch}</comment>");
        $output->writeln("<comment>git branch -d {$test_branch}</comment>");
        $output->writeln("\nTo undo the merge run:");
        $output->writeln("<comment>git reset --hard {$last['sha1']}");
        */
    }
}

