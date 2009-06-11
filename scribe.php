<?php
define('SCRIBE_PATH', dirname(__FILE__) . '/');
define('SCRIBE_VERSION', '0.0.3');

// Include all
define('DS', DIRECTORY_SEPARATOR);
include SCRIBE_PATH . 'include/utilities.php';
include SCRIBE_PATH . 'include/class.scblock.php';
include SCRIBE_PATH . 'include/class.scribe.php';
include SCRIBE_PATH . 'include/class.scproject.php';
include SCRIBE_PATH . 'include/class.screader.php';
include SCRIBE_PATH . 'include/class.sccontroller.php';
include SCRIBE_PATH . 'include/class.scoutput.php';
include SCRIBE_PATH . 'include/class.scstatus.php';
include SCRIBE_PATH . 'include/reader.default.php';
include SCRIBE_PATH . 'include/output.html.php';
include SCRIBE_PATH . 'vendors/markdown/markdown.php';
include SCRIBE_PATH . 'vendors/spyc/spyc.php';

// global $Sc;
// $Sc = new Scribe;
// $Sc->Controller->go();

$c = new ScController;
$c->go();