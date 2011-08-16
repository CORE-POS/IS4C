/*******************************************************************************
 *
 *     Copyright 2009 Whole Foods Co-op
 *
 *         This file is part of Fannie.
 *
 *             Fannie is free software; you can redistribute it and/or modify
 *                 it under the terms of the GNU General Public License as published by
 *                     the Free Software Foundation; either version 2 of the License, or
 *                         (at your option) any later version.
 *
 *                             Fannie is distributed in the hope that it will be useful,
 *                                 but WITHOUT ANY WARRANTY; without even the implied warranty of
 *                                     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *                                         GNU General Public License for more details.
 *
 *                                             You should have received a copy of the GNU General Public License
 *                                                 in the file license.txt along with IS4C; if not, write to the Free Software
 *                                                     Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *                                                     *********************************************************************************/

#include <sys/types.h>
#include <unistd.h>
#include <stdio.h>
#include <string.h>

const char* SHADOW_FILE	= "/etc/shadow";

int main(int argc, char** argv){
	FILE* fp;
	char buffer[1024];
	char* name;
	char* tmp_name;
	char* tmp_passwd;

	if (argc != 2){
		printf("Usage: shadowread <name>\n");
		return 1;
	}

	name = argv[1];

	if (strcmp(name,"root") == 0){
		printf("Error: invalid user\n");
		return 1;
	}

	fp = fopen(SHADOW_FILE,"r");
	if (fp == NULL){
		printf("Couldn't open %s\n",SHADOW_FILE);
		return 1;
	}

	while( fgets(buffer,1024,fp) != NULL){
		tmp_name = strtok(buffer,":");
		if (tmp_name == NULL) continue;
		if (strcmp(name,tmp_name) == 0){
			tmp_passwd = strtok(NULL,":");
			if (tmp_passwd == NULL){
				printf("No password found\n");
				return 1;
			}
			else if (strlen(tmp_passwd)<5){
				printf("Doesn't appear to be a password\n");
				return 1;	
			}
			else {
				printf("%s\n",tmp_passwd);
				return 0;
			}	
		}	
	}

	fclose(fp);

	return 1;
}
