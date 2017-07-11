<?php

use COREPOS\pos\lib\PrintHandlers\PrintHandler;

/**
  Stream raw bytes to printer that accepts
  RAW type connections
*/
class RemotePrinterRAW extends PrintHandler
{
    public function writeLine($text)
    {
        $host = CoreLocal::get('RemotePrintDevice');
        $port = 9100;
        if (strstr($host, ':')) {
            list($host, $port) = explode(':', $host, 2);
        }

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($sock === false) {
           return false;
        }

        $conneted = socket_connect($sock, $host, $port);
        if ($connected === false) {
            return false;
        }

        while (strlen($text) > 0) {
            $written = socket_write($sock, $text);
            if ($written === false || $written >= strlen($text)) {
                break;
            }
            $text = substr($text, $written);
        }

        return true;
    } 
}

