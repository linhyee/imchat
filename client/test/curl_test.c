#include "im.h"

int main(void)
{
	char buf[3] = {0};
	
	get_url("http://192.168.66.10/user.php", buf);
	printf("%s\n", buf);

	return 0;
}