<?php
	require('include.php');

	if(!$admin_array['permissions'][11])
		die('No access.');

	if(!isset($_GET['action']))
	{
		$url = PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/admin/index.php';
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlentities($url).'">'.htmlentities($url).'</a>');
	}

	admin_gui::html_head();

	switch($_GET['action'])
	{
		case 'add':
			if(isset($_POST['new_admin']) && count($_POST['new_admin']) >= 2)
			{
				if(isset($admins[$_POST['new_admin'][0]]))
				{
					if(preg_match('/_([0-9]+)$/', $_POST['new_admin'][0], $match))
					{
						$i = $match[1]+1;
						$_POST['new_admin'][0] = substr($_POST['new_admin'][0], 0, -strlen($i)-1);
					}
					else
						$i=0;
					while(isset($admins[$_POST['new_admin'][0].'_'.$i]))
						$i++;
					$_POST['new_admin'][0] .= '_'.$i;
				}

				$admins[$_POST['new_admin'][0]]['password'] = md5($_POST['new_admin'][1]);
				$admins[$_POST['new_admin'][0]]['permissions'] = array();

				for($i=0; $i<=14; $i++)
					$admins[$_POST['new_admin'][0]]['permissions'][$i] = (isset($_POST['new_admin'][$i+2]) ? '1' : '0');

				write_admin_list($admins);
			}
			else
			{
?>
<form action="usermanagement.php?action=add" method="post">
	<table border="1">
		<thead>
			<tr>
				<th rowspan="2" title="Name des Administrators">Name</th>
				<th rowspan="2">Passwort</th>
				<th colspan="7">Benutzeraktionen</th>
				<th rowspan="2" xml:lang="en" title="Todo-Liste bearbeiten">Todo</th>
				<th rowspan="2" xml:lang="en" title="Changelog bearbeiten">Changelog</th>
				<th rowspan="2" title="Nachricht versenden">Nachricht</th>
				<th rowspan="2" title="Log-Dateien ansehen"><span xml:lang="en">Logs</span></th>
				<th rowspan="2" title="Adminstratoren verwalten"><span xml:lang="en">Admins</span></th>
				<th rowspan="2" title="Wartungsarbeiten ein-/ausschalten">Wartung</th>
				<th rowspan="2" title="Spiel sperren/entsperren">Spiel sperren</th>
				<th rowspan="2" title="News bearbeiten"><span xml:lang="en">News</span></th>
			</tr>
			<tr>
				<th title="Die Benutzerliste einsehen">Liste</th>
				<th title="Als Geist als ein Benutzer anmelden">Geist</th>
				<th title="Das Passwort eines Benutzers ändern">Passwort</th>
				<th title="Die Passwörter zweier Benutzer vergleichen"><abbr title="Passwort">Pwd.</abbr>-<abbr title="Vergleich">Vergl.</abbr></th>
				<th title="Einen Benutzer löschen">Löschen</th>
				<th title="Einen Benutzer sperren/entsperren">Sperren</th>
				<th title="Einen Benutzer umbenennen">Umbenennen</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><input type="text" name="new_admin[0]" /></td>
				<td><input type="text" name="new_admin[1]" /></td>
<?php
				for($j=0; $j<=14; $j++)
				{
?>
				<td><input type="checkbox" name="new_admin[<?=htmlentities($j+2)?>]" value="1" /></td>
<?php
				}
?>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="16"><button type="submit">Hinzufügen</button></td>
			</tr>
		</tfoot>
	</table>
</form>
<?php
				break;
			}

		case 'delete':
			$admin_keys = array_keys($admins);
			if(isset($_GET['delete']) && isset($admin_keys[$_GET['delete']]) && $admin_keys[$_GET['delete']] != $_SESSION['admin_username'])
			{
				unset($admins[$admin_keys[$_GET['delete']]]);
				write_admin_list($admins);
			}

		case 'edit':
			if(isset($_POST['admin_array']))
			{
				$old_admins = array_keys($admins);
				$new_admins = array();
				$session_key = array_search($_SESSION['admin_username'], $old_admins);
				foreach($_POST['admin_array'] as $no=>$admin)
				{
					if(!isset($old_admins[$no]))
						continue;
					$this_password = $admins[$old_admins[$no]]['password'];
					$this_name = $admin[0];
					if(isset($new_admins[$this_name]))
					{
						if(preg_match('/_([0-9]+)$/', $this_name, $match))
						{
							$i = $match[1]+1;
							$this_name = substr($this_name, 0, -strlen($i)-1);
						}
						else
							$i=0;
						while(isset($new_admins[$this_name.'_'.$i]))
							$i++;
						$this_name .= '_'.$i;
					}
					if($old_admins[$no] != $this_name && $no == $session_key)
						$_SESSION['admin_username'] = $this_name;
					$new_admins[$this_name] = array();
					$new_admins[$this_name]['password'] = $this_password;
					$new_admins[$this_name]['permissions'] = array();
					for($i=0; $i<=14; $i++)
						$new_admins[$this_name]['permissions'][$i] = (isset($admin[$i+1]) ? '1' : '0');
					$new_admins[$_SESSION['admin_username']]['permissions'][11] = '1';
				}
				write_admin_list($new_admins);
				$admins = $new_admins;
			}
?>
<form action="usermanagement.php?action=edit" method="post">
	<table border="1">
		<thead>
			<tr>
				<th rowspan="2" title="Name des Administrators">Name</th>
				<th colspan="7">Benutzeraktionen</th>
				<th rowspan="2" xml:lang="en" title="Todo-Liste bearbeiten">Todo</th>
				<th rowspan="2" xml:lang="en" title="Changelog bearbeiten">Changelog</th>
				<th rowspan="2" title="Nachricht versenden">Nachricht</th>
				<th rowspan="2" title="Log-Dateien ansehen"><span xml:lang="en">Logs</span></th>
				<th rowspan="2" title="Adminstratoren verwalten"><span xml:lang="en">Admins</span></th>
				<th rowspan="2" title="Wartungsarbeiten ein-/ausschalten">Wartung</th>
				<th rowspan="2" title="Spiel sperren/entsperren">Spiel sperren</th>
				<th rowspan="2" title="News bearbeiten"><span xml:lang="en">News</span></th>
				<th rowspan="2">Löschen</th>
			</tr>
			<tr>
				<th title="Die Benutzerliste einsehen">Liste</th>
				<th title="Als Geist als ein Benutzer anmelden">Geist</th>
				<th title="Das Passwort eines Benutzers ändern">Passwort</th>
				<th title="Die Passwörter zweier Benutzer vergleichen"><abbr title="Passwort">Pwd.</abbr>-<abbr title="Vergleich">Vergl.</abbr></th>
				<th title="Einen Benutzer löschen">Löschen</th>
				<th title="Einen Benutzer sperren/entsperren">Sperren</th>
				<th title="Einen Benutzer umbenennen">Umbenennen</th>
			</tr>
		</thead>
		<tbody>
<?php
			$i = 0;
			foreach($admins as $name=>$settings)
			{
?>
			<tr>
				<td><input type="text" name="admin_array[<?=htmlentities($i)?>][0]" value="<?=utf8_htmlentities($name)?>" /></td>
<?php
				for($j=0; $j<=14; $j++)
				{
?>
				<td><input type="checkbox" name="admin_array[<?=htmlentities($i)?>][<?=htmlentities($j+1)?>]" value="1"<?=$settings['permissions'][$j] ? ' checked="checked"' : ''?><?=($j==11 && $name==$_SESSION['admin_username'])? ' disabled="disabled"' : ''?> /></td>
<?php
				}

				if($name == $_SESSION['admin_username'])
				{
?>
				<td>[Löschen]</td>
<?php
				}
				else
				{
?>
				<td><a href="?action=delete&amp;delete=<?=htmlentities(urlencode($i))?>">[Löschen]</a></td>
<?php
				}
?>
			</tr>
<?php
				$i++;
			}
?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="15"><button type="submit">Speichern</button></td>
			</tr>
		</tfoot>
	</table>
</form>
<?php
			break;
	}

	admin_gui::html_foot();
?>