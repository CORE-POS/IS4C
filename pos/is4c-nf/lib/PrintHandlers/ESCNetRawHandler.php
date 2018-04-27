<?php

namespace COREPOS\pos\lib\PrintHandlers;

class ESCNetRawHandler extends ESCPOSPrintHandler
{
    private $port = 9100;
    private $host = false;

    public function setTarget($host)
    {
        if (strpos($host, ':')) {
            list($this->host, $this->port) = explode(':', $host, 2);
        } else {
            $this->host = $host;
        }
    }

    public function writeLine($text)
    {
        if (!function_exists('socket_create') || $this->host === false) {
            return 0;
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return 0;
        }

        socket_set_block($socket);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));
        if (!socket_connect($socket, $this->host, $this->port)) {
            return false;
        }

        while(true) {
            $numWritten = socket_write($socket, $text);
            if ($numWritten === false) {
                // error occurred
                break;
            }

            if ($numWritten >= strlen($text)) {
                // whole message has been sent
                break;
            }

            $text = substr($text, $numWritten);
        }

        socket_close($socket);
    }
}

