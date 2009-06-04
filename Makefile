.PHONY: all install ss

all: ss

ss:
	rm ss; ( echo '#!/usr/bin/php'; echo "<?php"; find . -name \*.php -exec grep -v ?php {} \; | grep -v "include SCRIBE_PATH" ) > ss; chmod +x ss
