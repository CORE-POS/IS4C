<?php

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use COREPOS\common\mvc\FormValueContainer;

include_once(__DIR__ . '/../../lib/AutoLoader.php');

class LoggingTester extends BasicCorePage
{
    protected $hardware_polling = false;

    public function preprocess()
    {
        return true;
    }

    public function body_content()
    {
        $this->addScript('logging.js');
        $this->addOnloadCommand('logging.refreshLog();');

        echo <<<HTML
<style>
#scalebox {
    display: none;
}
</style>
<p>
    <pre id="log"></pre>
</p>
<p>
    <button onclick="logging.phpFatal(); return false;">PHP Fatal Error</button>
</p>
<p>
    <button onclick="logging.phpSyntax(); return false;">PHP Syntax Error</button>
</p>
<p>
    <button onclick="logging.phpNotice(); return false;">PHP Notice</button>
</p>
<p>
    <button onclick="logging.sqlError(); return false;">SQL Error</button>
</p>
<p>
    <a href="" onclick="logging.runtimeError(); return false;">Javascript Error</a><br />
    <em>This is a link so the page will implicitly refresh</em>
</p>
HTML;
    }
}

if (basename(AutoLoader::ownURL()) == 'index.php') {
    $session = new WrappedStorage();
    $form = new FormValueContainer();
    $page = new LoggingTester($session, $form);
}

