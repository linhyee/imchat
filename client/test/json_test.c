#include <stdio.h>
#include <stdlib.h>
#include "cJSON.h"

void createObject(void)
{
  cJSON *root,*fmt;
  char *out;

  root=cJSON_CreateObject();	
  // cJSON_AddItemToObject(root, "name", cJSON_CreateString("Jack (\"Bee\") Nimble"));
  cJSON_AddItemToObject(root, "name", cJSON_CreateString("如果我是好人 (\"Bee\") 坏人呢!"));
  cJSON_AddItemToObject(root, "format", fmt=cJSON_CreateObject());
  cJSON_AddStringToObject(fmt,"type",		"rect");
  cJSON_AddNumberToObject(fmt,"width",		1920);
  cJSON_AddNumberToObject(fmt,"height",		1080);
  cJSON_AddFalseToObject (fmt,"interlace");
  cJSON_AddNumberToObject(fmt,"frame rate",	24);
  
  out=cJSON_Print(root);
  cJSON_Delete(root);
  printf("%s\n",out);
  free(out);
}

int main(void)
{
  return 0;
}