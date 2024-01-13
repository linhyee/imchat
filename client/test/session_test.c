#include "im.h"

int main(void)
{
  int fd = im_connect("127.0.0.1", 8000);	

  Msg msg = {
    .type = 2,
    .chat = 'u',
    .to = "kitty",
    .from = "jonh",
    .data = "data pack test",
  };

  int sz;
  char *buf =  pack_msg(&msg, &sz);
  write(fd, buf, sz);

  Msg msg2;
  unpack_msg(fd, &msg2);

  im_close(fd);
  return 0;	
}