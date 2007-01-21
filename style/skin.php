<?php
	require('../../engine/include.php');

	header('Content-type: text/css; charset=UTF-8');
	header('Cache-control: max-age=152800');
	header('Expires: '.date('r', time()+152800));

	if(!isset($_GET['skin']) || !isset($_GET['type'])) exit(1);

	$skins = get_skins();
	if(!isset($skins[$_GET['skin']]) || !isset($skins[$_GET['skin']][1][$_GET['type']])) exit(1);

	foreach($skins[$_GET['skin']][1][$_GET['type']][1] as $fname)
	{
		$fname = $_GET['skin'].'/'.str_replace('\\', '/', $fname);
		if(strstr($fname, '/../') || !is_file($fname)) continue;
		echo "/* ".$fname." */\n\n";
		if(is_readable($fname))
		{
			$file = file_get_contents($fname);
			$file = preg_replace("/\[image:(.*?)\]/ie", "make_image_path('$1')", $file);
			print($file);
		}
		echo "\n\n\n";
	}

	function make_image_path($path)
	{
		if(!preg_match("/^(\\/|[a-z0-9]+:\/)/i", $path))
			$path = $_GET['skin']."/".$path;
		$path = "image.php?image=".urlencode($path);
		return $path;
	}
?>