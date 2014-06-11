/**
 * @file	proj03.c
 *
 * contains the project 3 program to count lower-case characters in
 * a given text file, and output them to the console.
 * Uses pthreads to actually do the work, with N pthreads each reading M
 * characters at-a-time. The pthreads use mutexes to lock use of global
 * variables.
 *
 * @date	Jun 10, 2014
 * @author	Chris Thomas (fires@msu.edu)
 */

#include <stdio.h>
#include <stdlib.h>
#include <pthread.h>
#include <ctype.h>
#include <string.h>
#include <time.h>

int counts[26] = { 0 };
int M;
FILE* file;
pthread_mutex_t file_mutex;
pthread_mutex_t count_mutex;

void* readFile( void* threadNum );
void usageMessage();

/**
 * Main execution point
 *
 * Does all the main program's work:
 * reading the cli arguments, if they exist,
 * opening the input file, handling errors, and starting the threads
 * that do the work for it.
 *
 * @param argc 	The number of command-line arguments (including itself)
 * @param argv	The actual command-line arguments, terminated by argv[argc] = NULL.
 * @return 		0 for success, otherwise for various types of failure.
 */
int main(int argc, char** argv)
{
	/*
	 * Declare vars
	 */

	// Number of threads
	int N;
	// input filename
	char filename[50];
	// the clock times
	clock_t begin, end;
	// total time spent
	double time_spent;
	// loop counter
	int i;

	/*
	 * Check arguments
	 */

	// set the defaults here, in case they supplied no arguments
	strcpy(filename, "samplefile.txt");
	N = 4;
	M = 100;

	if(argc >= 2)
	{
		// if they supplied arguments, check them
		for(i = 1; i < argc; i++)
		{
			if(strcmp(argv[i], "-M") == 0)
			{
				// if they've supplied "-M", check the next argument for a number
				i++;
				if(i < argc)
				{
					M = atoi(argv[i]);
					if(M <= 0)
					{
						printf("Invalid buffer size: %d.\n", M);
						usageMessage();
						puts("Please check your input, and try again.\n");
						exit(1);
					}
				}
				else
				{
					puts("Invalid syntax: '-M' requires exactly one argument.");
					usageMessage();
					puts("Please check your input, and try again.\n");
					exit(1);
				}
			}
			else if(strcmp(argv[i], "-N") == 0)
			{
				i++;
				if(i < argc)
				{
					N = atoi(argv[i]);
					if(N <= 0)
					{
						printf("Invalid number of threads: %d.\n", N);
						usageMessage();
						puts("Please check your input, and try again.\n");
						exit(1);
					}
				}
				else
				{
					puts("Invalid syntax: '-N' requires exactly one argument.");
					usageMessage();
					puts("Please check your input, and try again.\n");
					exit(1);
				}
			}
			else if(argv[i] != NULL)
			{
				strcpy(filename, argv[i]);
			}
		}
	}

	file = fopen(filename, "r");
	if(file == NULL)
	{
		printf("Unable to open file at %s.\n", filename);
		puts("Please check your input, and try again.\n");
		exit(1);
	}

	/*
	 * Actually do the character counting
	 */

	// Start the clock!
	begin = clock();

	// create my thread variables...
	pthread_t threads[N];
	int threadNums[N];
	// and actually create each one
	for(i = 0; i < N; i++)
	{
		// give it its 'thread-number'
		threadNums[i] = i + 1;

		// and actually create the thread, sending it on its way
		pthread_create( &threads[i], NULL, readFile, &(threadNums[i]) );
	}

	// then wait for each one to terminate before continuing
	for(i = 0; i < N; i++)
	{
		pthread_join( threads[i], NULL );
	}

	// End the clock!
	end = clock();
	// calculate time spent
	time_spent = (double)(end - begin) / CLOCKS_PER_SEC;

	/*
	 * Output results
	 */
	puts("------Results------");
	for(i = 0; i < 26; i++)
	{
		printf("%c: %d\n", i+97, counts[i]);
	}
	printf("Running time: %f seconds\n", time_spent);
	puts("-------------------");
	return 0;
}

/**
 * Read a file in a thread
 *
 * a thread will use this function to read in the file
 * defined in the global variables.
 * @param threadNum	void* pointer to this thread's thread-number.
 * 					Used exclusively for outputting Start/End messages
 * 					to the console.
 */
void* readFile( void* threadNum )
{
	int tnum = *((int*)threadNum);
	// I'm starting on this one
	printf( "thread-%d Start.\n", tnum );

	// create a buffer for reading characters
	char* buffer = (char*)calloc( M, sizeof(char) );
	int i;

	// while we aren't EOF or erroring (these are thread-safe functions)
	while( !feof( file ) && !ferror( file ) )
	{
		// lock the file's mutex or wait until it unlocks
		pthread_mutex_lock( &file_mutex );

		// read in M characters
		fgets( buffer, M, file );

		// unlock my control over the file
		pthread_mutex_unlock( &file_mutex );

		if(buffer != NULL)
		{
			// go through the buffer and count the lowercase characters
			for(i = 0; i < M; i++)
			{
				if(islower(buffer[i]))
				{
					// lock control over the count
					pthread_mutex_lock(&count_mutex);
					// update the count for this letter
					counts[buffer[i]-97]++;
					// unlock my control over the count
					pthread_mutex_unlock(&count_mutex);
				}
			}
		}
	}
	printf( "thread-%d End.\n", tnum );
	free(buffer);
	pthread_exit( 0 );
}

/**
 * Usage Message
 *
 * outputs the programs "Usage: " message to the console.
 */
void usageMessage()
{
	puts("Usage: proj03 [-M <buffer-size>] [-N <num-threads>] [<inputfilename>]");
	puts("    -M <buffer-size> : The number of characters each thread will read");
	puts("                       at a time. (default: 100)");
	puts("    -N <num-threads> : The number threads to use.");
	puts("                       (default: 4)");
	puts("    <inputfilename>  : The file to read and count characters in.");
	puts("                       (default: samplefile.txt)");
	puts("  **arguments are all optional and may be provided in any order.**");
}
