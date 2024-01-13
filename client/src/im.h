#ifndef __IM_H__
#define __IM_H__

#define IMAPI extern

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdarg.h>
#include <stdint.h>
#include <errno.h>
#include <time.h>
#include <unistd.h>
#include <curses.h>

#include <fcntl.h>
#include <netdb.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <sys/socket.h>
#include <pthread.h>
#include <signal.h>

#define BUFFER_SIZE 2000
#define NAME_MAX 80

typedef struct sa {
  socklen_t len;
  union {
    struct sockaddr sa;
    struct sockaddr_in sin;
  } u;
} sa;

IMAPI int im_connect(char *ip, int port);
IMAPI void* im_main(void *arg);
IMAPI void im_close(int cfd);

/* protocol */
typedef enum {
  Login,  //0
  Message,//1
  Present,//2
  Quit    //3
} msg_type;

typedef struct {
  int type;
  int chat;
  char to[NAME_MAX];
  char from[NAME_MAX];
  char data[BUFFER_SIZE];
} Msg;

char* pack_msg(Msg *msg, int *buf_size);
int unpack_msg(int fd, Msg *msg);
char* encode_msg(Msg *msg);
int decode_msg(Msg *msg, const char *buf);


/* UI interface */
pthread_mutex_t wlock; //protected msg win
IMAPI void wins_init();
IMAPI void wins_destroy(); 
IMAPI int win_gets(char *buf, int len);
IMAPI void win_puts(const char *buf) ;

/* utils*/
typedef struct {
  int len;
  const void *ptr;
} vec;

IMAPI void elog(int fatal, const char *fmt, ...);
IMAPI void print_msg(Msg *msg);

#endif
