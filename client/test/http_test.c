#include "im.h"

int main(void)
{
	char buf[BUFSIZE] = {0};
	http_get("http://192.168.66.10/api.php?a=user&name=mary", buf);
	// http_get("http://www.baidu.com");

	printf("%s\n", buf);

	return 0;
}
