#ifndef __UI_H__
#define __UI_H__

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <unistd.h>
#include <curses.h>
#include <panel.h>

#include <fcntl.h>
#include <netdb.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <pthread.h>

#define BUFFER_SIZE 4096
#define NAME_MAX 80
#define TITLE "iChat 1.0"

/* session */
struct Msg {
	int type;
	long int chat;
	char to[NAME_MAX];
	char from[NAME_MAX];
	char data[BUFFER_SIZE];
};

typedef struct Msg Msg;

struct User {
	char name[NAME_MAX];
	int port;
	char ip[256];
}

typedef struct User User;

struct sa {
	socklen_t len;
	union {
		struct sockaddr sa;
		struct sockaddr_in sin;
	} u;
#ifdef WITH_IPV6
	struct sockaddr_in6 sin6;
#endif
};

typedef struct sa sa;

void im_login(User *usr);
int im_connect(char *ip, int port);
void* im_main(void *arg);
void im_close(int cfd);

/* UI interface */
WINDOW *wins[3];
PANEL *panels[3];

void ui_init();
void ui_end();
void ui_main();
void ui_update();
void ui_gets(char *buf);
void ui_puts(char *buf);
void ui_promote(char *buf);

#endif