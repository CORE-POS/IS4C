<?php

/**
  @class UdpComm
  UDP send & receive function
*/
class UdpComm extends LibraryClass {

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

/**
  Bi-directional UDP communication
  @param $msg the message
  @param $port integer port
  @return the response or an empty string

  Whatever program is listening on the other
  end cannot respond on the same port. It must
  send the response on (port+1).
*/
static public function udpPoke($msg,$port=9450)
{
	$socket = stream_socket_server("udp://127.0.0.1:".($port+1), 
		$errno, $errstr, STREAM_SERVER_BIND);	
	self::udpSend($msg,$port);
	$read = array($socket);
	$write = null;
	$except = null;
	$ready = stream_select($read,$write,$except,0,500);
	$buf = "";
	if ($ready > 0) {
		$buf = stream_socket_recvfrom($socket, 1024, 0, $peer);
    }
	stream_socket_shutdown($socket,STREAM_SHUT_RDWR);
	return $buf;
}

}

