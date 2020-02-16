all:	usb4all

usb4all: usb4all.o
	$(CC) $(LDFLAGS) -L$(STAGING_DIR)/usr/lib usb4all.o -o usb4all

usb4all.o: usb4all.c
	$(CC) $(CFLAGS) -I$(STAGING_DIR)/usr/include -DUNIT_TEST -c usb4all.c


clean:		
	rm -f usb4all *.o
