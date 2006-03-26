<?php
	define('ignore_action', true);
	define('ajax', true);
	require('../scripts/include.php');

	$additionals = array('result' => array());
	$results = &$additionals['result'];

	if(!isset($_GET['action'])) $_GET['action'] = null;
	if(!isset($_GET['query'])) $_GET['query'] = '';

	switch($_GET['action'])
	{
		case 'userlist':
			$_GET['query'] = strtolower(urlencode($_GET['query']));
			$query_length = strlen($_GET['query']);
			if($query_length < LIST_MIN_CHARS) break;

			$dh = opendir(DB_PLAYERS);
			while(($fname = readdir($dh)) !== false)
			{
				if($fname == '.' || $fname == '..') continue;
				$fname = $fname;

				if(strlen($fname) >= $query_length && substr($fname, 0, $query_length) == $_GET['query'])
					$results[] = urldecode($fname);
			}
			closedir($dh);

			natcasesort($results);

			break;

		case 'spionage': case 'besiedeln': case 'sammeln':
			list($additionals['classname'], $results[]) = include('../flotten.php');
			break;
	}


	header('Content-type: text/xml;charset=UTF-8');
	echo "<xmlresponse>\n";
	foreach($additionals as $tagname=>$contents)
	{
		if(!is_array($contents)) $contents = array($contents);
		if(count($contents) <= 0) continue;

		foreach($contents as $content)
			echo "\t<".$tagname.">".htmlspecialchars($content)."</".$tagname.">\n";
	}
	echo "</xmlresponse>\n";
?>