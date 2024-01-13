#include "im.h"

void elog(int fatal, const char *fmt, ...) {
  va_list ap;
  time_t now = time(NULL);

  struct tm *dm = localtime(&now);

// log msg
#if 1
  if (!fatal) {
    return;
  }
#endif

  fprintf(stderr, "[%02d:%02d:%02d]: ", dm->tm_hour,
    dm->tm_min, dm->tm_sec);
  va_start(ap, fmt);
  vfprintf(stderr, fmt, ap);
  va_end(ap);

  fputc('\n', stderr);
  if (fatal) {
    exit(EXIT_FAILURE);
  }
}

void print_msg(Msg *msg) {
  time_t now = time(NULL);
  struct tm *dm = localtime(&now);

  // [22:22:22] someone : this is a message
  char msg_buf[11 + NAME_MAX + 3 + BUFFER_SIZE];
  memset(msg_buf, 0, sizeof(msg_buf));

  sprintf(msg_buf, "[%02d:%02d:%02d] %s : %s", dm->tm_hour, dm->tm_min, dm->tm_sec,
    msg->from, msg->data);

  win_puts(msg_buf);
}