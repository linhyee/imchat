#include "im.h"

static void print_in_middle(WINDOW *win, int y, int x, int w, char *s)
{
	int l, _y, _x;
	float t;

	if (win == NULL) win = stdscr;

	getyx(win, _y, _x);

	if (x != 0) _x = x;
	if (y != 0) _y = y;
	if (w == 0)  w = 80;

	l  = strlen(s);
	t  = (w - l) / 2;
	_x = x + (int) t;

	mvwprintw(win, _y, _x, "%s", s);
}

static void init_wins()
{
	int x, y, h, w, row = 3; //rows of promote

	/* initial frame */
	wins[0] = newwin(LINES - row, 0, 0, 0);
	getbegyx(wins[0], y, x);
	getmaxyx(wins[0], h, w);

	box(wins[0], 0, 0);
	mvwaddch(wins[0], 2, 0, ACS_LTEE);
	mvwhline(wins[0], 2, 1, ACS_HLINE, w - 2);
	mvwaddch(wins[0], 2, w - 1, ACS_RTEE);

	print_in_middle(wins[0], 1, 0, w, TITLE);
	refresh();

	/* initial memsage area */
	getmaxyx(wins[0], y, x);
	wins[1] = newwin(y - 4, x - 2, 3, 1);
	refresh();

	/* initial promote*/
	wins[2] = newwin(row, 0, y, 0);
	refresh();
}

/**
 * active current window
 */
static void ui_active_promote()
{
	touchwin(wins[2]);
	wrefresh(wins[2]);
}

void ui_init()
{
	initscr();
	cbreak();
	refresh();
}

void ui_end()
{
	endwin();
}

void ui_main()
{
	int i;

	init_wins();

	for (i = 0; i < 3; i++) {
		keypad(wins[i], TRUE);
		panels[i] = new_panel(wins[i]);
	}

	keypad(stdscr, TRUE);
	scrollok(wins[1], TRUE);

	set_panel_userptr(panels[0], panels[1]);
	set_panel_userptr(panels[1], panels[2]);
	set_panel_userptr(panels[2], panels[0]);

	ui_update();
}

void ui_update()
{
	update_panels();
	doupdate();
}

void ui_gets(char *buf)
{
	ui_active_promote();
	wgetnstr(wins[2], buf, BUFFER_SIZE);
}

void ui_promote(char *buf)
{
	ui_active_promote();

	wclear(wins[2]);
	mvwprintw(wins[2], 1, 2, "%s", buf);
	wrefresh(wins[2]);
}

void ui_puts(char *buf)
{
	wprintw(wins[1], "\n%s\n", buf);
	wrefresh(wins[1]);
}