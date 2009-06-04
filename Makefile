.PHONY: all install ss

all: ss

ss:
	rm ss; ( echo '#!/usr/bin/php'; echo "<?php error_reporting(0);"; find . -name \*.php -exec grep -v ?php {} \; | grep -v "include SCRIBE_PATH" ) > ss; chmod +x ss
