<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\common;

if (!class_exists('\COREPOS\common\BaseLogger', false)) {
    include(dirname(__FILE__) . '/BaseLogger.php');
}

/**
  @class Logger
  This class only exists for the sake of tidiness. 
  The real functionality is in BaseLogger.

  If the official PSR classes are present, Logger
  formally implements the PSR-3 logging interface.
  Otherwise it defines the same method without
  the official interface hierarchy
*/
if (interface_exists('\Psr\Log\LoggerInterface')) {
    class Logger extends BaseLogger implements \Psr\Log\LoggerInterface
    {
        public function log($level, $message, array $context = array())
        {
            switch ($this->normalizeLevel($level)) {
                case \Psr\Log\LogLevel::EMERGENCY:
                    $this->emergency($message, $context);
                    break;
                case \Psr\Log\LogLevel::ALERT:
                    $this->alert($message, $context);
                    break;
                case \Psr\Log\LogLevel::CRITICAL:
                    $this->critical($message, $context);
                    break;
                case \Psr\Log\LogLevel::ERROR:
                    $this->error($message, $context);
                    break;
                case \Psr\Log\LogLevel::WARNING:
                    $this->warning($message, $context);
                    break;
                case \Psr\Log\LogLevel::NOTICE:
                    $this->notice($message, $context);
                    break;
                case \Psr\Log\LogLevel::INFO:
                    $this->info($message, $context);
                    break;
                case \Psr\Log\LogLevel::DEBUG:
                    $this->debug($message, $context);
                    break;
            }
        } 
    }
} else {
    class Logger extends BaseLogger 
    {
        public function log($level, $message, array $context = array())
        {
            switch ($this->normalizeLevel($level)) {
                case 'emergency':
                    $this->emergency($message, $context);
                    break;
                case 'alert':
                    $this->alert($message, $context);
                    break;
                case 'critical':
                    $this->critical($message, $context);
                    break;
                case 'error':
                    $this->error($message, $context);
                    break;
                case 'warning':
                    $this->warning($message, $context);
                    break;
                case 'notice':
                    $this->notice($message, $context);
                    break;
                case 'info':
                    $this->info($message, $context);
                    break;
                case 'debug':
                    $this->debug($message, $context);
                    break;
            }
        }
    }
}


