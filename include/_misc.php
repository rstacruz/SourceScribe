<?php
// Only for ss

function _TokenizeHTML($str) {

	$index = 0;
	$tokens = array();

	$match = '(?s:<!(?:--.*?--\s*)+>)|'.	# comment
			 '(?s:<\?.*?\?>)|'.				# processing instruction
			 								# regular tags
			 '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)'; 

	$parts = preg_split("{($match)}", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

	foreach ($parts as $part) {
		if (++$index % 2 && $part != '') 
			$tokens[] = array('text', $part);
		else
			$tokens[] = array('tag', $part);
	}

	return $tokens;
}