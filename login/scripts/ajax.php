<?php
	require('../scripts/include.php');

	header('Content-type: text/xml;charset=UTF-8');

	$results = array();

	if(!isset($_GET['action'])) $_GET['action'] = null;
	if(!isset($_GET['query'])) $_GET['query'] = '';

	switch($_GET['action'])
	{
		case 'userlist':
			$query_length = strlen($_GET['query']);
			if($query_length < LIST_MIN_CHARS) break;

			$dh = opendir(DB_PLAYERS);
			while(($fname = readdir($dh)) !== false)
			{
				if($fname == '.' || $fname == '..') continue;
				$fname = urldecode($fname);

				if(strlen($fname) >= $query_length && substr($fname, 0, $query_length) == $_GET['query'])
					$results[] = $fname;
			}
			closedir($dh);

			natcasesort($results);

			break;
	}
?>
<xmlresponse>
<?php
	foreach($results as $result)
	{
?>
	<result><?=htmlspecialchars($result)?></result>
<?php
	}
?>
</xmlresponse>