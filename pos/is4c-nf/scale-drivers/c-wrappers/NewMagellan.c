#include <stdio.h>
#include <sys/types.h>
#include <dirent.h>
#include <string.h>
#include <unistd.h>

int main(int argc, char** argv){
	DIR *dh;//directory handle
	DIR *dh_tester;
	char fn_buffer[1024];
	struct dirent *file;//a 'directory entity' AKA file
	char * input_dir;
	FILE* fp;
	char fc_buffer[512];
	int eof_check;

	// no directory provided
	if (argc < 2)
		return 1;

	input_dir = argv[1];
	dh=opendir(input_dir);

	// bad directory
	if (dh == NULL)
		return 1;

	// default: no input
	strcpy(fc_buffer,"");
	while(file=readdir(dh)){
		// skip ., .., hidden stuff
		if (file->d_name[0] == '.')
			continue;

		// build full file name
		strcpy(fn_buffer,input_dir);
		strcat(fn_buffer,file->d_name);

		// check if entry is a directory
		dh_tester = opendir(fn_buffer);
		if (dh_tester != NULL){
			closedir(dh_tester);
			continue;
		}

		// get file contents
		fp = fopen(fn_buffer,"r");	
		eof_check = fscanf(fp,"%s",fc_buffer);
		fclose(fp);

		// blank contents on empty files
		if (eof_check == EOF)
			strcpy(fc_buffer,"");
		unlink(fn_buffer);
		break;
	}
	closedir(dh);

	// determine output
	if (strlen(fc_buffer) == 0){
		printf("{}\n");
	}
	else if (fc_buffer[0] == 'S'){
		printf("{\"scale\":\"%s\"}\n",fc_buffer);
	}
	else {
		printf("{\"scans\":\"%s\"}\n",fc_buffer);
	}
	
	return 0;
}
