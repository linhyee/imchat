#include "im.h"


int main(int argc, char *agrv[])
{
	int sfd;
	Msg msg;
	pthread_t tid;

	sfd = im_connect("192.168.66.10", 8080);

	ui_init();
	ui_main();

	pthread_create(&tid, NULL, (void*)im_main, (void *) sfd)

	while (1) {
		ui_promote(">> ");
		memset(&msg, 0, sizeof(0));

		if (ui_gets(msg.data) > 0) {
			msg.type = 1; // console
			msg.chat = 'a';
			msg.to = "all";
			msg.from = "xxx";

			write(sfd, &msg, sizeof(msg));
			ui_pust(msg.data);
		}

		ui_update();
	}

	im_close(sfd);
	ui_end();

	return 0;
}