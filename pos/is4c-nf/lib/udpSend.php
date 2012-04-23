<?php

/**
  Send a message via UDP
  @param $msg the message
  @param $port integer port
*/
function udpSend($msg,$port=9450){
	if (!function_exists("socket_create")) return;
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
function udpPoke($msg,$port=9450){
	udpSend($msg,$port);
	$recv = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_bind($recv,'127.0.0.1',$port+1);
	$from='';
	$buf='';
	$port=0;
	sock_recvfrom($recv,$buf,256,0,$from,$port);	
	return $buf;
}

?>
