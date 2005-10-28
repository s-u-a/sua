<?php
	require('engine/include.php');

	header('Content-type: text/plain;charset=UTF-8');

	ob_end_clean();

	$todo = '';
	if(is_file('db_things/todo') && is_readable('db_things/todo'))
		$todo = file_get_contents('db_things/todo');

	echo $todo;
?>