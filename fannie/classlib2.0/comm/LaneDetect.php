<?php

namespace COREPOS\Fannie\API\comm;

class LaneDetect
{
    /**
     * Send a UDP broadcast that the lanes will answer
     * @return [array] of answering IPs
     */
    public static function check()
    {
        $recv = self::getListener();
        self::sendBroadcast('core_detect');
        $lanes = self::getResponses($socket);

        return $lanes;
    }

    /**
     * This is the nuts and bolts of collecting the responses
     * It allows up to 5 seconds for responses to come in
     * and does some very basic validation of the responses
     * @param $socket [resource] UDP listening socket
     * @return [array] of responding IPs
     */
    private static function getResponses($socket)
    {
        $read = array($socket);
        $write = null;
        $err = null;
        $responses = array();
        while (true) {
            $ready = stream_select($read, $write, $err, 5, 0);
            if ($ready > 0) {
                $result = stream_socket_recvfrom($socket, 512, 0, $peer);
                if (self::validate($result, $peer)) {
                    $responses[] = self::getIP($result, $peer);
                }
            } else {
                break;
            }
        }

        return $responses;
    }

    /**
     * Wrapper function to create the UDP listening socket
     * It's in a separate function because there may need to
     * be some additional logic added for configuration options
     * @return [resource] UDP listening socket
     */
    private static function getListener()
    {
        // possibly this should be configurable rather than all interfaces
        return stream_socket_server("udp://0.0.0.0:9451", $errno, $errstr, STREAM_SERVER_BIND);
    }

    /**
     * Send a message to the subnet broadcast address
     * @param $msg [string]
     * @return result of the socket operation
     */
    private static function sendBroadcast($msg)
    {
        // possibly this also needs a configurable interface selection
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        $result = socket_sendto($sock, $msg, strlen($msg), 0, '255.255.255.255', 9450);

        return $result;
    }

    /**
     * Check that the message is valid. As implemented, this
     * is obviously not providing any measure of security
     * @param $msg [string] the message received
     * @param $host [string] ip:port
     * @param return [boolean]
     */
    private static function validate($msg, $host)
    {
        if (substr($msg, -2) != ';;') {
            // not expected format
            return false;
        }
        $sentIP = substr($msg, 0, strlen($msg) - 2);
        list($ip, $port) = explode(':', $peer);

        if ($sentIP != $ip) {
            // appears to be lying
            return false;
        }

        return true;
    }

    /**
     * Convert a message into an IP
     * Separated out in case a future, more security
     * oriented message format makes this more complicated
     * @param $msg [string] the message received
     * @param $host [string] ip:port
     * @return [string] ip
     */
    private static function getIP($msg, $peer)
    {
        return substr($msg, 0, strlen($msg) - 2);
    }

}

