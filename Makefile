#
# Makefile for packing up the dpviz module
#
TARBALL = ~/dpviz.tar.gz

all: sign pack

sign:
    sign dpviz

pack: $(TARBALL)

$(TARBALL):
    (cd .. ; tar cvzf $(TARBALL) --exclude=dpviz/.git --exclude=$(TARBALL) dpviz)

clean:
    rm -f $(TARBALL)
