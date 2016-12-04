#!/bin/env/php
<?php

/**
  Composer depedencies are required. Offer to
  install composer itself as well as its
  managed dependencies
*/
chdir(__DIR__ . '/../');
if (!is_dir('vendor') || !file_exists('vendor/autoload.php')) {
    echo "Composer dependencies are missing\n";
    if (!file_exists('composer.phar')) {
        echo "Install composer now? (y/n):";
        $inp = rtrim(fgets(STDIN));
        if ($inp !== 'y' && $inp !== 'Y') {
            echo "Please run \"composer install\" to install dependencies\n";
            exit;
        } else {
            copy('https://getcomposer.org/installer', 'composer-setup.php');
            passthru('php composer-setup.php');
            unlink('composer-setup.php');
        }
    }

    if (file_exists('composer.phar')) {
        echo "Install dependencies now? (y/n):";
        $inp = rtrim(fgets(STDIN));
        if ($inp !== 'y' && $inp !== 'Y') {
            echo "Please run \"composer install\" to install dependencies\n";
            exit;
        } else {
            passthru('composer.phar install');
            echo "============================\n";
            echo "Please re-run update.php script";
            exit;
        }
    }
}

include(__DIR__ . '/../vendor/autoload.php');
include(__DIR__ . '/update/ConfiguredApplication.php');
include(__DIR__ . '/update/VersionCommand.php');
include(__DIR__ . '/update/UpdateAutoCommand.php');
include(__DIR__ . '/update/UpdateMinorCommand.php');
include(__DIR__ . '/update/UpdateMajorCommand.php');
include(__DIR__ . '/update/UpdateDevCommand.php');

$application = new ConfiguredApplication('CORE Updater');
$application->add(new VersionCommand());
$application->add(new UpdateAutoCommand());
$application->add(new UpdateMinorCommand());
$application->add(new UpdateMajorCommand());
$application->add(new UpdateDevCommand());
$application->run();
