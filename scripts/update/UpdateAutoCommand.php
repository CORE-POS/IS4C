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
            ->setDescription('Update to the latest version')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Automatically resolve merge conflicts', null);
    }

    protected function goodTags($tags)
    {
        $tags = array_filter($tags, function ($i) { return preg_match('/^\d+\.\d+\.\d+/', $i); });
        usort($tags, array('UpdateAutoCommand', 'semVarSort'));

        return array_reverse($tags);
    }

    public static function semVarSort($a, $b)
    {
        if (preg_match('/^(\d+\.\d+\.\d+)/', $a, $matches)) {
            $a = $matches[1];
        } else {
            return 0;
        }
        if (preg_match('/^(\d+\.\d+\.\d+)/', $b, $matches)) {
            $b = $matches[1];
        } else {
            return 0;
        }
        list($a_maj, $a_min, $a_rev) = explode('.', $a);
        list($b_maj, $b_min, $b_rev) = explode('.', $b);
        if ($a_maj < $b_maj) {
            return -1;
        } elseif ($a_maj > $b_maj) {
            return 1;
        } elseif ($a_min < $b_min) {
            return -1;
        } elseif ($a_min > $b_min) {
            return 1;
        } elseif ($a_rev < $b_rev) {
            return -1;
        } elseif ($a_rev > $b_rev) {
            return 1;
        }
        return 0;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->configValue("projectPath");
        if ($path && $path[0] != "/") {
            $path = __DIR__ . "/" . $path;
        }
        $git = new Git($path);
        $branch = $git->getCurrentBranch();
        $revs = $git->getRevisions();
        $last = array_pop($revs); 
        $repo = $this->getApplication()->configValue('repo');
    
        try {
            // verify upstream is a remte
            $upstream = $git->remote('upstream');
        } catch (Exception $ex) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("Running: <comment>git remote add upstream {$repo}</comment>");
            }
            $git->addRemote('upstream', $repo);
        }

        $git->fetch('upstream');
        $tags = $git->tags('upstream');
        $tags = $this->goodTags($tags);
        $latest = $tags[0];
        $current = false;
        if (file_exists($path . '/composer.json')) {
            $composer = file_get_contents($path . '/composer.json');
            $composer = json_decode($composer, true);
            if (isset($composer['version'])) {
                $current = $composer['version'];
            }
        }
        if (!$current) {
            $current = trim(file_get_contents(__DIR__ . '/' . $this->getApplication()->configValue("versionFile")));
        }
        if (!preg_match('/^(\d+.\d+.\d+)/', $current, $matches)) {
            $output->writeln("<error>Version {$current} is not semVar</error>");
            return;
        }
        $current = $matches[0];
        if (self::semVarSort($current, $latest) != -1) {
            $output->writeln("<info>Version {$current} is up-to-date</info>");
            return;
        }

        $test_branch = 'snapshot-' . $current . '-' . date('Y-m-d-His');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Running: <comment>git branch {$test_branch} {$branch}</comment>");
        }
        $git->branch($test_branch, $branch);

        $force = $input->getOption('force');
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $force_cmd = $force ? ' -s recursive -Xtheirs ' : ' ';
            $output->writeln("Running: <comment>git pull{$force_cmd}{$repo} {$latest}</comment>");
        }

        $updated = $git->pull($repo, $latest, false, $force);
        if ($updated !== true) {
            $output->writeln("<error>Unable to complete update</error>");
            $output->writeln("Details:");
            foreach (explode("\r\n", $updated) as $line) {
                $output->writeln($line);
            }
        } else {
            $output->writeln("\nTo get back to your previous environment temporarily run:");
            $output->writeln("<comment>git checkout {$test_branch}</comment>");
            $output->writeln("\nTo undo this update permanently run:");
            $output->writeln("<comment>git reset --hard {$last['sha1']}");
        }
    }
}

