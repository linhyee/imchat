#ifndef __IM_H__
#define __IM_H__

#ifdef __cplusplus
extern "C"
{
#endif

#define IMAPI extern

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdarg.h>
#include <errno.h>
#include <time.h>
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
 *      'type': 'console', // 1 => console, 2 => web, 3 => app
 *      'chat': 'u',       // u => send to user, a => send all
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

/**
 *  mary@192.168.66.10:8049
 */
struct User {
    char name[NAME_MAX];
    char ip[256];
    int port;
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

IMAPI void im_login(User *usr);
IMAPI int im_connect(char *ip, int port);
IMAPI void* im_main(void *arg);
IMAPI void im_close(int cfd);
IMAPI int get_url(char *url, void *buf);

/* protocol */
IMAPI void file_get_contents(char *url, char *content);
IMAPI void im_create_protocol(Msg *msg, char *buf, int len);
IMAPI void im_parse_protocol(Msg *msg, char *buf);

/* UI interface */
WINDOW *wins[3];
PANEL *panels[3];

IMAPI void ui_init();
IMAPI void ui_end();
IMAPI void ui_main();
IMAPI void ui_update();
IMAPI int ui_gets(char *buf);
IMAPI void ui_puts(char *buf);
IMAPI void ui_promote(char *buf);

/* utils*/
void elog(int fatal, const char *fmt, ...);

#ifdef __cplusplus
}
#endif

#endif