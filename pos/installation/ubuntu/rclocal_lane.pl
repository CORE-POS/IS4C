#!/usr/bin/perl

open(FILE, "/etc/init.d/rc.local");

$ssd = -1;
$lptty = -1;

while ($line = <FILE>) {

	if (index($line, "/ssd") >= 0) {
		$ssd = 1;
	}
	if (index($line, "/lptty") >= 0) {
		$lptty = 1;
	}
}

close(FILE);

open(FILE, ">>/etc/init.d/rc.local");

if ($ssd < 0) {
	print FILE "\n\n/etc/init.d/ssd";
}

if ($lptty < 0) {
	print FILE "\n/etc/init.d/lptty";
}

close(FILE);
