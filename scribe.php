<?php
define('SCRIBE_PATH', dirname(__FILE__) . '/');

// Include all
define('DS', DIRECTORY_SEPARATOR);
include SCRIBE_PATH . 'include/utilities.php';
include SCRIBE_PATH . 'include/class.scribe.php';
include SCRIBE_PATH . 'include/class.scproject.php';
include SCRIBE_PATH . 'include/class.scparser.php';
include SCRIBE_PATH . 'include/parser.default.php';
include SCRIBE_PATH . 'include/output.html.php';
include SCRIBE_PATH . 'vendors/markdown/markdown.php';
include SCRIBE_PATH . 'vendors/spyc/spyc.php';

global $Sc;
$Sc = new Scribe;
$Sc->go();

