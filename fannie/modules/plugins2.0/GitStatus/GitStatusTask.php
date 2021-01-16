<?php

class GitStatusTask extends FannieTask
{
    public $name = 'Git Status';

    public $description = 'Looks for "git status" issues
(uncommitted changes etc.) in the source folder.

NOTE: This task is provided by the GitStatus plugin;
please see that for settings to control behavior.';

    public $default_schedule = array(
        'min' => 20,
        'hour' => 4,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    // NOTE: if you get output like the following then it probably means you
    // are *not* currently using 'sudo' to run 'git fetch' but that you do need
    // to use it.
    //
    //      error: cannot open .git/FETCH_HEAD: Permission denied
    //      failed to fetch remote!  return_var is 255; output is:
    //
    // if you instead get output like the following, then it means you *are*
    // using sudo, but have not yet configured the sudoers properly (below).
    //
    //      sudo: no tty present and no askpass program specified
    //      failed to fetch remote!  return_var is 1; output is:
    //
    // if you do use 'sudo' when running 'git fetch' (which is likely to be the
    // case) then you must also establish a 'sudoers' file entry, so that the
    // command can be ran with no need for password prompt. if this is the case
    // for you, then you must exlicitly allow www-data to do the fetch.  create
    // or edit a sudoers file specific to CORE:
    //
    //      sudo visudo -f /etc/sudoers.d/corepos
    //
    // and add the following entry, replacing "myself" with the appropriate
    // username, i.e. whoever is owner of the source folder:
    //
    //      www-data ALL = (myself) NOPASSWD: /usr/bin/git fetch origin

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->git = $settings['GitStatusExecutable'] or 'git';
        $this->debug = $settings['GitStatusDebug'] === 'true';

        // change to root dir of repo, to run our git commands
        $rootdir = realpath(__DIR__ . '/../../../..');
        if ($this->debug) {
            $this->stderr("git executable is: {$this->git}\n");
            $this->stderr("rootdir is: $rootdir\n");
        }
        chdir($rootdir);

        if (!$this->checkGitStatus()) {
            return;
        }

        if ($settings['GitStatusFetch'] !== 'true') {
            // no fetch, so can only check git status
            return;
        }

        if (!$this->identifyGitBranch($branch, $remote, $remoteBranch)) {
            return;
        }

        if (!$this->gitFetchRemote($remote)) {
            return;
        }

        if (!$this->checkGitDiff($branch, $remote, $remoteBranch)) {
            return;
        }

        if ($this->debug) {
            $this->stderr("made it to the end\n");
        }
    }

    private function stderr($text)
    {
        $fh = fopen('php://stderr', 'a');
        fwrite($fh, $text);
        fclose($fh);
    }

    private function checkGitStatus()
    {
        // use --porcelain so we can assume "empty output means clean status"
        exec("{$this->git} status --porcelain", $output, $return_var);

        if ($return_var) {
            $this->stderr("failed to check git status!  ");
            $this->showGitStatus();
            return false;
        }

        if ($output) {
            $this->stderr("git status is not clean!  ");
            $this->showGitStatus();
            $this->stderr("\n\nHINT: If you see \"untracked\" files above, which should not be\n"
                          . "\"officially\" ignored, but you would rather ignore for local status\n"
                          . "checks, then edit your .git/info/exclude file.\n");
            return false;
        }

        if ($this->debug) {
            $this->stderr("git status is clean\n");
        }
        return true;
    }

    private function showGitStatus()
    {
        exec("{$this->git} status", $output, $return_var);
        $this->showCommandResult($return_var, $output);
    }

    private function showCommandResult($return_var, $output)
    {
        $this->stderr("return_var is $return_var; output is:\n\n" . implode("\n", $output) . "\n");
    }

    private function identifyGitBranch(&$branch, &$remote, &$remoteBranch)
    {
        exec("{$this->git} status --branch --porcelain", $output, $return_var);

        if ($return_var) {
            $this->stderr("failed to identify git branch!  ");
            $this->showGitStatus();
            return false;
        }

        if (!$output) {
            $this->stderr("could not determine git branch!  ");
            $this->showGitStatus();
            return false;
        }

        // parse local branch from output
        $parts = explode('...', $output[0]);
        $branch = substr($parts[0], 3); // trim off first 3 chars, i.e. '## '

        // parse remote name, and remote branch
        $remoteParts = explode('/', $parts[1]);
        $remote = $remoteParts[0];
        $remoteBranch = $remoteParts[1];

        if ($this->debug) {
            $this->stderr("branch is: $branch\n");
            $this->stderr("remote is: $remote\n");
            $this->stderr("remoteBranch is: $remoteBranch\n");
        }
        return true;
    }

    private function identifyOwner(&$owner)
    {
        // do a simple "long list" of current folder
        exec('ls -ld .', $output, $return_var);

        if ($return_var) {
            $this->stderr("failed to identify folder owner!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if (!$output) {
            $this->stderr("got no output from folder list!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        // parse owner username from output
        $parts = explode(' ', $output[0]);
        $owner = $parts[2];

        if ($this->debug) {
            $this->stderr("folder owner is: $owner\n");
        }
        return true;
    }

    private function gitFetchRemote($remote)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $useSudo = $settings['GitStatusFetchWithSudo'] === 'true';

        // run git fetch command, with or without sudo
        if ($useSudo) {
            if (!$this->identifyOwner($owner)) {
                return false;
            }
            exec("sudo -u $owner -H {$this->git} fetch $remote", $output, $return_var);
        } else {
            exec("{$this->git} fetch $remote", $output, $return_var);
        }

        if ($return_var) {
            $this->stderr("failed to fetch remote!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if ($output) {
            $this->stderr("unexpected output when fetching remote!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if ($this->debug) {
            $this->stderr("remote commits were fetched\n");
        }
        return true;
    }

    private function checkGitDiff($branch, $remote, $remoteBranch)
    {
        // first look for remote commits not found in workdir
        exec("{$this->git} log ..$remote/$remoteBranch", $output, $return_var);

        if ($return_var) {
            $this->stderr("failed to check for unknown remote commits!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if ($output) {
            $this->stderr("$remote/$remoteBranch has commits not present in workdir!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        // next look for local commits not found in remote
        exec("{$this->git} log $remote/$remoteBranch..", $output, $return_var);

        if ($return_var) {
            $this->stderr("failed to check for unknown local commits!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if ($output) {
            $this->stderr("there are local commits not present in $remote/$remoteBranch!  ");
            $this->showCommandResult($return_var, $output);
            return false;
        }

        if ($this->debug) {
            $this->stderr("$remote/$remoteBranch and workdir match\n");
        }
        return true;
    }
}
