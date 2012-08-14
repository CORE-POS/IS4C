/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - -

 *  5Aug12 EL Resolve conflict from merge from CORE.  Hope I didn't wreck it.
 * 18Apr12 EL This file must be writable by Apache/PHP because
 *             the lane Install page, Extra Config section:
 * http://localhost/IS4C/pos/is4c-nf/install/extra_config.php
 *             removes "/var/www/IS4C/pos/is4c-nf/" from the scale and scanner
 *             paths to reduce them to the equivalent of ./, i.e.
 *              scale-drivers/drivers/rs232/
 *             But the driver cannot be started/won't-run configured this way,
 *              so restore from commented versions below and re-compile.
 *             I wonder if this is why the lane app hasn't been able to do
 *              anything with scans and weights yet, i.e. doesn't seem to be
 *              aware of them.
 * 14Apr12 EL Add comments.
 *            scale and scanner must exist, 666; this does not create them.
 *  7Apr12 EL Remove ^M's
 *            Change locations of scale and scanner files.

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
/* is signal.h needed? */

#define SSD_SERIAL_PORT "/dev/ttyS0"
//
#define SCALE_OUTPUT_FILE "/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/rs232/scale"
//Restore this if the install page removes the path.
//define SCALE_OUTPUT_FILE "/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/rs232/scale"
#define SCANNER_OUTPUT_FILE "/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/rs232/scanner"
//
//Restore this if the install page removes the path.
//define SCANNER_OUTPUT_FILE "/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/rs232/scanner"

int main(void) {
    /* Our process ID and Session ID */
    pid_t pid, sid;

    /* Fork off the parent process */
    pid = fork();
    if (pid < 0) {
        exit(EXIT_FAILURE);
    }

    /* If we got a good PID, then we can exit the parent process. */
    if (pid > 0) {
        exit(EXIT_SUCCESS);
    }

    /* Change the file mode mask */
    umask(0);

    /* Create a new SID for the child process */
    sid = setsid();
    if (sid < 0) {
        /* Log the failure */
        exit(EXIT_FAILURE);
    }

    /* Change the current working directory */
    if ((chdir("/")) < 0) {
        /* Log the failure */
        exit(EXIT_FAILURE);
    }

    /* Close out the standard file descriptors */
    close(STDIN_FILENO);
    close(STDOUT_FILENO);
    close(STDERR_FILENO);

    int open_port(void) {
        int fd;                   /* File descriptor for the port */

        fd = open(SSD_SERIAL_PORT, O_RDWR | O_NOCTTY | O_NDELAY);

        if (fd == -1) {
         fprintf(stderr, "open_port: Unable to open /dev/ttyS0 - %s\n",
             strerror(errno));
        }

        return (fd);
    }

    int mainfd = 0;
    int num, n; // File descriptor
    char chout[16];
    char serialBuffer[100];
    char preBuffer[16]; // not used
    char scannerInput[100];
    char scaleInput[9]; // not used
    char scaleBuffer[9] = "000000000";
    struct termios options;
    char serialInput;
    mainfd = open_port();
    fcntl(mainfd, F_SETFL, FNDELAY); // Configure port reading
    /* Get the current options for the port */
    tcgetattr(mainfd, &options);
    cfsetispeed(&options, B9600); // Set the baud rates to 9600
    cfsetospeed(&options, B9600);
    // Enable the receiver and set local mode
    options.c_cflag |= (CLOCAL | CREAD);
    /* 7 bits, odd parity */
    options.c_cflag |= PARENB;
    options.c_cflag |= PARODD;  // odd parity
    options.c_cflag &= ~CSTOPB;
    options.c_cflag &= ~CSIZE;
    options.c_cflag |= CS7;
    options.c_cflag |= CRTSCTS;
    /* Enable data to be processed as raw input */
    options.c_lflag &= ~(ICANON | ECHO | ISIG);
    /* Set the new options for the port */
    tcsetattr(mainfd, TCSANOW, &options);
    FILE *fp_scanner;
    FILE *fp_scale;
    int in_buffer = 0;
    int i;
    n = 0;
    num = 0;

		/* Poll the ss for weight data: */
		/* Send Scale Weight Request Command to ss. */
    write(mainfd, "S11\r", 5);
		/* Send Scale Monitor Command to ss. */
    write(mainfd, "S14\r", 5);

    while (1) {

				/* Read 1, the next, character from ABU. EL: What is ABU? */
				// I don't understand the ampersand-chout syntax.
        in_buffer = read(mainfd, &chout, 1);

        // if data could be read from the serial port ...
        //  '-1' is "error", apparently implies nothing in the port/handle to read.
        if (in_buffer != -1) {

						/* If first char is S:
						   - Could be either scanner or scale
							 - Set index to accumulation buffer to origin
						*/
            if (chout[0] == 'S') {
                num = 0;
            }

						/* Put that char at the beginning of the input buffer.
						   Point to the next position in the input buffer.
						*/
            serialBuffer[num] = chout[0];
            num++;

						/* If the current char is LF, a termination, and
							 the accumulated response, including it, in serialBuffer is already 3+ chars.
							 -> Should this not also test for \r?
						*/
            if (chout[0] == '\n' && num > 2) {

								/* Terminate the input buffer with NUL
										i.e. replace \n with NUL
								*/
                serialBuffer[num] = '\0';

								/* If 2nd char in input buffer is "0" the data is from a barcode. */
                if (serialBuffer[1] == '0') {

									/**************** process scanned data ****************/
									/* What kind of a code is it? */
									if (serialBuffer[3] == 'A' || serialBuffer[3] == 'E' || serialBuffer[3] == 'F'){
										/* familar UPC A/E/F */
										/* Starting from the 5th byte of the raw scan
												copy up to 17 chars into the scanner buffer. E.g.
												Of: S08F789678545021, copy 789678545021 to the scanner buffer.
										*/
										for (i = 0; i < 17; i++) {
											scannerInput[i] = serialBuffer[i+4];
										}
									}
									else if (serialBuffer[3] == 'R') {

										/* GS1 databar */
										scannerInput[0] = 'G';
										scannerInput[1] = 'S';
										scannerInput[2] = '1';
										scannerInput[3] = '~';
										scannerInput[4] = serialBuffer[3];
										scannerInput[5] = serialBuffer[4];

										// Copy the rest of the input to the scanner buffer.
										for (i=5; i <= num; i++)	{
											scannerInput[i+1] = serialBuffer[i];
										}	

									}
									else {
										/* unknown barcode type */
										// Set scanner buffer to nul, empty.
										scannerInput[0] = '\0';
									}

									// Write whatever is in the scanner buffer to the scanner file.
									fp_scanner = fopen(SCANNER_OUTPUT_FILE, "w");
									fprintf(fp_scanner, "%s\n", scannerInput);
									fclose(fp_scanner);

								// End of barcode handling.
                }

								/* If 2nd input char is "1" the data is from a
								    Scale Weight or Scale Monitor Response.
								*/
                if (serialBuffer[1] == '1') {

										/**************** process weight data ******************/
										/* If 3rd char is 1 it is a Weight Request Response */
                    if (serialBuffer[2] == '1') {
												/* Send a Scale Monitor Command to the ss */
                        write(mainfd, "S14\r", 5);
                    }
										/* If 3rd char is 4 it is a Scale Monitor Request Response
											 and if 4th char is 3 the weight is Stable Zero
										*/
                    else if (serialBuffer[2] == '4' && serialBuffer[3] == '3') {
												/* Send a Scale Weight Request Command to the ss */
                        write(mainfd, "S11\r", 5);
												/* If x and y are not the same ... */
                        if (strcmp(scaleBuffer, serialBuffer) != 0) {
														/* ... write y to the scale-data file. */
                            fp_scale = fopen(SCALE_OUTPUT_FILE, "w");
                            fprintf(fp_scale, "%s\n", serialBuffer);
                            fclose(fp_scale);
                        }
                    }
										/* If 3rd char is 4 it is a Scale Monitor Request Response
										    and the type-of-response is anything other than Stable Zero ...
										*/
                    else if (serialBuffer[2] == '4') {
												/* Send a Scale Monitor Command to the ss */
                        write(mainfd, "S14\r", 5);
												/* If x and y are not the same ... */
                        if (strcmp(scaleBuffer, serialBuffer) != 0) {
														/* ... write y to the scale-data file. */
                            fp_scale = fopen(SCALE_OUTPUT_FILE, "w");
                            fprintf(fp_scale, "%s\n", serialBuffer);
                            fclose(fp_scale);
                        }
                    }

										/* Then, for any type of Response
												copy y into x.
										*/
                    for (i = 0; i < 10; i++) {
                        scaleBuffer[i] = serialBuffer[i];
                    }

                }  /* weight data processing ends */

            }     /* termination-of-data processing ends */

        }       /* non-empty port buffer data processing ends */

				// Why do this?
        in_buffer = -1;

				/* sleep for 1 microsecond. One millionth. Not much sleep!
				   In fact no time, per http://www.delorie.com/djgpp/doc/libc/libc_844.html
					 Wonder what it is for.
				*/
        usleep(1);

		/* End of monitoring loop */
    }

    close(mainfd);
    exit(EXIT_SUCCESS);


}
