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
 
 #include <stdio.h>   /* Standard input/output definitions */
 #include <string.h>  /* String function definitions */
 #include <unistd.h>  /* UNIX standard function definitions */
 #include <fcntl.h>   /* File control definitions */
 #include <errno.h>   /* Error number definitions */
 #include <termios.h> /* POSIX terminal control definitions */
 #include <ctype.h>
 #include <stdlib.h>

 /*
  * 'open_port()' - Open serial port 1.
  *
  * Returns the file descriptor on success or -1 on error.
  */

 int open_port(void)
 {
   int fd;                                   /* File descriptor for the port */

   fd = open("/dev/ttyS0", O_RDWR | O_NOCTTY | O_NDELAY);

   if (fd == -1)
   {                                              /* Could not open the port */
     fprintf(stderr, "open_port: Unable to open /dev/ttyS0 - %s\n",
             strerror(errno));
   }

   return (fd);
 }



int main()
{
  int mainfd=0;  
  int num, n;                                    /* File descriptor */
  char chout[16];
  char serialBuffer[16];
  char preBuffer[16];
  char scannerInput[12];
  char scaleInput[9];
  char scaleBuffer[9] = "000000000";
  struct termios options;
  char serialInput;


  mainfd = open_port();

  fcntl(mainfd, F_SETFL, FNDELAY);                 /* Configure port reading */
                                     /* Get the current options for the port */
 tcgetattr(mainfd, &options);
 cfsetispeed(&options, B9600);                 /* Set the baud rates to 9600 */
 cfsetospeed(&options, B9600);
    
                                   /* Enable the receiver and set local mode */
 options.c_cflag |= (CLOCAL | CREAD);
				/* 7 bits, odd parity */
     options.c_cflag |= PARENB;
     options.c_cflag |= PARODD;  /* odd parity */
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

 write(mainfd, "S11\r", 5);
 write(mainfd, "S14\r", 5);

 
 while (1)
 {
   
   in_buffer = read(mainfd, &chout, 1); /* Read character from ABU */
  
 

   if (in_buffer != -1) {    /* if data is present in the serial port buffer */

     if (chout[0] == 'S') {  
       num = 0;
     }
     
     serialBuffer[num] = chout[0];
     
     num++;
                                  ;
    

     if (chout[0] == '\n' && num > 2) {

	serialBuffer[num] = '\0';

	/**************** process scanned data ****************/

	if (serialBuffer[1] == '0') {

	  for (i=0; i<17; i++) {
	    scannerInput[i] = serialBuffer[i+4];
	}
	fp_scanner = fopen("scanner", "w");
	fprintf(fp_scanner, "%s\n", scannerInput);
	fclose(fp_scanner);

	}  
	/**************** process weight data ******************/

	if (serialBuffer[1] == '1') {

	  
	 
 
	  if (serialBuffer[2] == '1') {

	    write(mainfd, "S14\r", 5);
	   
	  
	  } 

	  else if (serialBuffer[2] == '4' && serialBuffer[3] == '3') {

	    write(mainfd, "S11\r", 5);

	    if (strcmp(scaleBuffer, serialBuffer) != 0) {

		fp_scale = fopen("scale", "w");
		fprintf(fp_scale, "%s\n", serialBuffer);
		fclose(fp_scale);
	     
	    }
	   
	  }

	  else if (serialBuffer[2] == '4') {

	    write(mainfd, "S14\r", 5);

	    if (strcmp(scaleBuffer, serialBuffer) != 0) {

		fp_scale = fopen("scale", "w");
		fprintf(fp_scale, "%s\n", serialBuffer);
		fclose(fp_scale);
	    }  
	  }

	  

	  for (i=0; i<10; i++) {
	    scaleBuffer[i] = serialBuffer[i];
	  }

	}  /* weight data processing ends */

     }     /* end of line data processing ends */
 
   }       /* non-empty buffer data processing ends */
   in_buffer = -1;

 
   // usleep(20000);
 }
 
                                                    /* Close the serial port */
  close(mainfd);
 }
