IDIR=-I./
CC=gcc
LIBS=-lm -lncurses -lpthread

OBJ= $(patsubst %.c, %.o, $(wildcard ./*.c))

%.o: %.c
	$(CC) -c -o $@ $<

chat: $(OBJ)
	$(CC) $(LIBS) $^ -o $@

.PHONY: clean

clean:
	rm -rf *.o chat