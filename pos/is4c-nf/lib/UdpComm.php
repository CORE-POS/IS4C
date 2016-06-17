<?php

namespace COREPOS\pos\lib;

/**
  @class UdpComm
  UDP send & receive function
*/
class UdpComm 
{
    /**
      Send a message via UDP
      @param $msg the message
      @param $port integer port
    */
    static public function udpSend($msg,$port=9450)
    {
        if (!function_exists("socket_create")) {
            return;
        }
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $res = socket_sendto($sock, $msg, strlen($msg), 0, '127.0.0.1',$port);
        socket_close($sock);
    }

}

