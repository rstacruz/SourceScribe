<?php
// Comment block
// And stuff
// Yes
function aeScandir($dirPath, $opts=array(), $internal='/')
{
	$f = array();
	$dirPath = realpath($dirPath);
	$dir = opendir($dirPath);

	// If we're looking for DIR's, make sure to include the root
	if (($internal == '/') && (isset($opts['directory'])) &&
	    (@$opts['directory'] === TRUE))
		{ $f []= '/'; } 
	
	while ($fname = readdir($dir))
	  if (($fname != '.') && ($fname != '..'))
	{
		$result = $internal.$fname;
		$file = $dirPath . DIRECTORY_SEPARATOR . $fname;
        
		// Recurse if needed
		if ((is_dir($file)) && (@$opts['recursive']))
			{ $f = array_merge($f, aeScandir($file, $opts, $result.'/')); }
            
		// 'mask' => Include masks. (Goes back if it doesn't match)
		if (isset($opts['mask']))
		  foreach ((array) $opts['mask'] as $mask)
			if (!preg_match($mask, $result)) { continue 2; }
			
		// 'exclude' => Exclude masks (Goes back if it matches)
		if (isset($opts['exclude']))
		  foreach ((array) $opts['exclude'] as $mask)
			if (preg_match($mask, $result)) { continue 2; }
		
		// Append dir to results (if its asked for)	
		if ((is_dir($file)) && (isset($opts['directory'])))
			{ $f []= $result; }
		
		// Append the file to results
		else if ((is_file($file)) && (!isset($opts['directory'])))
		{
			// 'cnewer' => Newer than ctime
			if (isset($opts['cnewer']))
			  if (filectime($file) < $opts['cnewer']) { continue; }
				
			// 'mnewer' => Newer than mtime and ctime
			if (isset($opts['mnewer']))
			  if (filemtime($file) < $opts['mnewer']) { continue; }
			
			$f []= $result;
		}
	}
	
    if ((isset($opts['fullpath'])) && ($internal == '/'))
    {
        for ($i = 0; $i < count($f); ++$i)
        {
            $f[$i] = realpath($dirPath . DIRECTORY_SEPARATOR . $f[$i]);
        }
    }
	return $f;
}

function yaml($file)
{
    $parser = new Spyc;
    return $parser->load($file);
}

if (!function_exists('file_put_contents')) {
  function file_put_contents($file, $data)
  {
	$file = @fopen($fname, 'w');
	if (!$file) { return FALSE; }
	if (!fwrite($file, $contents)) { return FALSE; }
	fclose ($file);
	return TRUE;
  }
}

if (version_compare(phpversion(), '5.0') >= 0)
    { include dirname(__FILE__) . '/utilities.php5.php'; } 
else
    { include dirname(__FILE__) . '/utilities.php4.php'; } 

    