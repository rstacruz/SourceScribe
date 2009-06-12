#!/usr/bin/php
<?php
$x = $_SERVER['argv'];
array_shift($x);
system('php -f ' . dirname(__FILE__)."/scribe.php -- " . implode(' ',$x));