#!/usr/bin/perl

print "\n";
print "Enter configuration file (php.ini) path\n";
print "(default = /etc/php5/apache2/php.ini): "; 
$phpini = <STDIN>;



if (length($phpini) == 1) {
	$phpini = "/etc/php5/apache2/php.ini";
}

print "\nyou entered: ".$phpini;
print "\nWe will now edit your php.ini";
print "\nThe orginial will be saved as php.ini.dist";
print "\n\n(Press enter to continue): ";
$continue = <STDIN>;

open(FILE, $phpini);
open(NEWFILE, ">ubuntu.php.ini");

while ($line = <FILE> ) {

	$autostart = index($line, "session.auto_start =");
	$shorttag = index($line, "short_open_tag =");
	$error = index($line, "error_reporting =");
	$semicolon = index($line, ";");
	$logerrors = index($line, "log errors = ");
	$errorlog = index($line, "error_log = ");


	if ($autostart >= 0 && $semicolon < 0) {
		$line = "session.auto_start = 1\n";
	} 

	if ($shorttag >= 0 && $semicolon < 0) {
		$line = "short_open_tag = On\n";
	}
	
	if (($error >= 0) && ($semicolon < 0)) {
		$line = "error_reporting = E_ALL\n";
	}

        if ($logerrors >= 0) {
		$line = "log_errors = On\n";
	}

	if ($errorlog >= 0) {
		$line = "error_log = /pos/backend/log/php.error\n"; 
	}

	print NEWFILE $line;

}

close(NEWFILE);
close(FILE);

rename $phpini, $phpini.".dist";

system ("mv ubuntu.php.ini ".$phpini);


