<?php
	require("engine/include.php");

	$public_key = gpg_init(true);
	if($public_key)
	{
		header("Content-type: application/pgp");
		header("Content-disposition: attachment;filename=suabot.asc");
		print($public_key);
	}
	else
	{
		header($_SERVER["SERVER_PROTOCOL"]." 501 Not Implemented");
		header("Content-type: text/plain");

		echo "GPG is not activated.\n";
	}
?>