<?php
	require('scripts/include.php');

	$changed = false;

	$receive_settings = $me->checkSetting('receive');
	$show_building = $me->checkSetting('show_building');

	if(isset($_POST['skin-choice']))
	{
		if($_POST['skin-choice'] == '')
		{
			if(isset($_POST['skin']))
				$me->setSetting('skin', $_POST['skin']);
		}
		else
			$me->setSetting('skin', $_POST['skin-choice']);
	}

	if(isset($_POST['schrift']))
		$me->setSetting('schrift', ($_POST['schrift'] == true));

	if(isset($_POST['benutzerbeschreibung']))
		$me->setUserDescription($_POST['benutzerbeschreibung']);

	if(isset($_POST['spionagesonden']))
	{
		$sonden = (int) $_POST['spionagesonden'];
		if($sonden <= 0)
			$sonden = 1;
		$me->setSetting('sonden', $sonden);
	}

	if(isset($_POST['autorefresh']))
	{
		$ress_refresh = (real) str_replace(',', '.', $_POST['autorefresh']);
		if($ress_refresh <= 0)
			$ress_refresh = 0;
		if($ress_refresh > 0 && $ress_refresh < 0.2)
			$ress_refresh = 0.2;
		$me->setSetting('ress_refresh', $ress_refresh);
	}

	if(isset($_POST['change-checkboxes']) && $_POST['change-checkboxes'])
	{
		$receive_settings[1][1] = isset($_POST['nachrichten'][1][1]);
		$receive_settings[2][1] = isset($_POST['nachrichten'][2][1]);
		$receive_settings[3][0] = isset($_POST['nachrichten'][3][0]);
		$receive_settings[3][1] = isset($_POST['nachrichten'][3][1]);
		$receive_settings[4][1] = isset($_POST['nachrichten'][4][1]);
		$receive_settings[5][0] = isset($_POST['nachrichten'][5][0]);
		$receive_settings[5][1] = isset($_POST['nachrichten'][5][1]);
		$me->setSetting('receive', $receive_settings);

		$me->setSetting('fastbuild', isset($_POST['fastbuild']));
		$me->setSetting('shortcuts', isset($_POST['shortcuts']));
		$me->setSetting('tooltips', isset($_POST['tooltips']));
		$me->setSetting('ipcheck', isset($_POST['ipcheck']));
		$me->setSetting('noads', isset($_POST['noads']));
		$me->setSetting('show_extern', isset($_POST['show_extern']));
		$me->setSetting('notify', isset($_POST['notify']));
		$me->setSetting('ajax', isset($_POST['ajax']));
	}

	if(isset($_POST['building']))
	{
		if(isset($_POST['building']['gebaeude']) && in_array($_POST['building']['gebaeude'], array(0,1)))
			$show_building['gebaeude'] = $_POST['building']['gebaeude'];
		if(isset($_POST['building']['forschung']) && in_array($_POST['building']['forschung'], array(0,1)))
			$show_building['forschung'] = $_POST['building']['forschung'];
		if(isset($_POST['building']['roboter']) && in_array($_POST['building']['roboter'], array(0,1,2,3)))
			$show_building['roboter'] = $_POST['building']['roboter'];
		if(isset($_POST['building']['schiffe']) && in_array($_POST['building']['schiffe'], array(0,1,2,3)))
			$show_building['schiffe'] = $_POST['building']['schiffe'];
		if(isset($_POST['building']['verteidigung']) && in_array($_POST['building']['verteidigung'], array(0,1,2,3)))
			$show_building['verteidigung'] = $_POST['building']['verteidigung'];

		$me->setSetting('show_building', $show_building);
	}

	if(!$me->userLocked() && isset($_POST['umode']) && ($me->permissionToUmode() || isset($_SESSION['admin_username'])))
		$me->umode(!$me->umode());

	if(isset($_POST['email']))
		$me->setSetting('email', $_POST['email']);

	if(isset($_POST['old-password']) && isset($_POST['new-password']) && isset($_POST['new-password2']) && ($_POST['old-password'] != $_POST['new-password'] || $_POST['new-password'] != $_POST['new-password2']))
	{
		# Passwort aendern
		if(!$me->checkPassword($_POST['old-password']))
			$error = 'Das alte Passwort stimmt nicht.';
		elseif($_POST['new-password'] != $_POST['new-password2'])
			$error = 'Die beiden neuen Passwörter stimmen nicht überein.';
		else
			$me->setPassword($_POST['new-password']);
	}

	login_gui::html_head();

	$tabindex = 1;
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
<form action="<?=htmlentities(USE_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].h_root.'/login/einstellungen.php?'.urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="einstellungen-formular">
	<fieldset class="verschiedene-einstellungen">
		<legend>Verschiedene Einstellungen<input type="hidden" name="change-checkboxes" value="1" /></legend>
		<dl>
			<dt class="c-skin"><label for="skin-choice" xml:lang="en">Ski<kbd>n</kbd></label></dt>
			<dd class="c-skin">
				<select name="skin-choice" id="skin-choice" accesskey="n" tabindex="<?=$tabindex++?>" onchange="recalc_skin();" onkeyup="recalc_skin();">
<?php
	$selected = $one_selected = !$me->checkSetting('skin');
	foreach($skins as $id=>$name)
	{
		if($id == $me->checkSetting('skin'))
			$selected = $one_selected = true;
?>
					<option value="<?=utf8_htmlentities($id)?>"<?=$selected ? ' selected="selected"' : ''?>><?=utf8_htmlentities($name)?></option>
<?php
		$selected = false;
	}
?>
					<option value=""<?=(!$one_selected) ? ' selected="selected"' : ''?>>Benutzerdefiniert</option>
				</select>
				<input type="text" name="skin" id="skin" value="<?=htmlentities($user_array['skin'])?>" tabindex="<?=$tabindex++?>" />
			</dd>

			<dt class="c-schrift"><label for="schrift-choice">Schrift</label></dt>
			<dd class="c-schrift">
				<select name="schrift" id="schrift-choice" tabindex="<?=$tabindex++?>">
					<option value="1"<?=$me->checkSetting('schrift') ? ' selected="selected"' : ''?>>Lieblingsschrift des Admins</option>
					<option value="0"<?=!$me->checkSetting('schrift') ? ' selected="selected"' : ''?>>Ihre Lieblingsschrift</option>
				</select>
			</dd>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung">Ben<kbd>u</kbd>tzerbeschreibung</label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung" cols="50" rows="10" accesskey="u" tabindex="<?=$tabindex++?>"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', utf8_htmlentities($me->getUserDescription(false)))?></textarea></dd>

			<dt class="c-spionagesonden"><label for="spionagesonden">Spionagesonden</label></dt>
			<dd class="c-spionagesonden"><input type="text" name="spionagesonden" id="spionagesonden" value="<?=utf8_htmlentities($me->checkSetting('sonden'))?>" title="Anzahl Spionagesonden, die bei der Spionage eines fremden Planeten aus der Karte geschickt werden sollen [J]" accesskey="j" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-schnellbau"><label for="fastbuild">Auto-Schnellbau</label></dt>
			<dd class="c-auto-schnellbau"><input type="checkbox" name="fastbuild" id="fastbuild"<?=$me->checkSetting('fastbuild') ? ' checked="checked"' : ''?> title="Wird ein Gebäude in Auftrag gegeben, wird automatisch zum nächsten unbeschäftigten Planeten gewechselt [Q]" accesskey="q" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-schnell-shortcuts"><label for="shortcuts">Schnell-Shortcuts</label></dt>
			<dd class="c-schnell-shortcuts"><input type="checkbox" name="shortcuts" id="shortcuts"<?=$me->checkSetting('shortcuts') ? ' checked="checked"' : ''?> title="Mit dieser Funktion brauchen Sie zum Ausführen der Shortcuts keine weitere Taste zu drücken [X]" accesskey="x" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-javascript-tooltips"><label for="tooltips">Javascript-Tooltips</label></dt>
			<dd class="c-javascript-tooltips"><input type="checkbox" name="tooltips" id="tooltips"<?=$me->checkSetting('tooltips') ? ' checked="checked"' : ''?> title="Nicht auf langsamen Computern verwenden! Ist dieser Punkt aktiviert, werden die normalen Tooltips durch hübsche JavaScript-Tooltips ersetzt. [Y]" accesskey="y" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-refresh"><label for="autorefresh">Auto-Refresh</label></dt>
			<dd class="c-auto-refresh"><input type="text" name="autorefresh" id="autorefresh" value="<?=utf8_htmlentities($me->checkSetting('ress_refresh'))?>" title="Wird hier eine Zahl größer als 0 eingetragen, wird in deren Sekundenabstand die Rohstoffanzeige oben automatisch aktualisiert. (Hinweis: Diese Funktion erzeugt keinen zusätzlichen Traffic)" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-ip-schutz"><label for="ipcheck">IP-Schutz</label></dt>
			<dd class="c-ip-schutz"><input type="checkbox" name="ipcheck" id="ipcheck"<?=$me->checkSetting('ipcheck') ? ' checked="checked"' : ''?> title="Wenn diese Option deaktiviert ist, kann Ihre Session von mehreren IP-Adressen gleichzeitig genutzt werden. (Unsicher!)" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-werbung-ausblenden"><label for="noads">Werbung ausblenden</label></dt>
			<dd class="c-werbung-ausblenden"><input type="checkbox" name="noads" id="noads"<?=$me->checkSetting('noads') ? ' checked="checked"' : ''?> title="Wenn Sie die Werbung eingeblendet lassen, helfen Sie, die Finanzen des Spiels zu decken." tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-externe-navigationslinks"><label for="show-extern">Externe Navigationslinks</label></dt>
			<dd class="c-externe-navigationslinks"><input type="checkbox" name="show_extern" id="show-extern"<?=$me->checkSetting('show_extern') ? ' checked="checked"' : ''?> title="Wenn diese Option aktiviert ist, werden in der Navigation Links auf spielexterne Seiten wie das Board angezeigt." tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-nachrichteninformierung"><label for="notify">Nachrichteninformierung</label></dt>
			<dd class="c-nachrichteninformierung"><input type="checkbox" name="notify" id="notify"<?=$me->checkSetting('notify') ? ' checked="checked"' : ''?> title="Wenn diese Option aktiviert ist, wird nicht nur in der Übersicht angezeigt, dass Sie eine neue Nachricht erhalten haben, sondern auf allen Seiten." tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-ajax"><label for="ajax"><acronym title="Asynchronous JavaScript and XML" xml:lang="en">AJAX</acronym> aktivieren</label></dt>
			<dd class="c-ajax" xml:lang="en"><input type="checkbox" name="ajax" id="ajax"<?=$me->checkSetting('ajax') ? ' checked="checked"' : ''?> title="Nützliche Eingabevereinfachungen, empfehlenswert in neuen Browsern mit schneller Internetverbindung." tabindex="<?=$tabindex++?>" /></dd>
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
		<legend>Nachrichtentypen empfangen</legend>
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
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[1][1]" tabindex="<?=$tabindex++?>"<?=$receive_settings[1][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Spionage</th>
					<td class="c-ankunft leer"></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[2][1]" tabindex="<?=$tabindex++?>"<?=$receive_settings[2][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Transport</th>
					<td class="c-ankunft"><input type="checkbox" name="nachrichten[3][0]" tabindex="<?=$tabindex++?>"<?=$receive_settings[3][0] ? ' checked="checked"' : ''?> /></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[3][1]" tabindex="<?=$tabindex++?>"<?=$receive_settings[3][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Sammeln</th>
					<td class="c-ankunft leer"></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[4][1]" tabindex="<?=$tabindex++?>"<?=$receive_settings[4][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
				<tr>
					<th class="c-nachrichtentyp">Besiedelung</th>
					<td class="c-ankunft"><input type="checkbox" name="nachrichten[5][0]" tabindex="<?=$tabindex++?>"<?=$receive_settings[5][0] ? ' checked="checked"' : ''?> /></td>
					<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[5][1]" tabindex="<?=$tabindex++?>"<?=$receive_settings[5][1] ? ' checked="checked"' : ''?> /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
	<fieldset class="bauende-gegenstaende">
		<legend>Bauende Gegenstände in der Übersicht</legend>
		<table>
			<thead>
				<tr>
					<th>Gegenstandsart</th>
					<th title="Zeigt keine verbleibende Bauzeit in der Übersicht">Ausgeschaltet</th>
					<th title="Zeigt die verbleibende Bauzeit zu dem Gegenstand an, der gerade gebaut wird.">Erster Gegenstand</th>
					<th title="Zeigt die verbleibende Bauzeit aller bauenden Gegenstände des aktuellen Gegenstandstyps an (zum Beispiel 5 Bauroboter).">Erster Gegenstandstyp</th>
					<th title="Zeigt die verbleibende Bauzeit aller bauenden Gegenstände an.">Alle Gegenstände</th>
				</tr>
			</thead>
			<tbody>
				<tr class="c-gebauede">
					<th>Gebäude</th>
					<td><input type="radio" name="building[gebaeude]" value="0"<?=($show_building['gebaeude']==0) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[gebaeude]" value="1"<?=($show_building['gebaeude']==1) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td></td>
					<td></td>
				</tr>
				<tr class="c-forschung">
					<th>Forschung</th>
					<td><input type="radio" name="building[forschung]" value="0"<?=($show_building['forschung']==0) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[forschung]" value="1"<?=($show_building['forschung']==1) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td></td>
					<td></td>
				</tr>
				<tr class="c-roboter">
					<th>Roboter</th>
					<td><input type="radio" name="building[roboter]" value="0"<?=($show_building['roboter']==0) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[roboter]" value="1"<?=($show_building['roboter']==1) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[roboter]" value="2"<?=($show_building['roboter']===2) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[roboter]" value="3"<?=($show_building['roboter']===3) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
				</tr>
				<tr class="c-schiffe">
					<th>Schiffe</th>
					<td><input type="radio" name="building[schiffe]" value="0"<?=($show_building['schiffe']==0) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[schiffe]" value="1"<?=($show_building['schiffe']==1) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[schiffe]" value="2"<?=($show_building['schiffe']===2) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[schiffe]" value="3"<?=($show_building['schiffe']===3) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
				</tr>
				<tr class="c-verteidigung">
					<th>Verteidigung</th>
					<td><input type="radio" name="building[verteidigung]" value="0"<?=($show_building['verteidigung']==0) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[verteidigung]" value="1"<?=($show_building['verteidigung']==1) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[verteidigung]" value="2"<?=($show_building['verteidigung']===2) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					<td><input type="radio" name="building[verteidigung]" value="3"<?=($show_building['verteidigung']===3) ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
	<div class="einstellungen-speichern-1"><input type="submit" title="[W]" value="Speichern" /></div>
<?php
	$save_tabindex = $tabindex++;

	if(!$me->userLocked())
	{
?>
	<fieldset class="urlaubsmodus">
		<legend>Urlaubsmodus</legend>
<?php
		if(!$me->umode())
		{
			if($me->permissionToUmode() || isset($_SESSION['admin_username']))
			{
?>
		<div><input type="submit" name="umode" value="Urlaubsmodus" tabindex="<?=$tabindex++?>" onclick="return confirm('Wollen Sie den Urlaubsmodus wirklich betreten?');" /></div>
		<p>Sie werden frühestens nach drei Tagen (<?=date('Y-m-d, H:i', $me->getUmodeReturnTime())?>, Serverzeit) aus dem Urlaubsmodus zurückkehren können.</p>
<?php
			}
			else
			{
?>
		<p>Sie können erst wieder ab dem <?=date('Y-m-d, H:i', $me->getUmodeReturnTime())?> (Serverzeit) in den Urlaubsmodus wechseln.</p>
<?php
			}
		}
		elseif($me->permissionToUmode() || isset($_SESSION['admin_username']))
		{
?>
		<div><input type="submit" name="umode" value="Urlaubsmodus verlassen" tabindex="<?=$tabindex++?>" onclick="return confirm('Wollen Sie den Urlaubsmodus wirklich verlassen?');" /></div>
<?php
		}
		else
		{
?>
		<p>Sie können den Urlaubsmodus spätestens am <?=date('Y-m-d, H:i', $me->getUmodeReturnTime())?> (Serverzeit) verlassen.</p>
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
			<dd class="c-email-adresse"><input type="text" name="email" id="email" value="<?=utf8_htmlentities($me->checkSetting('email'))?>" title="Ihre E-Mail-Adresse wird benötigt, wenn Sie Ihr Passwort vergessen haben. [Z]" tabindex="<?=$tabindex++?>" accesskey="z" /></dd>
		</dl>
	</fieldset>
	<fieldset class="passwort-aendern">
		<legend>Passwort ändern</legend>
		<dl>
			<dt class="c-altes-passwort"><label for="old-password">Altes Passw<kbd>o</kbd>rt</label></dt>
			<dd class="c-altes-passwort"><input type="password" name="old-password" id="old-password" tabindex="<?=$tabindex++?>" accesskey="o" /></dd>

			<dt class="c-neues-passwort"><label for="new-password">Neues Passwort</label></dt>
			<dd class="c-neues-passwort"><input type="password" name="new-password" id="new-password" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-neues-passwort-wiederholen"><label for="new-password2">Neues Passwort wiederholen</label></dt>
			<dd class="c-neues-passwort-wiederholen"><input type="password" name="new-password2" id="new-password2" tabindex="<?=$tabindex++?>" /></dd>
		</dl>
	</fieldset>
	<div class="einstellungen-speichern-2"><button type="submit" tabindex="<?=$save_tabindex?>" accesskey="w" title="[W]">Speichern</button></div>
</form>
<?php
	login_gui::html_foot();
?>