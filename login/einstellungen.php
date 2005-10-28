<?php
	require('scripts/include.php');

	$changed = false;
	if(isset($_POST['skin-choice']))
	{
		if($_POST['skin-choice'] == '')
		{
			if(isset($_POST['skin']))
			{
				$user_array['skin'] = $_POST['skin'];
				$changed = true;
			}
		}
		else
		{
			$user_array['skin'] = $_POST['skin-choice'];
			$changed = true;
		}
	}

	if(isset($_POST['benutzerbeschreibung']))
	{
		$user_array['description'] = preg_replace("/\r\n|\r|\n/", "\n", $_POST['benutzerbeschreibung']);
		$changed = true;
	}

	if(isset($_POST['spionagesonden']))
	{
		$user_array['sonden'] = (int) $_POST['spionagesonden'];
		if($user_array['sonden'] <= 0)
			$user_array['sonden'] = 1;
		$changed = true;
	}

	if(isset($_POST['change-receive']) && $_POST['change-receive'])
	{
		$user_array['receive'][1][1] = isset($_POST['nachrichten'][1][1]);
		$user_array['receive'][2][1] = isset($_POST['nachrichten'][2][1]);
		$user_array['receive'][3][0] = isset($_POST['nachrichten'][3][0]);
		$user_array['receive'][3][1] = isset($_POST['nachrichten'][3][1]);
		$user_array['receive'][4][1] = isset($_POST['nachrichten'][4][1]);
		$user_array['receive'][5][0] = isset($_POST['nachrichten'][5][0]);
		$user_array['receive'][5][1] = isset($_POST['nachrichten'][5][1]);

		$user_array['fastbuild'] = isset($_POST['fastbuild']);
		$user_array['shortcuts'] = isset($_POST['shortcuts']);
		$user_array['tooltips'] = isset($_POST['tooltips']);

		$changed = true;
	}

	if(isset($_POST['umode']) && time()-$user_array['umode_time'] >= 259200)
	{
		if(!$user_array['umode'])
		{
			$planets = array_keys($user_array['planets']);

			$new_username = substr($_SESSION['username'], 0, 20);
			$new_username .= ' (U)';
			foreach($planets as $planet)
			{
				$pos = explode(':', $user_array['planets'][$planet]['pos']);
				$info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $info[0], $new_username, $info[2]);
			}

			$user_array['umode'] = true;
			$user_array['umode_time'] = time();
		}
		else
		{
			$planets = array_keys($user_array['planets']);
			foreach($planets as $planet)
			{
				$pos = explode(':', $user_array['planets'][$planet]['pos']);
				$info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $info[0], $_SESSION['username'], $info[2]);
			}

			$user_array['umode'] = false;
			$user_array['umode_time'] = time();
		}
	}

	if(isset($_POST['old-password']) && isset($_POST['new-password']) && isset($_POST['new-password2']) && ($_POST['old-password'] != $_POST['new-password'] || $_POST['new-password'] != $_POST['new-password2']))
	{
		# Passwort aendern
		if(md5($_POST['old-password']) != $user_array['password'])
			$error = 'Das alte Passwort stimmt nicht.';
		elseif($_POST['new-password'] != $_POST['new-password2'])
			$error = 'Die beiden neuen Passwörter stimmen nicht überein.';
		else
		{
			$user_array['password'] = md5($_POST['new-password']);
			$changed = true;
		}
	}

	if($changed && !write_user_array())
		$error = 'Datenbankfehler.';

	login_gui::html_head();
?>
<h2>Einstellungen</h2>
<?php
	if(isset($error) && trim($error) != '')
	{
?>
<p class="error">
	<?=htmlentities($error)."\n"?>
</p>
<?php
	}
?>
<form action="einstellungen.php" method="post" class="einstellungen-formular">
	<fieldset class="verschiedene-einstellungen">
		<legend>Verschiedene Einstellungen</legend>
		<dl>
			<dt class="c-skin"><label for="skin-choice" xml:lang="en">Ski<kbd>n</kbd></label></dt>
			<dd class="c-skin">
				<select name="skin-choice" id="skin-choice" accesskey="n" tabindex="1" onchange="recalc_skin();" onkeyup="recalc_skin();">
<?php
	$selected = $one_selected = !isset($user_array['skin']);
	foreach($skins as $id=>$name)
	{
		if(isset($user_array['skin']) && $user_array['skin'] == $id)
			$selected = $one_selected = true;
?>
					<option value="<?=utf8_htmlentities($id)?>"<?=$selected ? ' selected="selected"' : ''?>><?=utf8_htmlentities($name)?></option>
<?php
		$selected = false;
	}
?>
					<option value=""<?=(!$one_selected) ? ' selected="selected"' : ''?>>Benutzerdefiniert</option>
				</select>
				<input type="text" name="skin" id="skin" value="<?=htmlentities($user_array['skin'])?>" tabindex="2" />
			</dd>
			<script type="text/javascript">
				function recalc_skin()
				{
					var skin = document.getElementById('skin-choice').value;
					if(skin == '')
					{
						document.getElementById('skin').removeAttribute('readonly');
					}
					else
					{
						document.getElementById('skin').setAttribute('readonly', 'readonly');
						document.getElementById('skin').value = skin;
					}
				}
				recalc_skin();
			</script>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung">Ben<kbd>u</kbd>tzerbeschreibung</label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung" cols="50" rows="10" accesskey="u" tabindex="3"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', utf8_htmlentities($user_array['description']))?></textarea></dd>

			<dt class="c-spionagesonden"><label for="spionagesonden">Spionagesonden</label></dt>
			<dd class="c-spionagesonden"><input type="text" name="spionagesonden" id="spionagesonden" value="<?=utf8_htmlentities($user_array['sonden'])?>" title="Anzahl Spionagesonden, die bei der Spionage eines fremden Planeten aus der Karte geschickt werden sollen [J]" accesskey="j" tabindex="4" /></dd>

			<dt class="c-auto-schnellbau"><label for="fastbuild">Auto-Schnellbau</label></dt>
			<dd class="c-auto-schnellbau"><input type="checkbox" name="fastbuild" id="fastbuild"<?=$user_array['fastbuild'] ? ' checked="checked"' : ''?> title="Wird ein Gebäude in Auftrag gegeben, wird automatisch zum nächsten unbeschäftigten Planeten gewechselt [Q]" accesskey="q" tabindex="5" /></dd>

			<dt class="c-schnell-shortcuts"><label for="shortcuts">Schnell-Shortcuts</label></dt>
			<dd class="c-schnell-shortcuts"><input type="checkbox" name="shortcuts" id="shortcuts"<?=$user_array['shortcuts'] ? ' checked="checked"' : ''?> title="Mit dieser Funktion brauchen Sie zum Ausführen der Shortcuts keine weitere Taste zu drücken [X]" accesskey="x" tabindex="6" /></dd>

			<dt class="c-javascript-tooltips"><label for="tooltips">Javascript-Tooltips</label></dt>
			<dd class="c-javascript-tooltips"><input type="checkbox" name="tooltips" id="tooltips"<?=$user_array['tooltips'] ? ' checked="checked"' : ''?> title="Nicht auf langsamen Computern verwenden! Ist dieser Punkt aktiviert, werden die normalen Tooltips durch hübsche JavaScript-Tooltips ersetzt. [Y]" accesskey="y" tabindex="7" /></dd>
		</dl>
	</fieldset>
	<fieldset class="nachrichtentypen-empfangen">
		<legend>Nachrichtentypen empfangen<input type="hidden" name="change-receive" value="1" /></legend>
		<table>
			<thead>
				<tr>
					<th>Nachrichtentyp</th>
					<th>Ankunft</th>
					<th>Rückkehr</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>Kaempfe</th>
					<td></td>
					<td><input type="checkbox" name="nachrichten[1][1]" tabindex="8"<?=$user_array['receive'][1][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th>Spionage</th>
					<td></td>
					<td><input type="checkbox" name="nachrichten[2][1]" tabindex="9"<?=$user_array['receive'][2][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th>Transport</th>
					<td><input type="checkbox" name="nachrichten[3][0]" tabindex="10"<?=$user_array['receive'][3][0] ? ' checked="checked"' : ''?> /></td>
					<td><input type="checkbox" name="nachrichten[3][1]" tabindex="11"<?=$user_array['receive'][3][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th>Sammeln</th>
					<td></td>
					<td><input type="checkbox" name="nachrichten[4][1]" tabindex="12"<?=$user_array['receive'][4][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th>Besiedelung</th>
					<td><input type="checkbox" name="nachrichten[5][0]" tabindex="13"<?=$user_array['receive'][5][0] ? ' checked="checked"' : ''?> /></td>
					<td><input type="checkbox" name="nachrichten[5][1]" tabindex="14"<?=$user_array['receive'][5][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
	<fieldset class="urlaubsmodus">
		<legend>Urlaubsmodus</legend>
<?php
	if(!$user_array['umode'])
	{
		if(time()-$user_array['umode_time'] >= 259200)
		{
?>
		<div><button name="umode" value="on">Urlaubsmodus</button></div>
		<p>Sie werden frühestens nach drei Tagen (<?=date('Y-m-d, H:i', time()+259200)?>, Serverzeit) aus dem Urlaubsmodus zurückkehren können.</p>
<?php
		}
		else
		{
?>
		<p>Sie können erst wieder ab dem <?=date('Y-m-d, H:i', $user_array['umode_time']+259200)?> (Serverzeit) in den Urlaubsmodus wechseln.</p>
<?php
		}
	}
	elseif(time()-$user_array['umode_time'] >= 259200)
	{
?>
		<div><button name="umode" value="on">Urlaubsmodus verlassen</button></div>
<?php
	}
	else
	{
?>
		<p>Sie können den Urlaubsmodus spätestens am <?=date('Y-m-d, H:i', $user_array['umode_time']+259200)?> (Serverzeit) verlassen.</p>
<?php
	}
?>
	</fieldset>
	<fieldset class="passwort-aendern">
		<legend>Passwort ändern</legend>
		<dl>
			<dt class="c-altes-passwort"><label for="old-password">Altes Passw<kbd>o</kbd>rt</label></dt>
			<dd class="c-altes-passwort"><input type="password" name="old-password" id="old-password" tabindex="16" accesskey="o" /></dd>

			<dt class="c-neues-passwort"><label for="new-password">Neues Passwort</label></dt>
			<dd class="c-neues-passwort"><input type="password" name="new-password" id="new-password" tabindex="17" /></dd>

			<dt class="c-neues-passwort-wiederholen"><label for="new-password2">Neues Passwort wiederholen</label></dt>
			<dd class="c-neues-passwort-wiederholen"><input type="password" name="new-password2" id="new-password2" tabindex="18" /></dd>
		</dl>
	</fieldset>
	<div><button type="submit" tabindex="15" accesskey="w" title="[W]">Speichern</button></div>
</form>
<?php
	login_gui::html_foot();
?>