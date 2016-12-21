#include "im.h"

static int http_parse_url(const char *s, int len, struct url *u)
{
	register const char	*p, *e;
	struct vec *v, nil = { 0, 0 };
	
	(void) memset(u, 0, sizeof(*u));

	/* Now, dispatch URI */
	for (p = s, e = s + len, v = &u->proto; p < e; p++) {
		switch (*p) {
		
		case ':':
			if (v == &u->proto) {
				if (&p[2] < e && p[1] == '/' && p[2] == '/') {
					p += 2;
					v = &u->user;
				} else {
					u->user = u->proto;
					u->proto = nil;
					v = &u->pass;
				}
			} else if (v == &u->user) {
				v = &u->pass;
			} else if (v == &u->host) {
				v = &u->port;
			} else if (v == &u->uri) {
				/* : is allowed in path or query */
				v->len++;
			} else {
				return (-1);
			}
			break;
		
		case '@':
			if (v == &u->proto) {
				u->user = u->proto;
				u->proto = nil;
				v = &u->host;
			} else if (v == &u->pass || v == &u->user) {
				v = &u->host;
			} else if (v == &u->uri) {
				/* @ is allowed in path or query */
				v->len++;
			} else {
				return (-1);
			}
			break;
		
		case '/':
#define	SETURI()	v = &u->uri; v->ptr = p; v->len = 1
			if ((v == &u->proto && u->proto.len == 0) ||
			    v == &u->host || v == &u->port) {
				SETURI();
			} else if (v == &u->user) {
				u->host = u->user;
				u->user = nil;
				SETURI();
			} else if (v == &u->pass) {
				u->host = u->user;
				u->port = u->pass;
				u->user = u->pass = nil;
				SETURI();
			} else if (v == &u->uri) {
				/* / is allowed in path or query */
				v->len++;
			} else {
				return (-1);
			}
			break;
		
		default:
			if (!v->ptr)
				v->ptr = p;
			v->len++;
		}
	}

	if (v == &u->proto && v->len > 0) {
		v = ((char *) v->ptr)[0] == '/' ? &u->uri : &u->host;
		*v = u->proto;
		u->proto = nil;
	} else if (v == &u->user) {
		u->host = u->user;
		u->user = nil;
	} else if (v == &u->pass) {
		u->host = u->user;
		u->port = u->pass;
		u->user = u->pass = nil;
	}

	return (p - s);
}

static int http_tcp_client_create(const char *host, int port)
{
	struct hostent *he;
	struct sockaddr_in sin;	
	int sfd;

	if ((he = gethostbyname(host)) == NULL)
		return -1;

	sin.sin_family = AF_INET;
	sin.sin_port = htons(port);
	sin.sin_addr = *((struct in_addr *)he->h_addr);

	if ((sfd = socket(AF_INET, SOCK_STREAM, 0)) == -1)
		return -1;

	if (connect(sfd, (struct sockaddr*)&sin, (socklen_t)sizeof(sin)) < 0)
		return -1;

	return sfd;
}

static void http_parse_response(const char *s, void *buf)
{
	int nbytes = 0;
	char *p = (char *)strstr(s, "HTTP/1.1");

	if (!p)
		return;
	if (atoi(p + 9) != 200)
		return;

	p = (char *)strstr(s, "\r\n\r\n");
	if (!p)
		return;

	p += 4;
	sscanf(p, "%x", &nbytes);

	p = strstr(p, "\r\n");
	p += 2;
	memcpy(buf, p, nbytes);
}

static int http_tcp_send(int sfd,  char *buf, int size)
{
	int sent = 0, tmp = 0;

	while (sent < size) {
		tmp = send(sfd, buf + sent, size - sent, 0);

		if (tmp == -1) return -1;
		sent += tmp;
	}

	return sent;
}

static int http_tcp_recv(int sfd, char *buf, int size)
{
	int recvn = 0;

	recvn = recv(sfd, buf, size, 0);

	return recvn;
}

void http_get(const char *url, char *buf)
{
	int sfd;
	char host[BUFSIZE] = {0};
	char uri[BUFSIZE] = {0};
	char req[BUFSIZE] = {0};
	char text[BUFSIZE] = {0};
	struct url u;

	http_parse_url(url, strlen(url), &u);

	memcpy(host, u.host.ptr, u.host.len);
	memcpy(uri, u.uri.ptr + 1, u.uri.len);

	sfd = http_tcp_client_create(host, 80);

	sprintf(req, HTTP_GET, uri, host, 80);

	if (http_tcp_send(sfd, req, strlen(req) + 1) < 0)
		exit(1);


	if (http_tcp_recv(sfd, text, BUFSIZE) < 0) {
		exit(1);
	}

	http_parse_response(text, buf);

	close(sfd);
}

void http_post(const char *url, const char *post)
{
	
}
