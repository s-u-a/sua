<?php
	require('engine/include.php');

	header('Content-type: text/plain;charset=UTF-8');

	ob_end_clean();

	$changelog = '';
	if(is_file('db_things/changelog') && is_readable('db_things/changelog'))
		$changelog = file_get_contents('db_things/changelog');

	$changelog = preg_split("/\r\n|\r|\n/", $changelog);

	foreach($changelog as $log)
	{
		$log = explode("\t", $log, 2);
		if(count($log) < 2)
			echo $log[0]."\n";
		else
			echo date('Y-m-d, H:i:s', $log[0]).': '.$log[1]."\n";
	}
?>