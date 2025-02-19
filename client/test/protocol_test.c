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
  
  printf("encode msg ok : %s\n", buf);

  char *text = "{\"type\":1,\"chat\":117,\"to\":\"andy\",\"from\":\"mary\",\"data\":\"hello, 我的朋友！\"}";

  int r;
  if ((r = strcmp(buf, text)) != 0) {
    printf("not expected as text\n");
    exit(-1);
  }
  memset(&in, 0, sizeof(in));
  r = decode_msg(&in, buf);
  if (r < 0) {
    printf("decode msg fail.\n");
    exit(-1);
  }

  printf("decode msg ok : \n");
  printf("\ttype = %d\n", in.type);
  printf("\tchat = %d\n", in.chat);
  printf("\tto = %s\n", in.to);
  printf("\tfrom = %s\n", in.from);
  printf("\tdata = %s\n", in.data);

  int sz;
  char *pack = pack_msg(&msg, &sz);
  printf("pack = %s\n", pack);

  int sfd;
  sfd = im_connect("127.0.0.1", 8000);
  if (sfd < 0) {
    printf("connect server fail.\n");
    exit(-1);
  }

  // handshake
  Msg h = {
    .type = Login,
    .data = "syn",
  };

  memcpy(h.from , "test", NAME_MAX);
  int bs;
  char *b = pack_msg(&h, &bs);

  if (send(sfd, b, bs, 0) < 0) {
    printf("send msg fail.\n");
    exit(-1);
  }

  // veify
  Msg v;
  r = unpack_msg(sfd, &v);
  if (r < 0) {
    printf("unpack msg fail.\n");
    exit(-1);
  }

  if (strcmp(v.data, "ok") != 0) {
    printf("verify fail.\n");
    exit(-1);
  }

  //send 2 msgs

  Msg m1 = {
    .from = "test",
    .type = Message,
    .data = "test data1",
  };

  Msg m2 = {
    .from = "test",
    .type = Message,
    .data = "test data2",
  };

  int sz1 =0;
  char *b1 = pack_msg(&m1, &sz1);
  if (!b1) {
    printf("pack msg m1 fail.\n");
    exit(-1);
  }

  int sz2 = 0;
  char *b2 = pack_msg(&m2, &sz2);
  if (!b2) {
    printf("pack msg m2 fail.\n");
    exit(-1);
  }

  char *pkg = (char *)malloc(sz1 + sz2);
  if (!pkg) {
    printf("malloc pkg fail.\n");
    exit(-1);
  }

  memcpy(pkg, b1, sz1);
  memcpy(pkg + sz1, b2, sz2);

  //send
  r = send(sfd, pkg, sz1 + sz2 / 2, 0);
  if (r < 0) {
    printf("send pkg fail.\n");
    exit(-1);
  }

  close(sfd);
  free(buf);
  free(pkg);
  free(b1);
  free(b2);
  return 0;
}

// command:
// gcc -o ptest protocol_test.c ../src/cJSON.c ../src/session.c ../src/protocol.c ../src/utils.c ../src/ui.c  -I../src -lm -lncurses -lpthread