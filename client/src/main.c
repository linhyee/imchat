#include "im.h"

typedef struct {
  vec user;
  vec addr;
  vec port;
} dsn;

static void parse_dsn(const char *s, dsn *dsn) {
  int i;
  register const char *p;
  vec *v, nil = {0, 0};

  memset(dsn, 0, sizeof (*dsn));

  for (p = s, v = &dsn->user; *p != '\0'; p++) {
    switch(*p) {
    case '@':
      if (v == &dsn->user) {
        v = &dsn->addr;
      } else {
        v->len++; // allow '@' in addr
      }
      break;
    case ':':
      if (v == &dsn->user) {
        v = &dsn->addr;
        dsn->addr = dsn->user;
        dsn->user = nil;
      }
      if (v == &dsn->addr) {
        v = &dsn->port;
      } else {
        v->len++; // allow ':'' in port
      }
      break;
    default:
      if (!v->ptr) {
        v->ptr = p;
      }
      v->len++;
    }
  }
}

static void usage(const char *prog) {
  fprintf(stderr,
    "ichat version %s\n"
    "usage: %s name@address:port\n"
    "\tname: the chat username\n"
    "\taddress: the remote server ip address\n"
    "\tport: the port number\n"
    , "1.0", prog
  );
  exit(1);
}

int sfd = -1;
pthread_t tid;

// ctrl+c
void sigal_handler (int signo) {
  if (sfd) {
    im_close(sfd);
    if (tid) { // on linux, tid is integer
      pthread_join(tid, NULL);
    }
  }
  pthread_mutex_destroy(&wlock);
  wins_destroy();
  exit(0);
}

void do_handshake(int fd, char* user) {
  if (strlen(user) < 1) {
    elog(1, "login user name must more than 2 characters.");
  }
  Msg msg = {
    .type = Login,
    .data = "syn",
  };
  memcpy(msg.from, user, NAME_MAX);
  int buf_sz;
  char* buf = pack_msg(&msg, &buf_sz);
  if (buf == NULL) {
    elog(1, "handshake: pack msg fail");
  }
  if (send(fd, buf, buf_sz, 0) < 0) {
    elog(1, "handshake: send buf error: %s", strerror(errno));
  }
  //waiting server response
  Msg res;
  int ret;
  if ((ret = unpack_msg(fd, &res)) < 0){
    elog(1, "handshake: unpack msg fail");
  } else if (ret == 1) {
    elog(1, "handshake: remote server closed");
  }
  //no ok
  if (strcmp(res.data, "ok") != 0) {
    elog(1, "handshake: login deny, remote server auth fail");
  }
}


// main函数
int main(int argc, char *argv[]) {
  if (argc < 2 && argv[1] == NULL) {
    usage(argv[0]);
  }

  //Sin Handle
  signal(SIGINT, sigal_handler);

  //解析连接参数
  dsn dsn;
  parse_dsn(argv[1], &dsn);

  char addr[BUFFER_SIZE]={0}, port[BUFFER_SIZE]={0}, user[BUFFER_SIZE]={0};
  strncpy(user, dsn.user.ptr, dsn.user.len);
  //check user is valid
  if (strlen(user) < 2) {
    fprintf(stderr, "username must more than 2 characters.\n");
    exit(-1);
  }
  strncpy(addr, dsn.addr.ptr, dsn.addr.len);
  if (strlen(addr) == 0) {
    fprintf(stderr, "server address is empty.\n");
    exit(-1);
  }
  strncpy(port, dsn.port.ptr, dsn.port.len);
  if (strlen(port) == 0 || atoi(port) <= 0) {
    fprintf(stderr, "server port is empty or must great than 0.\n");
    exit(-1);
  }

  fprintf(stdout, "connecting to server %s on port %s ...\n", addr, port);
  sfd = im_connect(addr, atoi(port));
  fprintf(stdout, "connected ok!\n");

  //与服务器握手
  fprintf(stdout, "user `%s` is logining ...\n", user);
  do_handshake(sfd, user);
  fprintf(stdout, "logined ok!\n");

  //初始化UI界面
  wins_init();

  Msg welcome = {
    .type = Present,
    .from = "system message",
  };
  sprintf(welcome.data, "welcome, %s !", user);
  print_msg(&welcome);

  int err;
  if (err = pthread_mutex_init(&wlock, NULL)) {
    elog(1, "mutex log init error:%s", strerror(err));
  }
  if (err = pthread_create(&tid, NULL, (void*)im_main, (void *)(unsigned long)sfd)) {
    elog(1, "create thread im_main error: %s", strerror(err));
  }

  Msg msg;
  int sz = 0, n = 0, ofz;
  char *buf;
  time_t t = time(NULL);
  while (1) {
    memset(&msg, 0, sizeof(msg));
    if (win_gets(msg.data, BUFFER_SIZE) > 0) { // block in read
      strcpy(msg.from, user);
      //quit
      if (strcmp(msg.data, "/q") == 0) {
        msg.type = Quit;
      } else {
        msg.type = Message;
      }
      buf = pack_msg(&msg, &sz);
      if (buf == NULL) {
        elog(1, "pack msg fail");
      }
      ofz = 0;
      while (sz > 0) {
        n = send(sfd, buf + ofz, sz, 0);
        if (n < 0) {
          elog(1, "send msg error: %s", strerror(errno));
        }
        sz -= n;
        ofz += n;
      }
      if (msg.type == Quit) {
        break;
      } else{
        print_msg(&msg);
      }
    }
  }
  im_close(sfd);
  if (err =  pthread_join(tid, NULL)) {
    elog(1, "im_main thread not joining, error:%s", strerror(err));
  }
  pthread_mutex_destroy(&wlock);
  wins_destroy(); // if don't destroy, will affect terminal
  return 0;
}
