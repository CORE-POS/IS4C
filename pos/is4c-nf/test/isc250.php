<?php
	echo '<html><body><pre>';
	echo 'start'."\n";
	$s=socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($s) {
		echo 'socket_create'."\n";
		$c=socket_connect($s, '192.168.0.101', '12000');
		if ($c) {
			echo 'socket_connect'."\n";
			// Send "online" to terminal
			$msg=chr(2).'01.'.'0000'.'0000'.chr(3).('0'^'1'^'.'^'0'^'0'^'0'^'0'^'0'^'0'^'0'^'0'^chr(3));
			$r=socket_send($s, $msg, strlen($msg), 0);
			if ($r) {
				echo $r."\n";
				// Send $10.25............................$10.25 to the 1st line of the form display
				$msg=chr(2).'28.91000104$10.25............................$10.25'.chr(3).(
					'2'^'8'^'.'^'9'^'1'^'0'^'0'^'0'^'1'^'0'^'4'^'$'^'1'^'0'^'.'^'2'^'5'^
					'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^'.'^
					'$'^'1'^'0'^'.'^'2'^'5'^chr(3)
				);
				$r2=socket_send($s, $msg, strlen($msg), 0);
				if ($r2) {
					echo $r2."\n";
					sleep(5);
					// Send Status query to terminal
					$msg=chr(2).'11.'.chr(3).('1'^'1'^'.'^chr(3));
					$r2b=socket_send($s, $msg, strlen($msg), 0);
					if ($r2b) {
						echo $r2b."\n";
						// Read Status response from terminal
						
						// TODO - Find response packet size
						$resp=socket_read($s, 100);
						if ($resp) {
							// First couple of bytes are ACK from previous commands
							// echo ord(substr($resp, 0, 1))."(ACK)";
							// ACK for online message
							echo $resp."\n";
						} else {
							echo 'resp fail'."\n";
						}
					} else {
						echo 'r2b fail'."\n";
					}
				} else {
					echo 'r2 fail'."\n";
				}
			} else {
				echo 'socket_send_fail'."\n";
			}
			sleep(15);
			// Send "offline" to terminal
			$msg=chr(2).'00.'.'0000'.chr(3).('0'^'0'^'.'^'0'^'0'^'0'^'0'^chr(3));
			$r3=socket_send($s, $msg, strlen($msg), 0);
			if ($r3) {
				echo $r3."\n";
			} else {
				echo 'r3 fail'."\n";
			}
			socket_close($s);
		} else {
			echo 'socket_connect_fail'."\n";
		}
	} else {
		echo socket_strerror(socket_last_error())."\n";
	}
	
	/*
	 * You should receive something like:
	 * 
start
socket_create
socket_connect
14
54
6
01.00000000,01.00000000,
10
	 */
	
	echo '</pre><a href="./index.php">back</a></body></html>';