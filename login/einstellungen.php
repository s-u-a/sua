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

	if(isset($_POST['schrift']))
	{
		$user_array['schrift'] = ($_POST['schrift'] == true);
		$changed = true;
	}

	if(isset($_POST['benutzerbeschreibung']))
	{
		$user_array['description'] = preg_replace("/\r\n|\r|\n/", "\n", $_POST['benutzerbeschreibung']);
		$user_array['description_parsed'] = parse_html($user_array['description']);
		$changed = true;
	}

	if(isset($_POST['spionagesonden']))
	{
		$user_array['sonden'] = (int) $_POST['spionagesonden'];
		if($user_array['sonden'] <= 0)
			$user_array['sonden'] = 1;
		$changed = true;
	}

	if(isset($_POST['autorefresh']))
	{
		$user_array['ress_refresh'] = (real) str_replace(',', '.', $_POST['autorefresh']);
		if($user_array['ress_refresh'] <= 0)
			$user_array['ress_refresh'] = 0;
		if($user_array['ress_refresh'] > 0 && $user_array['ress_refresh'] < 0.2)
			$user_array['ress_refresh'] = 0.2;
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
		$user_array['ipcheck'] = isset($_POST['ipcheck']);
		$user_array['noads'] = isset($_POST['noads']);
		$user_array['show_extern'] = isset($_POST['show_extern']);

		$changed = true;
	}

	if((!isset($user_array['locked']) || !$user_array['locked']) && isset($_POST['umode']) && time()-$user_array['umode_time'] >= 259200)
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
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $info[0], $new_username, $info[2], $info[3]);
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
				universe::set_planet_info($pos[0], $pos[1], $pos[2], $info[0], $_SESSION['username'], $info[2], $info[3]);
			}

			$user_array['umode'] = false;
			$user_array['umode_time'] = time();
		}

		logfile::action('25', $user_array['umode']);

		$changed = true;
	}

	if(isset($_POST['email']))
	{
		$user_array['email'] = $_POST['email'];
		$changed = true;
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

	if($changed)
	{
		if(!write_user_array())
			$error = 'Datenbankfehler.';
		else
			logfile::action('24');
	}

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
<form action="einstellungen.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="einstellungen-formular">
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

			<dt class="c-schrift"><label for="schrift-choice">Schrift</label></dt>
			<dd class="c-schrift">
				<select name="schrift" id="schrift-choice" tabindex="3">
					<option value="1"<?=(!isset($user_array['schrift']) || $user_array['schrift']) ? ' selected="selected"' : ''?>>Lieblingsschrift des Admins</option>
					<option value="0"<?=(isset($user_array['schrift']) && !$user_array['schrift']) ? ' selected="selected"' : ''?>>Ihre Lieblingsschrift</option>
				</select>
			</dd>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung">Ben<kbd>u</kbd>tzerbeschreibung</label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung" cols="50" rows="10" accesskey="u" tabindex="4"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', utf8_htmlentities($user_array['description']))?></textarea></dd>

			<dt class="c-spionagesonden"><label for="spionagesonden">Spionagesonden</label></dt>
			<dd class="c-spionagesonden"><input type="text" name="spionagesonden" id="spionagesonden" value="<?=utf8_htmlentities($user_array['sonden'])?>" title="Anzahl Spionagesonden, die bei der Spionage eines fremden Planeten aus der Karte geschickt werden sollen [J]" accesskey="j" tabindex="5" /></dd>

			<dt class="c-auto-schnellbau"><label for="fastbuild">Auto-Schnellbau</label></dt>
			<dd class="c-auto-schnellbau"><input type="checkbox" name="fastbuild" id="fastbuild"<?=$user_array['fastbuild'] ? ' checked="checked"' : ''?> title="Wird ein Gebäude in Auftrag gegeben, wird automatisch zum nächsten unbeschäftigten Planeten gewechselt [Q]" accesskey="q" tabindex="6" /></dd>

			<dt class="c-schnell-shortcuts"><label for="shortcuts">Schnell-Shortcuts</label></dt>
			<dd class="c-schnell-shortcuts"><input type="checkbox" name="shortcuts" id="shortcuts"<?=$user_array['shortcuts'] ? ' checked="checked"' : ''?> title="Mit dieser Funktion brauchen Sie zum Ausführen der Shortcuts keine weitere Taste zu drücken [X]" accesskey="x" tabindex="7" /></dd>

			<dt class="c-javascript-tooltips"><label for="tooltips">Javascript-Tooltips</label></dt>
			<dd class="c-javascript-tooltips"><input type="checkbox" name="tooltips" id="tooltips"<?=$user_array['tooltips'] ? ' checked="checked"' : ''?> title="Nicht auf langsamen Computern verwenden! Ist dieser Punkt aktiviert, werden die normalen Tooltips durch hübsche JavaScript-Tooltips ersetzt. [Y]" accesskey="y" tabindex="8" /></dd>

			<dt class="c-auto-refresh"><label for="autorefresh">Auto-Refresh</label></dt>
			<dd class="c-auto-refresh"><input type="text" name="autorefresh" id="autorefresh" value="<?=utf8_htmlentities($user_array['ress_refresh'])?>" title="Wird hier eine Zahl größer als 0 eingetragen, wird in deren Sekundenabstand die Rohstoffanzeige oben automatisch aktualisiert. (Hinweis: Diese Funktion erzeugt keinen zusätzlichen Traffic)" tabindex="9" /></dd>

			<dt class="c-ip-schutz"><label for="ipcheck">IP-Schutz</label></dt>
			<dd class="c-ip-schutz"><input type="checkbox" name="ipcheck" id="ipcheck"<?=(!isset($user_array['ipcheck']) || $user_array['ipcheck']) ? ' checked="checked"' : ''?> title="Wenn diese Option deaktiviert ist, kann Ihre Session von mehreren IP-Adressen gleichzeitig genutzt werden. (Unsicher!)" tabindex="10" /></dd>
			
			<dt class="c-werbung-ausblenden"><label for="noads">Werbung ausblenden</label></dt>
			<dd class="c-werbung-ausblenden"><input type="checkbox" name="noads" id="noads"<?=(isset($user_array['noads']) && $user_array['noads']) ? ' checked="checked"' : ''?> title="Wenn Sie die Werbung eingeblendet lassen, helfen Sie, die Finanzen des Spiels zu decken." tabindex="11" /></dd>
			
			<dt class="c-externe-navigationslinks"><label for="show-extern">Externe Navigationslinks</label></dt>
			<dd class="c-externe-navigationslinks"><input type="checkbox" name="show_extern" id="show-extern"<?=(isset($user_array['show_extern']) && $user_array['show_extern']) ? ' checked="checked"' : ''?> title="Wenn diese Option aktiviert ist, werden in der Navigation Links auf spielexterne Seiten wie das Board angezeigt." tabindex="12" /></dd>
		</dl>
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
	</fieldset>
	<fieldset class="nachrichtentypen-empfangen">
		<legend>Nachrichtentypen empfangen<input type="hidden" name="change-receive" value="1" /></legend>
		<table>
			<thead>
				<tr>
					<th class="c-nachrichtentyp">Nachrichtentyp</th>
					<th class="c-ankunft">Ankunft</th>
					<th class="c-rueckkehr">Rückkehr</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th class="c-nachrichtentyp">Kämpfe</th>
					<td class="c-ankunft leer"></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[1][1]" tabindex="13"<?=$user_array['receive'][1][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Spionage</th>
					<td class="c-ankunft leer"></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[2][1]" tabindex="14"<?=$user_array['receive'][2][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Transport</th>
					<td class="c-ankunft"><input type="checkbox" name="nachrichten[3][0]" tabindex="15"<?=$user_array['receive'][3][0] ? ' checked="checked"' : ''?> /></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[3][1]" tabindex="16"<?=$user_array['receive'][3][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Sammeln</th>
					<td class="c-ankunft leer"></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[4][1]" tabindex="17"<?=$user_array['receive'][4][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Besiedelung</th>
					<td class="c-ankunft"><input type="checkbox" name="nachrichten[5][0]" tabindex="18"<?=$user_array['receive'][5][0] ? ' checked="checked"' : ''?> /></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[5][1]" tabindex="19"<?=$user_array['receive'][5][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
	<div class="einstellungen-speichern-1"><input type="submit" title="[W]" value="Speichern" /></div>
<?php
	if(!isset($user_array['locked']) || !$user_array['locked'])
	{
?>
	<fieldset class="urlaubsmodus">
		<legend>Urlaubsmodus</legend>
<?php
		if(!$user_array['umode'])
		{
			if(time()-$user_array['umode_time'] >= 259200)
			{
?>
		<div><input type="submit" name="umode" value="Urlaubsmodus" tabindex="21" onclick="return confirm('Wollen Sie den Urlaubsmodus wirklich betreten?');" /></div>
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
		<div><input type="submit" name="umode" value="Urlaubsmodus verlassen" tabindex="21" onclick="return confirm('Wollen Sie den Urlaubsmodus wirklich verlassen?');" /></div>
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
<?php
	}
?>
	<fieldset class="email-adresse">
		<legend>E-Mail-Adresse</legend>
		<dl>
			<dt class="c-email-adresse"><label for="email">E-Mail-Adresse</label></dt>
			<dd class="c-email-adresse"><input type="text" name="email" id="email" value="<?=utf8_htmlentities($user_array['email'])?>" title="Ihre E-Mail-Adresse wird benötigt, wenn Sie Ihr Passwort vergessen haben. [Z]" tabindex="22" accesskey="z" /></dd>
		</dl>
	</fieldset>
	<fieldset class="passwort-aendern">
		<legend>Passwort ändern</legend>
		<dl>
			<dt class="c-altes-passwort"><label for="old-password">Altes Passw<kbd>o</kbd>rt</label></dt>
			<dd class="c-altes-passwort"><input type="password" name="old-password" id="old-password" tabindex="23" accesskey="o" /></dd>

			<dt class="c-neues-passwort"><label for="new-password">Neues Passwort</label></dt>
			<dd class="c-neues-passwort"><input type="password" name="new-password" id="new-password" tabindex="24" /></dd>

			<dt class="c-neues-passwort-wiederholen"><label for="new-password2">Neues Passwort wiederholen</label></dt>
			<dd class="c-neues-passwort-wiederholen"><input type="password" name="new-password2" id="new-password2" tabindex="25" /></dd>
		</dl>
	</fieldset>
	<div class="einstellungen-speichern-2"><button type="submit" tabindex="20" accesskey="w" title="[W]">Speichern</button></div>
</form>
<?php
	login_gui::html_foot();
?>