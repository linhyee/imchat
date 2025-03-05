#include "im.h"

int im_connect(char *ip, int port) {
  int sock, af;
  sa sa;
  af = PF_INET;
  if ((sock = socket(af, SOCK_STREAM, 0)) == -1) {
    fprintf(stderr, "socket create error: %s\n", strerror(errno));
    exit(-1);
  }
  sa.u.sin.sin_family = af;
  sa.u.sin.sin_port = htons(port);
  sa.u.sin.sin_addr.s_addr = inet_addr(ip);
  sa.len = sizeof(sa.u.sin);

  if (connect(sock, &(sa.u.sa), sa.len) < 0) {
    fprintf(stderr, "connect server error: %s\n", strerror(errno));
    exit(-1);
  }
  elog(0, "connected to server[%s:%d]", ip, port);
  return sock;
}

void* im_main(void *arg) {
  Msg msg;
  int sock = (unsigned long) arg;
  int ret;
  while (1) {
    memset(&msg, 0, sizeof(msg));
    ret = unpack_msg(sock, &msg); //blocking in sock
    if (ret < 0 || ret == 1) {
      break;
    } else {
      print_msg(&msg);
    }
  }
  pthread_exit(NULL);
}

void im_close(int fd) { close(fd); }