<?php
	require('include.php');

	if(!$admin_array['permissions'][11])
		die(h(_('No access.')));

	if(!isset($_GET['action']))
	{
		$url = global_setting("PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/admin/index.php';
		header('Location: '.$url, true, 303);
		die('HTTP redirect: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a>');
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

				write_admin_list($admins) && protocol("11.1", $_POST['new_admin'][0]);
			}
			else
			{
?>
<form action="usermanagement.php?action=add" method="post">
	<table border="1">
		<thead>
			<tr>
				<th rowspan="2" title="<?=h(_("Name des Administrators"))?>"><?=h(_("Name"))?></th>
				<th rowspan="2"><?=h(_("Passwort"))?></th>
				<th colspan="7"><?=h(_("Benutzeraktionen"))?></th>
				<th rowspan="2" xml:lang="en" title="<?=h(_("Anfängerschutz ein-/ausschalten"))?>"><?=h(_("Anfängerschutz"))?></th>
				<th rowspan="2" xml:lang="en" title="<?=h(_("Changelog bearbeiten"))?>"><?=h(_("Changelog"))?></th>
				<th rowspan="2" title="<?=h(_("Nachricht versenden"))?>"><?=h(_("Nachricht"))?></th>
				<th rowspan="2" title="<?=h(_("Log-Dateien ansehen"))?>"><?=h(_("Logs"))?></th>
				<th rowspan="2" title="<?=h(_("Adminstratoren verwalten"))?>"><?=h(_("Admins"))?></th>
				<th rowspan="2" title="<?=h(_("Wartungsarbeiten ein-/ausschalten"))?>"><?=h(_("Wartung"))?></th>
				<th rowspan="2" title="<?=h(_("Spiel sperren/entsperren"))?>"><?=h(_("Spiel sperren"))?></th>
				<th rowspan="2"><?=h(_("Flottensperre"))?></th>
				<th rowspan="2" title="<?=h(_("News bearbeiten"))?>"><?=h(_("News"))?></th>
			</tr>
			<tr>
				<th title="<?=h(_("Die Benutzerliste einsehen"))?>"><?=h(_("Liste"))?></th>
				<th title="<?=h(_("Als Geist als ein Benutzer anmelden"))?>"><?=h(_("Geist"))?></th>
				<th title="<?=h(_("Das Passwort eines Benutzers ändern"))?>"><?=h(_("Passwort"))?></th>
				<th title="<?=h(_("Die Passwörter zweier Benutzer vergleichen"))?>"><?=h(_("Pwd.-Vergl."))?></th>
				<th title="<?=h(_("Einen Benutzer löschen"))?>"><?=h(_("Löschen"))?></th>
				<th title="<?=h(_("Einen Benutzer sperren/entsperren"))?>"><?=h(_("Sperren"))?></th>
				<th title="<?=h(_("Einen Benutzer umbenennen"))?>"><?=h(_("Umbenennen"))?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><input type="text" name="new_admin[0]" /></td>
				<td><input type="text" name="new_admin[1]" /></td>
<?php
				for($j=0; $j<=15; $j++)
				{
					if($j == 15) $j = 14; elseif($j == 14) $j = 15;
?>
				<td><input type="checkbox" name="new_admin[<?=htmlspecialchars($j+2)?>]" value="1" /></td>
<?php
					if($j == 15) $j = 14; elseif($j == 14) $j = 15;
				}
?>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="16"><button type="submit"<?=accesskey_attr(_("Hinzufügen&[admin/usermanagement.php|1]"))?>><?=h(_("Hinzufügen&[admin/usermanagement.php|1]"))?></button></td>
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
				write_admin_list($admins) && protocol("11.4", $_GET['delete']);
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
					if($old_admins[$no] != $this_name)
					{
						protocol("11.3", $old_admins[$no], $this_name);
						if($no == $session_key)
							$_SESSION['admin_username'] = $this_name;
					}
					$new_admins[$this_name] = array();
					$new_admins[$this_name]['password'] = $this_password;
					$new_admins[$this_name]['permissions'] = array();
					$prot = false;
					for($i=0; $i<=15; $i++)
					{
						$new_admins[$this_name]['permissions'][$i] = (isset($admin[$i+1]) ? '1' : '0');
						if($admins[$this_name]['permissions'][$i] != $new_admins[$this_name]['permissions'][$i])
							$prot = true;
					}
					if($prot) protocol("11.2", $this_name);
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
				<th rowspan="2" title="<?=h(_("Name des Administrators"))?>"><?=h(_("Name"))?></th>
				<th rowspan="2"><?=h(_("Passwort"))?></th>
				<th colspan="7"><?=h(_("Benutzeraktionen"))?></th>
				<th rowspan="2" xml:lang="en" title="<?=h(_("Anfängerschutz ein-/ausschalten"))?>"><?=h(_("Anfängerschutz"))?></th>
				<th rowspan="2" xml:lang="en" title="<?=h(_("Changelog bearbeiten"))?>"><?=h(_("Changelog"))?></th>
				<th rowspan="2" title="<?=h(_("Nachricht versenden"))?>"><?=h(_("Nachricht"))?></th>
				<th rowspan="2" title="<?=h(_("Log-Dateien ansehen"))?>"><?=h(_("Logs"))?></th>
				<th rowspan="2" title="<?=h(_("Adminstratoren verwalten"))?>"><?=h(_("Admins"))?></th>
				<th rowspan="2" title="<?=h(_("Wartungsarbeiten ein-/ausschalten"))?>"><?=h(_("Wartung"))?></th>
				<th rowspan="2" title="<?=h(_("Spiel sperren/entsperren"))?>"><?=h(_("Spiel sperren"))?></th>
				<th rowspan="2"><?=h(_("Flottensperre"))?></th>
				<th rowspan="2" title="<?=h(_("News bearbeiten"))?>"><?=h(_("News"))?></th>
			</tr>
			<tr>
				<th title="<?=h(_("Die Benutzerliste einsehen"))?>"><?=h(_("Liste"))?></th>
				<th title="<?=h(_("Als Geist als ein Benutzer anmelden"))?>"><?=h(_("Geist"))?></th>
				<th title="<?=h(_("Das Passwort eines Benutzers ändern"))?>"><?=h(_("Passwort"))?></th>
				<th title="<?=h(_("Die Passwörter zweier Benutzer vergleichen"))?>"><?=h(_("Pwd.-Vergl."))?></th>
				<th title="<?=h(_("Einen Benutzer löschen"))?>"><?=h(_("Löschen"))?></th>
				<th title="<?=h(_("Einen Benutzer sperren/entsperren"))?>"><?=h(_("Sperren"))?></th>
				<th title="<?=h(_("Einen Benutzer umbenennen"))?>"><?=h(_("Umbenennen"))?></th>
			</tr>
		</thead>
		<tbody>
<?php
			$i = 0;
			foreach($admins as $name=>$settings)
			{
?>
			<tr>
				<td><input type="text" name="admin_array[<?=htmlspecialchars($i)?>][0]" value="<?=htmlspecialchars($name)?>" /></td>
<?php
				for($j=0; $j<=15; $j++)
				{
					if($j == 14) $j = 15; elseif($j == 15) $j = 14;
?>
				<td><input type="checkbox" name="admin_array[<?=htmlspecialchars($i)?>][<?=htmlspecialchars($j+1)?>]" value="1"<?=$settings['permissions'][$j] ? ' checked="checked"' : ''?><?=($j==11 && $name==$_SESSION['admin_username'])? ' disabled="disabled"' : ''?> /></td>
<?php
					if($j == 14) $j = 15; elseif($j == 15) $j = 14;
				}

				if($name == $_SESSION['admin_username'])
				{
?>
				<td>[<?=h(_("Löschen"))?>]</td>
<?php
				}
				else
				{
?>
				<td><a href="?action=delete&amp;delete=<?=htmlspecialchars(urlencode($i))?>">[<?=h(_("Löschen"))?>]</a></td>
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
				<td colspan="15"><button type="submit"><?=h(_("Speichern"))?></button></td>
			</tr>
		</tfoot>
	</table>
</form>
<?php
			break;
	}

	admin_gui::html_foot();
?>
