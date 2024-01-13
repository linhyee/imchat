#include "im.h"

WINDOW *top, *chat, *bot, *input;

void wins_init() {
  //清空之前的输出
  clear();
  initscr();
  cbreak();
  nonl();
  refresh();

  int x, y, h, w;
  getmaxyx(stdscr, h, w);

  //message win
  top = newwin(h-4, w, 0, 0);
  box(top, 0, 0);
  wrefresh(top);

  chat = subwin(top, h-6, w-2, 1, 1);
  wrefresh(chat);

  //initial input win
  getmaxyx(top, y, x);
  bot = newwin(4, w, y, 0);
  box(bot, 0, 0);
  wrefresh(bot);

  getbegyx(bot, y, x);
  //坑!必新起一个win,如果win中缓冲buf含有字符(包括已输出),会影响输入
  input = subwin(bot, 2, w-2, y+1 ,1);
  wrefresh(input);

  //use keypad on all windows
  keypad(top, TRUE);
  keypad(chat, TRUE);
  keypad(bot, TRUE);
  keypad(input, TRUE);
  scrollok(chat, TRUE);
}

void wins_destroy() {
  delwin(top);
  delwin(chat);
  delwin(bot);
  delwin(input);
  endwin();
}

int win_gets(char *buf, int len) {
  wcursyncup(input);
  wrefresh(input);
  wmove(input, 0, 0);
  wrefresh(input);
  wgetnstr(input, buf, len);
  wclear(input);
  wrefresh(input);
  return strlen(buf);
}

void win_puts(const char *buf) {
  pthread_mutex_lock(&wlock);
  wprintw(chat, "%s\n",buf);
  wrefresh(chat);
  pthread_mutex_unlock(&wlock);
  //always focus at input
  wcursyncup(input);
  wrefresh(input);
}