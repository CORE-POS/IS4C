<?php

use Symfony\Component\Console\Application;

class ConfiguredApplication extends Application
{
    private $jsonConfig = array();
    public function configValue($key)
    {
        if (empty($this->jsonConfig)) {
            $json = file_get_contents(__DIR__ . '/config.json');
            $json = json_decode($json, true);
            $this->jsonConfig = $json ?: array();
        }

        return $this->jsonConfig[$key] ?: null;
    } 
}

