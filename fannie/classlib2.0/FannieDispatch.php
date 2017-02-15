<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class FannieDispatch 
{

    /**
      Lookup custom permissions for a page 
    */
    static protected function authOverride(SQLManager $dbc, $op_db, $page_class)
    {
        $prep = $dbc->prepare('
            SELECT authClass
            FROM PagePermissons
            WHERE pageClass=?',
            $op_db);
        $auth = $dbc->getValue($prep, array($page_class), $op_db);

        return $auth ? $auth : false;
    }

    static protected function i18n()
    {
        if (function_exists('bindtextdomain') && defined('LC_MESSAGES')) {
            setlocale(LC_MESSAGES, "en_US");
            bindtextdomain('messages', realpath(dirname(__FILE__).'/../locale'));
            bind_textdomain_codeset('messages', 'UTF-8');
            textdomain('messages');
        }
    }

    static public function runPage($class)
    {
        $config = FannieConfig::factory();
        $logger = FannieLogger::factory();
        if ($config->get('SYSLOG_SERVER')) {
            $logger->setRemoteSyslog(
                $config->get('SYSLOG_SERVER'),
                $config->get('SYSLOG_PORT'),
                $config->get('SYSLOG_PROTOCOL')
            );
        }
        $op_db = $config->get('OP_DB');
        $dbc = FannieDB::get($op_db);

        // setup error logging
        COREPOS\common\ErrorHandler::setLogger($logger);
        COREPOS\common\ErrorHandler::setErrorHandlers();
        // initialize locale & gettext
        self::i18n();

        $obj = new $class();
        if ($dbc && $dbc->isConnected($op_db)) {
            /*
            $auth = self::authOverride($dbc, $op_db, $class);
            if ($auth) {
                $obj->setPermissions($auth);
            }
            */
        }
        $obj->setConfig($config);
        $obj->setLogger($logger);
        if (is_a($obj, 'FannieReportPage')) {
            $dbc = FannieDB::getReadOnly($op_db);
        }
        $obj->setConnection($dbc);
        $obj = self::twig($obj);
        $obj->draw_page();
    }

    static public function twig($obj)
    {
        if (!class_exists('Twig_Environment') || !method_exists($obj, 'setTwig')) {
            return $obj;
        }

        $refl = new ReflectionClass($obj);
        $path = dirname($refl->getFileName());
        $paths = array($path . DIRECTORY_SEPARATOR . 'twig', $path);
        if (!is_dir($paths[0])) {
            $paths = $paths[1];
        }
        $loader = new Twig_Loader_Filesystem($paths);
        $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'core.twig';
        $twig = new Twig_Environment($loader, array('cache'=>$temp));
        $twig->addExtension(new Twig_Extensions_Extension_I18n());
        $obj->setTwig($twig);

        return $obj;
    }

    /**
      Render the current page if appropriate
      The page is only shown if it's accessed
      directly rather than through an include().

      @param $custom_errors @deprecated
        This behavior is controlled by config variable
        FANNIE_CUSTOM_ERRORS. The optional parameter
        remains for th sake of compatibility but does
        not do anything. It will go away when all calls
        to this method have been cleaned up.
    */
    static public function conditionalExec($custom_errors=true)
    {
        $frames = debug_backtrace();
        // conditionalExec() is the only function on the stack
        if (count($frames) == 1) {
            // draw current page
            $page = basename(filter_input(INPUT_SERVER, 'PHP_SELF'));
            $class = substr($page,0,strlen($page)-4);
            if ($class != 'index' && class_exists($class)) {
                self::runPage($class);
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }
}

