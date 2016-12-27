#include "im.h"
#include "cJSON.h"

/**
 * 创建JSON类型的数据报
 */
void im_create_protocol(Msg *msg, char *buf, int len)
{
	cJSON *root;
	char *out;

	root = cJSON_CreateObject();
	cJSON_AddNumberToObject(root, "type", msg->type);
	cJSON_AddNumberToObject(root, "chat", msg->chat);
	cJSON_AddItemToObject(root, "to", cJSON_CreateString(msg->to));
	cJSON_AddItemToObject(root, "from", cJSON_CreateString(msg->from));
	cJSON_AddItemToObject(root, "data", cJSON_CreateString(msg->data));

	out = cJSON_PrintUnformatted(root);
	memcpy(buf, out, len);

	cJSON_Delete(root);
	free(out);
}


/**
 * 解析服务发来的数据报
 */
void im_parse_protocol(Msg *msg, char *buf)
{
	cJSON *json, *item;
	
	json = cJSON_Parse(buf);

	if (!json)
		elog(1, "Error before: [%s]\n", cJSON_GetErrorPtr());

	item = cJSON_GetObjectItem(json, "type");
	msg->type = item->valueint;

	item = cJSON_GetObjectItem(json, "chat");
	msg->chat = item->valueint;

	item = cJSON_GetObjectItem(json, "to");
	memcpy(msg->to, item->valuestring, NAME_MAX);

	item = cJSON_GetObjectItem(json, "from");
	memcpy(msg->from, item->valuestring, NAME_MAX);

	item = cJSON_GetObjectItem(json, "data");
	memcpy(msg->data, item->valuestring, BUFFER_SIZE);

	cJSON_Delete(json);
}
