<?php

class GitStatus extends COREPOS\Fannie\API\FanniePlugin
{
    public $plugin_description = 'Provides a scheduled task to check for "git status" issues in the source folder.';

    public $plugin_settings = array(
        'GitStatusFetchWithSudo' => array(
            'label'=>'Fetch with sudo',
            'description'=>"Whether 'sudo' must be used when running 'git fetch' command.  Note that using sudo typically requires a corresponding entry in a 'sudoers' file, to obviate the need for password prompt.",
            'options' => array("Do not use sudo (e.g.: git fetch origin)" => 'false',
                               "Use sudo (e.g.: sudo -u owner git fetch origin)" => 'true'),
            'default'=>'false'),
    );
}
