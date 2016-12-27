#include "im.h"

struct dsn
{
	struct vec user;
	struct vec addr;
	struct vec port;
};

static void 
parse_dsn(const char *s, struct dsn *dsn)
{
	int i;
	register const char *p;
	struct vec *v, nil = {0, 0};

	memset(dsn, 0, sizeof (*dsn));

	for (p = s, v = &dsn->user; *p != '\0'; p++) {
		switch(*p) {
			case '@':
				if (v == &dsn->user)
					v = &dsn->addr;
				else
					v->len++; // allow '@' in addr
				break;

			case ':':
				if (v == &dsn->user) {
					v = &dsn->addr;
					dsn->addr = dsn->user;
					dsn->user = nil;
				}

				if (v == &dsn->addr)
					v = &dsn->port;
				else
					v->len++; // allow ':'' in port
				break;

			default:
				if (!v->ptr)
					v->ptr = p;
				v->len++;
		}
	}

}

static void usage(const char *prog)
{
	fprintf(stderr,
		"ichat version %s\n"
		"usage: %s [name@]address:port\n"
		"\tname: The chat username\n"
		"\taddress: The remote server ip address\n"
		"\tport: The port number\n"
		, "1.0", prog
	);

	exit(1);
}

/**
 * 
 * usage: ichat mary@192.168.66.10:8080
 * 
 */
int main(int argc, char *argv[])
{
	int sfd;
	Msg msg;
	pthread_t tid;
	struct dsn dsn;
	char addr[BUFFER_SIZE] = {0},
		 port[BUFFER_SIZE] = {0},
		 user[BUFFER_SIZE] = {0};

	if (argc < 2 && argv[1] == NULL)
		usage(argv[0]);

	parse_dsn(argv[1], &dsn);

	/* copy into buffer */
	strncpy(user, dsn.user.ptr, dsn.user.len);
	strncpy(addr, dsn.addr.ptr, dsn.addr.len);
	strncpy(port, dsn.port.ptr, dsn.port.len);

	sfd = im_connect(addr, atoi(port));

	ui_init();
	ui_main();

	pthread_create(&tid, NULL, (void*)im_main, (void *)(unsigned long)sfd);

	while (1) {
		ui_promote(">> ");
		memset(&msg, 0, sizeof(0));

		if (ui_gets(msg.data) > 0) {
			msg.type = 1; // console
			msg.chat = 'a';
			strcpy(msg.to, "all");
			strcpy(msg.from, user);

			write(sfd, &msg, sizeof(msg));
			ui_puts(msg.data);
		}

		ui_update();
	}

	im_close(sfd);
	ui_end();

	return 0;
}