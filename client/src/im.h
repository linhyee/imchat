#ifndef __IM_H__
#define __IM_H__

#ifdef __cplusplus
extern "C"
{
#endif

#define IM_API extern

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

/* session*/

/*
 *
 * The object will be transform to json string
 * eg: {
 * 	    'type': 'console', // console, web, app
 * 	    'chat': 'u',       // u, a
 *      'to': 'andy',
 *      'from' : 'mary',
 *      'data' : 'hello world!'
 *     }
 */
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
};

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

IM_API void im_login(User *usr);
IM_API int im_connect(char *ip, int port);
IM_API void* im_main(void *arg);
IM_API void im_close(int cfd);

/* protocol */
IM_API void file_get_contents(char *url, char *content);
IM_API void im_create_protocol(char *buf, Msg *msg);
IM_API void im_parse_protocol(Msg *msg, char *buf);

/* UI interface */
IM_API WINDOW *wins[3];
IM_API PANEL *panels[3];

IM_API void ui_init();
IM_API void ui_end();
IM_API void ui_main();
IM_API void ui_update();
IM_API void ui_gets(char *buf);
IM_API void ui_puts(char *buf);
IM_API void ui_promote(char *buf);

#ifdef __cplusplus
}
#endif

#endif