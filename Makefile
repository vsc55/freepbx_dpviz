# Makefile for packing up the dpviz module

MODULE := dpviz
VERSION := $(shell sed -n '/<module>/,/<\/module>/s:.*<version>\(.*\)</version>.*:\1:p' module.xml | head -n 1)
TARBALL := $(HOME)/$(MODULE)-$(VERSION).tar.gz

all: sign pack

sign:
	sign $(MODULE)

pack: $(TARBALL)

$(TARBALL):
	tar cvzf $(TARBALL) \
		--exclude=$(MODULE)/.git \
		--exclude=$(TARBALL) \
		--directory=.. $(MODULE)

clean:
	rm -f $(HOME)/$(MODULE)-*.tar.gz
