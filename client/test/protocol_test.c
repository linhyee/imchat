#include "im.h"

int main(void)
{
	Msg in, msg = {
		.type = 1,
		.chat = 'u',
		.to = "andy",
		.from = "mary",
		.data = "hello, 我的朋友！"
	};

	char buf[8192] = {0};
	im_create_protocol(&msg, buf, sizeof(buf) );
	
	printf("%s\n", buf);

	char *text = "{\"type\":1,\"chat\":117,\"to\":\"andy\",\"from\":\"mary\",\"data\":\"hello, 我的朋友！\"}";
	im_parse_protocol(&in, text);

	printf("type = %d\n", in.type);
	printf("chat = %d\n", in.chat);
	printf("to = %s\n", in.to);
	printf("from = %s\n", in.from);
	printf("data = %s\n", in.data);

	return 0;
}