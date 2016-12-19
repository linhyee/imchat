#include "im.h"

int main(void)
{
	int cfd;

	cfd = im_connect("192.168.66.10", 8080);	

	write(cfd, "hello, mmm", 20);
	
	im_main((void *) (unsigned long)cfd);

	im_close(cfd);

	return 0;	
}