<?php
	header('Content-type: text/css; charset=ISO-8859-1');
	header('Cache-control: max-age=152800');
	header('Expires: '.strftime('%a, %d %b %Y %T %Z', time()+152800));
	ob_start('ob_gzhandler');


	$skin_id = urldecode($_SERVER['QUERY_STRING']);

	if(is_file('skins') && is_readable('skins'))
	{
		$skins = preg_split("/\r\n|\r|\n/", file_get_contents('skins'));

		foreach($skins as $skin)
		{
			$skin = explode("\t", $skin, 3);
			if(count($skin) < 3)
				continue;

			if($skin[0] != $skin_id)
				continue;

			$skin_files = preg_split('/\\s+/', $skin[2]);
			foreach($skin_files as $skin_file)
			{
				if(is_file($skin_file) && is_readable($skin_file))
				{
					readfile($skin_file);
					echo "\n\n\n";
				}
			}
		}
	}
?>