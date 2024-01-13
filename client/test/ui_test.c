#include "im.h"

typedef struct {
  long int type;
  char data[BUFFER_SIZE];
} Msg_st;

int main(void)
{
  wins_init();
  Msg_st msg;
  while (1) {
    memset((void*) &msg, 0, sizeof(Msg));
    win_gets(msg.data, BUFFER_SIZE);
    win_puts(msg.data);
  }
  wins_destroy();

  return 0;
}