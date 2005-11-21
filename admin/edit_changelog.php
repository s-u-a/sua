<?php
	require('include.php');

	if(!$admin_array['permissions'][8])
		die('No access.');

	$old_version = $version = explode('.', get_version(), 3);
	if(!isset($version[0])) $version[0] = '0';
	if(!isset($version[1])) $version[1] = '0';
	if(!isset($version[2])) $version[2] = '0';

	if(isset($_POST['version']) && is_array($_POST['version']))
	{
		if(isset($_POST['version'][0]))
			$version[0] = $_POST['version'][0];
		if(isset($_POST['version'][1]))
			$version[1] = $_POST['version'][1];
		if(isset($_POST['version'][2]))
			$version[2] = $_POST['version'][2];
	}

	if(isset($_POST['increase_version']) && is_array($_POST['increase_version']))
	{
		if(isset($_POST['increase_version'][0]))
			$version[0]++;
		if(isset($_POST['increase_version'][1]))
			$version[1]++;
		if(isset($_POST['increase_version'][2]))
			$version[2]++;
	}

	if($version != $old_version)
	{
		$version_string = implode('.', $version);
		$fh = fopen(s_root.'/db_things/version', 'w');
		if($fh)
		{
			flock($fh, LOCK_EX);
			fwrite($fh, $version_string);
			flock($fh, LOCK_UN);
			fclose($fh);

			$_POST['add'] = 'Neue Versionsnummer: '.$version_string;
		}
	}

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
	<fieldset>
		<legend>Version</legend>
		<p>Achtung: Durch Dr�cken der <span xml:lang="en">Return</span>-Taste wird unter umst�nden nicht das erzielt, was man w�nscht.</p>
		<p><input name="version[0]" value="<?=utf8_htmlentities($version[0])?>" size="3" /><input type="submit" name="increase_version[0]" value="&uarr;" />&nbsp;.&nbsp;<input name="version[1]" value="<?=utf8_htmlentities($version[1])?>" size="3" /><input type="submit" name="increase_version[1]" value="&uarr;" />&nbsp;.&nbsp;<input name="version[2]" value="<?=utf8_htmlentities($version[2])?>" size="3" /><input type="submit" name="increase_version[2]" value="&uarr;" /></p>
		<div><button type="submit">Speichern</button></div>
	</fieldset>
</form>
<form action="edit_changelog.php" method="post">
	<ul>
		<li><input type="text" name="add" value="" /> <button type="submit">Hinzuf�gen</button></li>
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
		echo " [<a href=\"edit_changelog.php?delete=".htmlentities(urlencode(count($changelog)-$i))."\">L�schen</a>]</li>\n";
	}
?>
	</ul>
</form>
<?php
	admin_gui::html_foot();
?>