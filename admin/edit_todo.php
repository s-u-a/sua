<?php
	require('../engine/include.php');

	if(isset($_GET['delete']))
	{
		$old_todo = '';
		if(is_file('../db_things/todo') && is_readable('../db_things/todo'))
			$old_todo = trim(file_get_contents('../db_things/todo'));
		if(strlen($old_todo) <= 0)
			$old_todo = array();
		else
			$old_todo = preg_split("/\r\n|\r|\n/", $old_todo);
		if(isset($old_todo[$_GET['delete']]))
		{
			$fh = fopen('../db_things/todo', 'w');
			if($fh)
			{
				flock($fh, LOCK_EX);

				unset($old_todo[$_GET['delete']]);
				fwrite($fh, implode("\n", $old_todo));

				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		unset($old_todo);
	}

	if(isset($_GET['moveup']) || isset($_GET['movedown']))
	{
		$id = (isset($_GET['moveup']) ? $_GET['moveup'] : $_GET['movedown']);
		$up = (isset($_GET['moveup']) ? 1 : -1);
		$old_todo = '';
		if(is_file('../db_things/todo') && is_readable('../db_things/todo'))
			$old_todo = trim(file_get_contents('../db_things/todo'));
		if(strlen($old_todo) <= 0)
			$old_todo = array();
		else
			$old_todo = preg_split("/\r\n|\r|\n/", $old_todo);
		if(isset($old_todo[$id]) && isset($old_todo[$id-$up]))
		{
			$fh = fopen('../db_things/todo', 'w');
			if($fh)
			{
				flock($fh, LOCK_EX);

				list($old_todo[$id], $old_todo[$id-$up]) = array($old_todo[$id-$up], $old_todo[$id]);
				fwrite($fh, implode("\n", $old_todo));

				flock($fh, LOCK_UN);
				fclose($fh);
			}
		}
		unset($old_todo);
	}

	if(isset($_POST['add']) && strlen(trim($_POST['add'])) > 0)
	{
		$filesize = filesize('../db_things/todo');
		$fh = fopen('../db_things/todo', 'a');
		if($fh)
		{
			flock($fh, LOCK_EX);
			if($filesize > 0)
				fwrite($fh, "\n");
			fwrite($fh, $_POST['add']);
			flock($fh, LOCK_UN);
			fclose($fh);
		}
	}

	admin_gui::html_head();
?>
<form action="edit_todo.php" method="post">
	<ol>
<?php
	$todo = '';
	if(is_file('../db_things/todo') && is_readable('../db_things/todo'))
		$todo = trim(file_get_contents('../db_things/todo'));
	if(strlen($todo) <= 0)
		$todo = array();
	else
		$todo = preg_split("/\r\n|\r|\n/", $todo);

	foreach($todo as $i=>$log)
	{
		echo "\t\t<li>";
		$log = explode("\t", $log, 2);
		if(count($log) < 2)
			echo utf8_htmlentities($log[0]);
		else
			echo date('Y-m-d, H:i:s', $log[0]).': '.utf8_htmlentities($log[1]);
		if($i > 0)
			echo " [<a href=\"edit_todo.php?moveup=".htmlentities(urlencode($i))."\">Hoch</a>]\n";
		if($i < count($todo)-1)
			echo " [<a href=\"edit_todo.php?movedown=".htmlentities(urlencode($i))."\">Runter</a>]\n";
		echo " [<a href=\"edit_todo.php?delete=".htmlentities(urlencode($i))."\">Löschen</a>]</li>\n";
	}
?>
		<li><input type="text" name="add" value="" /> <button type="submit">Hinzufügen</button></li>
	</ol>
</form>
<?php
	admin_gui::html_foot();
?>