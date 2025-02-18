#include "im.h"
#include "cJSON.h"

typedef struct {
  uint8_t x_id;
  uint16_t x_len;
  uint8_t *x_buf;
} __attribute__((packed)) x_p;

char* pack_msg(Msg *msg, int *buf_size) {
  int hsz = sizeof(x_p) - sizeof (uint8_t*);
  char *buf = encode_msg(msg);
  if (buf == NULL) {
    elog(0, "encode msg error: buf=0");
    return NULL;
  }
  int psz = strlen(buf) + 1;
  int len = hsz + psz; //封包长度

  char *out = (char *)malloc(hsz + psz);
  if (out == NULL) {
    elog(0, "molloc packed msg buf error: %s", strerror(errno));
    free(buf); // release buf
    return NULL;
  }
  char *s = out;

  *((uint8_t*) s) = 0xeb;
  s += sizeof(uint8_t);

  *((uint16_t*) s) = htons(psz);  //载荷长度
  s += sizeof(uint16_t);

  memcpy(s, buf, psz);
  if (buf_size != NULL) {
    *buf_size = len;
  }

  elog(0, "pack msg ok: id=%x, len=%d, buf=%s",
    0xeb, psz, buf);

  free(buf);
  return out;
}

int unpack_msg(int fd, Msg *msg) {
  x_p xp; 
  ssize_t hsz = sizeof(x_p) - sizeof(uint8_t*);
  ssize_t sz = 0, ofs=0;
  while (hsz > 0) {
    sz = recv(fd, (char*)&xp + ofs, hsz, MSG_WAITALL);
    if (sz < 0) {
      elog(0, "unpack msg header error: %s", strerror(errno));
      return -1;
    } else if (sz == 0) {
      return 1;
    } 
    hsz -= sz;
    ofs += sz;
  }
  xp.x_len = ntohs(xp.x_len);
  if (xp.x_len == 0) {
    elog(0, "unpack msg header error: x_len=0");
    return -1;
  }
  ssize_t psz = xp.x_len;
  if (psz > 0) {
    xp.x_buf = (uint8_t*) malloc(psz);
    if (xp.x_buf == NULL) {
      elog(0, "malloc unpack body buf error:%s", strerror(errno));
      return -1;
    }
  }
  ofs = 0;
  while (psz > 0) {
    sz = recv(fd, (char *)xp.x_buf + ofs, psz, MSG_WAITALL);
    if (sz < 0) {
      elog(0, "unpack msg body error: %s", strerror(errno));
      free(xp.x_buf);
      return -1;
    } else if (sz == 0) {
      free(xp.x_buf);
      return 1;
    }
    psz -= sz;
    ofs += sz;
  }

  elog(0, "unpack msg ok: id=%x, len=%d, buf=%.*s",
    xp.x_id, xp.x_len, xp.x_buf);

  int n = decode_msg(msg, (char *)xp.x_buf) ;
  if (xp.x_buf != NULL) {
    free(xp.x_buf);
  }
  return n;
}

char* encode_msg(Msg *msg) {
  cJSON *root = cJSON_CreateObject();
  cJSON_AddNumberToObject(root, "type", msg->type);
  cJSON_AddNumberToObject(root, "chat", msg->chat);
  cJSON_AddItemToObject(root, "to", cJSON_CreateString(msg->to));
  cJSON_AddItemToObject(root, "from", cJSON_CreateString(msg->from));
  cJSON_AddItemToObject(root, "data", cJSON_CreateString(msg->data));

  char *out = cJSON_PrintUnformatted(root);
  cJSON_Delete(root);
  return out;
}

int decode_msg(Msg *msg, const char *buf) {
  cJSON *json, *item;
  const char *ep = NULL;
  
  json = cJSON_Parse(buf, &ep);

  if (!json) {
    elog(0, "decode msg error: before [%s]", *ep);
    return -1;
  }

  item = cJSON_GetObjectItem(json, "type");
  if (item) {
    msg->type = item->valueint;
  }
  item = cJSON_GetObjectItem(json, "chat");
  if (item) {
    msg->chat = item->valueint;
  }
  item = cJSON_GetObjectItem(json, "to");
  if (item && item->valuestring) {
    memcpy(msg->to, item->valuestring, NAME_MAX);
  }
  item = cJSON_GetObjectItem(json, "from");
  if (item && item->valuestring) {
    memcpy(msg->from, item->valuestring, NAME_MAX);
  }
  item = cJSON_GetObjectItem(json, "data");
  if (item && item->valuestring) {
    memcpy(msg->data, item->valuestring, BUFFER_SIZE);
  }
  cJSON_Delete(json);
  return 0;
}
