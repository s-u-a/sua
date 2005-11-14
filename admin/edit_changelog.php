<?php
	require('include.php');

	if(!$admin_array['permissions'][8])
		die('No access.');

	if(isset($_GET['delete']))
	{
		$old_changelog = '';
		if(is_file('../db_things/changelog') && is_readable('../db_things/changelog'))
			$old_changelog = trim(file_get_contents('../db_things/changelog'));
		if(strlen($old_changelog) <= 0)
			$old_changelog = array();
		else
			$old_changelog = preg_split("/\r\n|\r|\n/", $old_changelog);
		if(isset($old_changelog[count($old_changelog)-$_GET['delete']]))
		{
			$fh = fopen('../db_things/changelog', 'w');
			if($fh)
			{
				flock($fh, LOCK_EX);

				unset($old_changelog[count($old_changelog)-$_GET['delete']]);
				fwrite($fh, implode("\n", $old_changelog));

				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		unset($old_changelog);
	}

	if(isset($_POST['add']) && strlen(trim($_POST['add'])) > 0)
	{
		$old_changelog = '';
		if(is_file('../db_things/changelog') && is_readable('../db_things/changelog'))
			$old_changelog = trim(file_get_contents('../db_things/changelog'));
		$fh = fopen('../db_things/changelog', 'w');
		if($fh)
		{
			flock($fh, LOCK_EX);
			fwrite($fh, time()."\t".$_POST['add']."\n");
			fwrite($fh, $old_changelog);
			flock($fh, LOCK_UN);
			fclose($fh);
		}
		unset($old_changelog);
	}

	admin_gui::html_head();
?>
<form action="edit_changelog.php" method="post">
	<ul>
		<li><input type="text" name="add" value="" /> <button type="submit">Hinzufügen</button></li>
<?php
	$changelog = '';
	if(is_file('../db_things/changelog') && is_readable('../db_things/changelog'))
		$changelog = trim(file_get_contents('../db_things/changelog'));
	if(strlen($changelog) <= 0)
		$changelog = array();
	else
		$changelog = preg_split("/\r\n|\r|\n/", $changelog);

	foreach($changelog as $i=>$log)
	{
		echo "\t\t<li>";
		$log = explode("\t", $log, 2);
		if(count($log) < 2)
			echo utf8_htmlentities($log[0]);
		else
			echo date('Y-m-d, H:i:s', $log[0]).': '.utf8_htmlentities($log[1]);
		echo " [<a href=\"edit_changelog.php?delete=".htmlentities(urlencode(count($changelog)-$i))."\">Löschen</a>]</li>\n";
	}
?>
	</ul>
</form>
<?php
	admin_gui::html_foot();
?>