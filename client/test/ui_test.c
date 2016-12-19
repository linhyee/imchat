#include "im.h"

typedef struct
{
	long int type;
	char data[BUFFER_SIZE];
} Msg_st;

int main(void)
{
	ui_init();
	ui_main();

	Msg_st msg;

	while (1) {
		ui_promote(">>");

		memset((void*) &msg, 0, sizeof(Msg));

		if (ui_gets(msg.data) > 0)
			ui_puts(msg.data);

		ui_update();
	}

	ui_end();

	return 0;
}