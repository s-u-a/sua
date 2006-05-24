<?php
	require('scripts/include.php');

	login_gui::html_head();

	$changelog = '';
	if(is_file(GDB_DIR.'/changelog') && is_readable(GDB_DIR.'/changelog'))
		$changelog = file_get_contents(GDB_DIR.'/changelog');

	$changelog = preg_split("/\r\n|\r|\n/", $changelog);
?>
<h2 id="changelog" xml:lang="en">Changelog</h2>
<ol class="changelog">
<?php
	foreach($changelog as $log)
	{
		$log = explode("\t", $log, 2);
		if(count($log) < 2)
		{
?>
	<li><?=utf8_htmlentities($log[0])?></li>
<?php
		}
		else
		{
?>
	<li><span class="zeit"><?=date('Y-m-d, H:i:s', $log[0])?>:</span> <?=utf8_htmlentities($log[1])?></li>
<?php
		}
	}

	login_gui::html_foot();
?>