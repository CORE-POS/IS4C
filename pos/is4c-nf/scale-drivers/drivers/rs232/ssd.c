/*******************************************************************************

	Copyright 2001, 2004 Wedge Community Co-op
	This file is part of IS4C.

	IS4C is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	IS4C is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	in the file license.txt along with IS4C; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
********************************************************************************

   This is the preliminary daemon that effectively acts as part of the
   driver for the single cable Magellan scanner scale on the Linux platform.
   The settings for the serial port communication parameters are
   based on the factory defaults for Magellan. The polling behaviour,
   likewise is based on the technical specs for the Magellan.
   Details are listed in the document scrs232.doc in the installation directory.

   In brief, what the daemon does is to listen in on the serial port
   for in-coming data. It puts the last scanned input in
   the file "/pos/is4c/rs232/scanner".
   Similiarly it puts the last weight input in the
   file "/pos/is4c/rs232/scale".
   The pages chkscale.php and chkscanner check these
   files and assign their contents to the appropriate global variables.

   To set up the daemon, compile ssd.c and arrange for it
   to run at boot time.
*/

#include <sys/types.h>
#include <sys/stat.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <errno.h>
#include <unistd.h>
#include <syslog.h>
#include <string.h>
#include <termios.h> /* POSIX terminal control definitions */
#include <ctype.h>
#include <signal.h>

int scannerFD = 0;
int num, n;
char chout[16];
char serialBuffer[16];
char preBuffer[16];
char scannerInput[12];
char scaleInput[9];
char scaleBuffer[9] = "000000000";
struct termios options;
char serialInput;

char config_file	[1024]	= 	"ssd.conf";
char serial_port	[1024]	= 	"/dev/ttyS0";
char scanner_file	[1024]	=	"scanner";
char scale_file		[1024]	= 	"scale";
char log_file		[1024]	= 	"ssd.log";
char pid_file		[1024]	= 	"ssd.pid";

FILE *fp_scanner;
FILE *fp_scale;
FILE *fp_log;
FILE *fp_pid;

int in_buffer = 0;
int i;
int q = 0;



void read_config_file(char *loc)
{
	FILE *fp_conf;
	char line[1024];
	char *option;
	char *value;

	if (loc != NULL) strcpy(config_file, loc);

	fp_conf = fopen(config_file, "r");
	if (fp_conf == NULL) return;

	while (fgets(line, 1024, fp_conf) != NULL) {
		option = strtok(line, " \t");
		value = strtok(NULL, "\n");

		if (strcmp(option, "ConfFile") == 0) {
			strcpy(config_file, value);
		}
		else if (strcmp(option, "SerialPort") == 0) {
			strcpy(serial_port, value);
		}
		else if (strcmp(option, "ScannerFile") == 0) {
			strcpy(scanner_file, value);
		}
		else if (strcmp(option, "ScaleFile") == 0) {
			strcpy(scale_file, value);
		}
		else if (strcmp(option, "LogFile") == 0) {
			strcpy(log_file, value);
		}
		else if (strcmp(option, "PIDFile") == 0) {
			strcpy(pid_file, value);
		}
	}
	fclose(fp_conf);
}


void log_message(char *message)
{
	fp_log = fopen(log_file, "a");
	fprintf(fp_log, message);
	fclose(fp_log);
}


void set_handler(int signal, void *handler)
{
	struct sigaction new_sa;

	new_sa.sa_handler = handler;
	new_sa.sa_flags = SA_RESTART;
	if (sigaction(signal, &new_sa, 0) != 0) {
		log_message("Couldn't set up handler.\n");
		exit(EXIT_FAILURE);
	}
}


int connect_scanner(void)
{
	if (scannerFD) return scannerFD;

	scannerFD = open(serial_port, O_RDWR | O_NOCTTY | O_NDELAY);
	if (scannerFD == -1) {
		fp_log = fopen(log_file, "a");
		fprintf(fp_log, "connect_scanner(): Unable to open %s - %s\n", serial_port, strerror(errno));
		fclose(fp_log);
	}
	return scannerFD;
}


void start_scanner(void)
{
	/* Enable scale, request status fields, give a confirm beep. */
	write(scannerFD, "S00\r", 5);		/* Hard reset */
	write(scannerFD, "S01\r", 5);		/* Enable */
	write(scannerFD, "S03\r", 5);		/* Scanner status */
	write(scannerFD, "S04\r", 5);		/* Scanner switch read */
	write(scannerFD, "S13\r", 5);		/* Scale status */
	write(scannerFD, "S23\r", 5);		/* Display status */
	write(scannerFD, "S336\r", 6);		/* Send status to host */
	write(scannerFD, "S334\r", 6);		/* Good beep tone */

	log_message("Scanner started.\n");
}


void stop_scanner(void)
{
	write(scannerFD, "S335\r", 6);	     /* Power down */
	log_message("Scanner stopped.\n");
}


void handle_HUP(int signo /*, siginfo_t *info, void *context */)
{
	log_message("Received SIGHUP - rereading config, restarting scanner.\n");
	stop_scanner();
	read_config_file(NULL);
	start_scanner();
}


void handle_TSTP(int signo /*, siginfo_t *info, void *context */)
{
	log_message("Received SIGTSTP - stopping scanner.\n");
	stop_scanner();
}


void handle_CONT(int signo /*, siginfo_t *info, void *context */)
{
	log_message("Received SIGCONT - starting scanner.\n");
	start_scanner();
}


void handle_XFSZ(int signo /*, siginfo_t *info, void *context */)
{
	fp_log = fopen(log_file, "w");
	fprintf(fp_log, "Received XFSZ - restarting log file.\n");
	fclose(fp_log);
}


int main(int argc, char *argv[])
{
	/* Our process ID and Session ID */
	pid_t pid, sid;

	/* Specify command-line specified config file if possible; otherwise NULL specifies compiled default */
	read_config_file(argc > 1? argv[1] : NULL);

	/* Fork off the parent process */
	pid = fork();
	if (pid < 0) {
		fprintf(stderr, "Couldn't fork child process.\n");
		exit(EXIT_FAILURE);
	}
	/* If we got a good PID, then
	   we can exit the parent process. */
	else if (pid > 0) {
		exit(EXIT_SUCCESS);
	}

	/* Change the file mode mask */
	umask(0);

	/* Open any logs here */

	/* Log our pid */
	fp_pid = fopen(pid_file, "w");
	fprintf(fp_pid, "%d", getpid());
	fclose(fp_pid);

	/* Create a new Session ID for the child process */
	sid = setsid();
	if (sid < 0) {
		log_message("Couldn't create new Session ID.\n");
		exit(EXIT_FAILURE);
	}

	/* Change the current working directory */
/* 	if ((chdir("/")) < 0) {
 		log_message("Couldn't change directory.\n");
 		exit(EXIT_FAILURE);
 	}
*/
	/* Set up signal handlers */
	set_handler(SIGHUP, handle_HUP);
	set_handler(SIGTSTP, handle_TSTP);
	set_handler(SIGCONT, handle_CONT);
	set_handler(SIGXFSZ, handle_XFSZ);

	/* Close out the standard file descriptors */
	close(STDIN_FILENO);
	close(STDOUT_FILENO);
	close(STDERR_FILENO);

	connect_scanner();
	start_scanner();

	/* Configure port reading */
	/*fcntl(scannerFD, F_SETFL, FNDELAY);  */

	/* Get the current options for the port */
	tcgetattr(scannerFD, &options);

	/* Set the baud rates to 9600 */
	cfsetispeed(&options, B9600);

	/* Enable the receiver and set local mode */
	options.c_cflag |= (CLOCAL | CREAD);

	/* 7 bits, odd parity */
	options.c_cflag |= PARENB;	/* enable parity */
	options.c_cflag |= PARODD;  /* odd parity */
	options.c_cflag &= ~CSTOPB;	/* disable double stop */
	options.c_cflag &= ~CSIZE;
	options.c_cflag |= CS7;
	options.c_cflag |= CRTSCTS;

	/* Enable data to be processed as raw input */
	options.c_lflag &= ~(ICANON | ECHO | ISIG);

	/* Set the new options for the port */
/*  tcsetattr(scannerFD, TCSANOW, &options);  */

	n = 0;
	num = 0;

	write(scannerFD, "S11\r", 5);
	write(scannerFD, "S14\r", 5);

	while (1) {
		in_buffer = read(scannerFD, &chout, 1); /* Read character from ABU */
		if (in_buffer != -1) {	/* if data is present in the serial port buffer */

			if (chout[0] == 'S') {
				num = 0;
			}

			serialBuffer[num] = chout[0];
			num++;

			if (chout[0] == '\n' && num > 2) {
				serialBuffer[num] = '\0';

				/**************** process scanned data ****************/
				if (serialBuffer[1] == '0') {
					for (i=0; i<17; i++) {
						scannerInput[i] = serialBuffer[i+4];
					}
					fp_scanner = fopen(scanner_file, "w");
					fprintf(fp_scanner, "%s\n", scannerInput);
					fclose(fp_scanner);		
					fp_log = fopen(log_file, "a");
					fprintf(fp_log, "%d Scanned: %s", q++, serialBuffer);
					fclose(fp_log);
				}

				/**************** process weight data ******************/
				else if (serialBuffer[1] == '1') {

					if (serialBuffer[2] == '1') {
						write(scannerFD, "S14\r", 5);
					}

					else if (serialBuffer[2] == '4' && serialBuffer[3] == '3') {
						write(scannerFD, "S11\r", 5);
						if (strcmp(scaleBuffer, serialBuffer) != 0) {
							fp_scale = fopen(scale_file, "w");
							fprintf(fp_scale, "%s\n", serialBuffer);
							fclose(fp_scale);		
							fp_log = fopen(log_file, "a");
							fprintf(fp_log, "%d Weighed: %s", q++, serialBuffer);
							fclose(fp_log);
						}
					}

					else if (serialBuffer[2] == '4') {
						write(scannerFD, "S14\r", 5);		
						if (strcmp(scaleBuffer, serialBuffer) != 0) {
							fp_scale = fopen(scale_file, "w");
							fprintf(fp_scale, "%s\n", serialBuffer);
							fclose(fp_scale);
							fp_log = fopen(log_file, "a");
							fprintf(fp_log, "%d Weighed: %s", q++, serialBuffer);
							fclose(fp_log);
						}
					}

					for (i=0; i<10; i++) {
						scaleBuffer[i] = serialBuffer[i];
					}	

				}	/* weight data processing ends */

			}	/* end of line data processing ends */

		}	/* non-empty buffer data processing ends */

		in_buffer = -1;
		usleep(1);
	}

	close(scannerFD);
	exit(EXIT_SUCCESS);
}
