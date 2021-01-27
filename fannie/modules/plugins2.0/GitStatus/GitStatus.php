<?php

class GitStatus extends COREPOS\Fannie\API\FanniePlugin
{
    public $plugin_description = 'Provides a scheduled task to check for "git status" issues in the source folder.';

    public $plugin_settings = array(
        'GitStatusExecutable' => array(
            'label'=>'Git executable',
            'description'=>"Executable to use for Git.  Default of 'git' is fine in most cases.  If you need something else then it should probably be an absolute path.",
            'default'=>'git'),
        'GitStatusFetch' => array(
            'label'=>'Git fetch',
            'description'=>"Whether 'git fetch' should be ran at all.",
            'options' => array("Do not use 'git fetch' (check 'git status' only)" => 'false',
                               "Use 'git fetch' and compare local vs. remote" => 'true'),
            'default'=>'false'),
        'GitStatusFetchWithSudo' => array(
            'label'=>'Fetch with sudo',
            'description'=>"Whether 'sudo' must be used when running 'git fetch' command.  Note that using sudo typically requires a corresponding entry in a 'sudoers' file, to obviate the need for password prompt.",
            'options' => array("Do not use sudo (e.g.: git fetch origin)" => 'false',
                               "Use sudo (e.g.: sudo -u owner git fetch origin)" => 'true'),
            'default'=>'false'),
        'GitStatusDebug' => array(
            'label'=>'Debug mode',
            'description'=>"Whether to print debug messages.  Note that cron will send email only when there is output.  (The plugin/task does not itself directly send email.)",
            'options' => array("No debug messages (means no email unless problem is found)" => 'false',
                               "Include debug messages (means email is always sent)" => 'true'),
            'default'=>'false'),
    );
}
