#!/usr/bin/perl

system("ps x | grep /etc/init.d/ssd > process");
open(FILE, "process");
$i = 0;
while ($line = <FILE>) {
	if (index($line, "/etc/init.d/ssd") >= 0) {
		$i = 1;
	}
}
close(FILE);

if ($i == 0) {
	system("/etc/init.d/ssd");
}
