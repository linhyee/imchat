#include "im.h"

typedef struct
{
	long int type;
	char data[BUFFER_SIZE];
} Msg;

int main(void)
{
	ui_init();
	ui_main();

	Msg msg;

	while (1) {
		ui_promote(">>");
		memset((void*) &msg, 0, sizeof(Msg));
		ui_gets(msg.data);
		ui_puts(msg.data);

		ui_update();
	}

	ui_end();

	return 0;
}