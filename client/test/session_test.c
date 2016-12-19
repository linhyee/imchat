#include "im.h"

int main(void)
{
	int cfd;

	cfd = im_connect("192.168.66.10", 8080);	

	write(cfd, "hello, mmm", 20);
	
	im_main((int *) cfd);

	im_close(cfd);

	return 0;	
}