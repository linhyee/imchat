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

  char* buf = encode_msg(&msg);
  
  printf("%s\n", buf);
  free(buf);

  char *text = "{\"type\":1,\"chat\":117,\"to\":\"andy\",\"from\":\"mary\",\"data\":\"hello, 我的朋友！\"}";
  decode_msg(&in, text);

  printf("type = %d\n", in.type);
  printf("chat = %d\n", in.chat);
  printf("to = %s\n", in.to);
  printf("from = %s\n", in.from);
  printf("data = %s\n", in.data);

  int sz;
  char *pack = pack_msg(&msg, &sz);
  printf("pack = %s\n", pack);

  return 0;
}