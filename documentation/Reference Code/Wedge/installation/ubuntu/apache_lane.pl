#!/usr/bin/perl

system("cp /pos/installation/ubuntu/is4c.conf /etc/apache2/conf.d/is4c.conf");

print "\n\nYour DirectoryIndex has been set to login.php index.php";
print "\nServerName set to 127.0.0.1";
print "\n\nWe will Edit the location of your web root,";
print "\nassuming your config file is /etc/apache2/sites-available/default.";
print "\nOriginal file will be saved as default.dist";
print "\n\ny = continue, n = abort. edit by hand later (default y): ";
$continue = <STDIN>;

if (length($continue) == 1) {
	$continue = "Y\n";
}

chop($continue);

if (uc($continue) eq "Y" || uc($continue) eq "YES") {
	
	$webconfig = "/etc/apache2/sites-available/default";
	open(FILE, $webconfig);
	open(NEWFILE, ">default.ubuntu");
	
	$currentroot = "no match";

	while ($line = <FILE>) {


		$dr = index($line, "DocumentRoot /");
		if ($dr >= 0) {
			$currentroot = substr($line, 0, $dr + 13);
			chop($currentroot);
			$line =  substr($line, 0, $dr + 13)."/pos/is4c/\n";
		}
		$dir = index($line, "<Directory /");
		$crposition = index($line, $currentroot);
		if ($dir >= 0 && $crposition >= 0) {
			$line = substr($line, 0, $dir + 11)."/pos/is4c/>\n";
		}
		print NEWFILE $line;		
		
	}

	close(NEWFILE);
	close(FILE);

	print "\n\nWeb root changed to /pos/is4c/\n";

	system("cp /etc/apache2/sites-available/default /etc/apache2/sites-available/default.dist");
	system("mv default.ubuntu /etc/apache2/sites-available/default");
	system("/etc/init.d/apache2 restart");

}
