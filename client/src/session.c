#include "im.h"
#include <curl/curl.h>

/**
 *  Log function
 */
void elog(int fatal, const char *fmt, ...)
{
	va_list ap;
	static time_t now;

#if 1
	if (!fatal)
		return;
#endif

	// fprintf(stderr, "%lu ", (unsigned long) now);
	va_start(ap, fmt);
	vfprintf(stderr, fmt, ap);
	va_end(ap);

	fputc('\n', stderr);

	if (fatal)
		exit(EXIT_FAILURE);
}

void im_login(User *usr)
{
	//check usr whether valid		
}

int im_connect(char *ip, int port)
{
	int sock, af;
	sa sa;

#ifdef WITH_IPV6
	af = PF_INET6;
#else
	af = PF_INET;
#endif

	if ((sock = socket(af, SOCK_STREAM, 0)) == -1)
		elog(1, "socket: %s", strerror(errno));

#ifdef WITH_IPV6
	sa.u.sin6.sin6_family = af;
	sa.u.sin6.sin6_port = htons(port);
	sa.u.sin6.sin6_addr = inet_addr(ip);
	sa.len = sizeof(sa.u.sin6);
#else
	sa.u.sin.sin_family = af;
	sa.u.sin.sin_port = htons(port);
	sa.u.sin.sin_addr.s_addr = inet_addr(ip);
	sa.len = sizeof(sa.u.sin);
#endif

	if (connect(sock, &(sa.u.sa), sa.len) < 0)
		elog(1, "connect: %s", strerror(errno));

	return sock;
}

void* im_main(void *arg)
{
	Msg msg;
	char buf[8029]; // the max tcp package length
	int nread, sock = (unsigned long) arg;

	while (1) {
		nread = read(sock, buf, sizeof(buf));
		im_parse_protocol(&msg, buf);

		if (nread == 0) {
			switch (msg.type) {
				case 'u':
					break;
				case 'a':
					break;
				default:
					;
			}

		} else if (nread > 0) {
			printf("%s\n", buf);
		} else {

		}
	}

	pthread_exit(NULL);
}

void im_close(int cfd)
{
	close(cfd);
}

static size_t wcb(void *ptr, size_t size, size_t nmemb, void *data)
{
	size_t realsize = size * nmemb;
	memcpy(data, ptr, size * nmemb);

	return realsize;
}

int get_url(char *url, void *buf)
{
	int ret = -1;
	CURL *curl;
	CURLcode res;

	curl = curl_easy_init();

	if (curl) {
		curl_easy_setopt(curl, CURLOPT_URL, url);
		curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, wcb);
		curl_easy_setopt(curl, CURLOPT_WRITEDATA, (void*)buf);

		res = curl_easy_perform(curl);

		curl_easy_cleanup(curl);
		ret = 0;
	}

	return ret;	
}