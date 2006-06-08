<?php
	require('scripts/include.php');

	$changed = false;

	$receive_settings = $me->checkSetting('receive');
	$show_building = $me->checkSetting('show_building');

	$messengers = get_messenger_info();
	$messenger_settings = $me->getNotificationType();
	$messenger_receive = $me->checkSetting('messenger_receive');

	if(isset($_POST['skin-choice']))
	{
		if($_POST['skin-choice'] == 'custom')
		{
			if(isset($_POST['skin']))
				$me->setSetting('skin', array('custom', $_POST['skin']));
		}
		elseif(strstr($_POST['skin-choice'], '/'))
			$me->setSetting('skin', explode('/', $_POST['skin-choice']));
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

		if(!isset($_POST['im-receive']) || !isset($_POST['im-receive']['messages']))
			$messenger_receive['messages'] = array(1=>false, 2=>false, 3=>false, 4=>false, 5=>false, 6=>false, 7=>false);
		else
		{
			$messenger_receive['messages'][1] = isset($_POST['im-receive']['messages'][1]);
			$messenger_receive['messages'][2] = isset($_POST['im-receive']['messages'][2]);
			$messenger_receive['messages'][3] = isset($_POST['im-receive']['messages'][3]);
			$messenger_receive['messages'][4] = isset($_POST['im-receive']['messages'][4]);
			$messenger_receive['messages'][5] = isset($_POST['im-receive']['messages'][5]);
			$messenger_receive['messages'][6] = isset($_POST['im-receive']['messages'][6]);
			$messenger_receive['messages'][7] = isset($_POST['im-receive']['messages'][7]);
		}
		$me->setSetting('messenger_receive', $messenger_receive);
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

	if(isset($_POST['im-receive']) && isset($_POST['im-receive']['building']))
	{
		$im_recalc = array('gebaeude' => false, 'forschung' => false, 'roboter' => false, 'schiffe' => false, 'verteidigung' => false);
		if(isset($_POST['im-receive']['building']['gebaeude']) && in_array($_POST['im-receive']['building']['gebaeude'], array(0,1)) && $_POST['im-receive']['building']['gebaeude'] != $messenger_receive['building']['gebaeude'])
		{
			$messenger_receive['building']['gebaeude'] = $_POST['im-receive']['building']['gebaeude'];
			$im_recalc['gebaeude'] = true;
		}
		if(isset($_POST['im-receive']['building']['forschung']) && in_array($_POST['im-receive']['building']['forschung'], array(0,1)) && $_POST['im-receive']['building']['forschung'] != $messenger_receive['building']['forschung'])
		{
			$messenger_receive['building']['forschung'] = $_POST['im-receive']['building']['forschung'];
			$im_recalc['forschung'] = true;
		}
		if(isset($_POST['im-receive']['building']['roboter']) && in_array($_POST['im-receive']['building']['roboter'], array(0,1,2,3)) && $_POST['im-receive']['building']['roboter'] != $messenger_receive['building']['roboter'])
		{
			$messenger_receive['building']['roboter'] = $_POST['im-receive']['building']['roboter'];
			$im_recalc['roboter'] = true;
		}
		if(isset($_POST['im-receive']['building']['schiffe']) && in_array($_POST['im-receive']['building']['schiffe'], array(0,1,2,3)) && $_POST['im-receive']['building']['schiffe'] != $messenger_receive['building']['schiffe'])
		{
			$messenger_receive['building']['schiffe'] = $_POST['im-receive']['building']['schiffe'];
			$im_recalc['schiffe'] = true;
		}
		if(isset($_POST['im-receive']['building']['verteidigung']) && in_array($_POST['im-receive']['building']['verteidigung'], array(0,1,2,3)) && $_POST['im-receive']['building']['verteidigung'] != $messenger_receive['building']['verteidigung'])
		{
			$messenger_receive['building']['verteidigung'] = $_POST['im-receive']['building']['verteidigung'];
			$im_recalc['verteidigung'] = true;
		}

		$me->setSetting('messenger_receive', $messenger_receive);
		$active_planet = $me->getActivePlanet();
		$planets = $me->getPlanetsList();
		foreach($im_recalc as $which=>$whether)
		{
			if($whether)
			{
				foreach($planets as $planet)
				{
					$me->setActivePlanet($planet);
					$me->refreshMessengerBuildingNotifications($which);
				}
			}
		}
		$me->setActivePlanet($active_planet);
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

	if((!$messenger_settings && isset($_POST['im-protocol']) && isset($messengers[$_POST['im-protocol']]) && isset($_POST['im-uin']) && trim($_POST['im-uin'])) || ($messenger_settings && ((isset($_POST['im-protocol']) && trim($_POST['im-protocol']) != $messenger_settings[1]) || (isset($_POST['im-uin']) && trim($_POST['im-uin']) != $messenger_settings[0]))))
	{
		if((isset($_POST['im-protocol']) && !isset($messengers[$_POST['im-protocol']])) || (isset($_POST['im-uin']) && !trim($_POST['im-uin'])))
		{
			# IM deaktivieren
			$me->disableNotification();
			$imfile = Classes::IMFile();
			$imfile->removeMessages($me->getName());
		}
		else
		{
			$new_uin = (isset($_POST['im-uin']) ? trim($_POST['im-uin']) : $messenger_settings[0]);
			$new_protocol = ((isset($_POST['im-protocol']) && isset($messengers[$_POST['im-protocol']])) ? trim($_POST['im-protocol']) : $messenger_settings[1]);

			if((!isset($messengers[$new_protocol]['blocked']) || !in_array(strtolower($new_uin), explode(',', strtolower(trim($messengers[$new_protocol]['blocked']))))) && $me->checkNewNotificationType($new_uin, $new_protocol))
			{
				$imfile = Classes::IMFile();
				$rand_id = $imfile->addCheck($new_uin, $new_protocol, $me->getName());
				$imfile->addMessage($new_uin, $new_protocol, $me->getName(), "Sie erhalten diese Nachricht, weil jemand in Stars Under Attack diesen Account zur Benachrichtigung eingetragen hat. Ignorieren Sie die Nachricht, wenn Sie die Eintragung nicht vornehmen möchten. Um die Einstellung zu bestätigen, antworten Sie bitte auf diese Nachricht folgenden Code: ".$rand_id);
			}
		}
	}

	if(isset($_POST['performance']) && in_array($_POST['performance'], array(1,2,3)))
		$me->setSetting('performance', $_POST['performance']);

	login_gui::html_head();

	$tabindex = 1;
	$show_im = isset($messengers['jabber']);
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
<form action="<?=htmlentities(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/login/einstellungen.php?'.urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="einstellungen-formular">
	<fieldset class="aussehen">
		<legend>Aussehen</legend>
		<dl>
			<dt class="c-skin"><label for="skin-choice" xml:lang="en">Ski<kbd>n</kbd></label></dt>
			<dd class="c-skin">
				<select name="skin-choice" id="skin-choice" accesskey="n" tabindex="<?=$tabindex++?>" onchange="recalc_skin();" onkeyup="recalc_skin();">
<?php
	$my_skin = $me->checkSetting('skin');
	foreach($skins as $skin=>$skin_info)
	{
		$skin_selected = ($my_skin && $skin == $my_skin[0]);
?>
					<optgroup label="<?=htmlspecialchars($skin_info[0])?>">
<?php
		foreach($skin_info[1] as $type=>$type_info)
		{
			$type_selected = ($skin_selected && $my_skin && $type == $my_skin[1]);
?>
						<option value="<?=htmlspecialchars($skin)?>/<?=htmlspecialchars($type)?>"<?=$type_selected ? ' selected="selected"' : ''?>><?=htmlspecialchars($type_info[0])?></option>
<?php
		}
?>
					</optgroup>
<?php
	}
	$custom_skin = ($my_skin && $my_skin[0] == 'custom');
?>
					<option value="custom"<?=$custom_skin ? ' selected="selected"' : ''?>>Benutzerdefiniert</option>
				</select>
				<input type="text" name="skin" id="skin" value="<?=htmlentities($my_skin[1])?>" tabindex="<?=$tabindex++?>" />
			</dd>

			<dt class="c-schrift"><label for="schrift-choice">Schrift</label></dt>
			<dd class="c-schrift">
				<select name="schrift" id="schrift-choice" tabindex="<?=$tabindex++?>">
					<option value="1"<?=$me->checkSetting('schrift') ? ' selected="selected"' : ''?>>Lieblingsschrift des Admins</option>
					<option value="0"<?=!$me->checkSetting('schrift') ? ' selected="selected"' : ''?>>Ihre Lieblingsschrift</option>
				</select>
			</dd>

			<dt class="c-werbung-ausblenden"><label for="noads">Werbung ausblenden</label></dt>
			<dd class="c-werbung-ausblenden"><input type="checkbox" name="noads" id="noads"<?=$me->checkSetting('noads') ? ' checked="checked"' : ''?> title="Wenn Sie die Werbung eingeblendet lassen, helfen Sie, die Finanzen des Spiels zu decken." tabindex="<?=$tabindex++?>" /></dd>
		</dl>

		<script type="text/javascript">
			function recalc_skin()
			{
				var skin = document.getElementById('skin-choice').value;
				if(skin == 'custom')
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

	<fieldset class="verhalten">
		<legend>Verhalten</legend>
		<dl>
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

			<dt class="c-externe-navigationslinks"><label for="show-extern">Externe Navigationslinks</label></dt>
			<dd class="c-externe-navigationslinks"><input type="checkbox" name="show_extern" id="show-extern"<?=$me->checkSetting('show_extern') ? ' checked="checked"' : ''?> title="Wenn diese Option aktiviert ist, werden in der Navigation Links auf spielexterne Seiten wie das Board angezeigt." tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-javascript-performance"><label for="performance">JavaScript-Performance</label></dt>
			<dd class="c-javascript-performance">
				<select name="performance" tabindex="<?=$tabindex++?>">
					<option value="1"<?=($me->checkSetting('performance')==1) ? ' selected="selected"' : ''?>>Ungenau, wenig CPU-Last</option>
					<option value="2"<?=($me->checkSetting('performance')==2) ? ' selected="selected"' : ''?>>Praktisch, mittlere CPU-Last</option>
					<option value="3"<?=($me->checkSetting('performance')==3) ? ' selected="selected"' : ''?>>Komfortabel und präzise, hohe CPU-Last</option>
				</select>
			</dd>
		</dl>
	</fieldset>

	<fieldset class="benachrichtigung">
		<legend>Benachrichtigung</legend>
<?php
	if($show_im)
	{
?>
		<p>Nach Änderung des Instant-Messaging-Accounts wird zunächst eine Bestätigungsnachricht versandt.</p>
<?php
	}
?>
		<dl>
			<dt class="c-nachrichteninformierung"><label for="notify">Nachrichten auf jeder Seite</label></dt>
			<dd class="c-nachrichteninformierung"><input type="checkbox" name="notify" id="notify"<?=$me->checkSetting('notify') ? ' checked="checked"' : ''?> title="Wenn diese Option aktiviert ist, wird nicht nur in der Übersicht angezeigt, dass Sie eine neue Nachricht erhalten haben, sondern auf allen Seiten." tabindex="<?=$tabindex++?>" /></dd>
<?php
	if($show_im)
	{
?>

			<dt class="c-im-account"><label for="i-im-protocol"><abbr title="Instant-Messaging">IM</abbr>-Account</label></dt>
			<dd class="c-im-account">
				<select name="im-protocol" id="i-im-protocol" onchange="document.getElementById('i-im-uin').disabled = !this.value;" onkeyup="this.onchange();" tabindex="<?=$tabindex++?>">
					<option value="">Deaktiviert</option>
<?php
		foreach($messengers as $protocol=>$minfo)
		{
			$name = (isset($minfo['name']) ? $minfo['name'] : $protocol);
?>
					<option value="<?=htmlspecialchars($protocol)?>"<?=($messenger_settings && $messenger_settings[1] == $protocol) ? ' selected="selected"' : ''?>><?=htmlspecialchars($name)?></option>
<?php
		}
?>
				</select>
				<input type="text" name="im-uin" id="i-im-uin" title="UIN"<?=$messenger_settings ? ' value="'.htmlspecialchars($messenger_settings[0]).'"' : ''?> tabindex="<?=$tabindex++?>" />
			</dd>
<?php
	}
?>
		</dl>
<?php
	if($show_im)
	{
?>
		<script type="text/javascript">
			document.getElementById('i-im-uin').disabled = !document.getElementById('i-im-protocol').value;
		</script>
<?php
	}
?>
		<fieldset class="benachrichtigungen-nachrichten">
			<legend>Benachrichtigung bei Nachrichten</legend>
			<table>
				<thead>
					<tr>
						<th class="c-nachrichtentyp">Nachrichtentyp</th>
						<th class="c-ankunft">Ankunft</th>
						<th class="c-rueckkehr">Rückkehr</th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><abbr title="Instant-Messaging">IM</abbr>-Benachrichtigung</th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-kaempfe">
						<th class="c-nachrichtentyp">Kämpfe</th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[1][1]"<?=$receive_settings[1][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][1]"<?=$messenger_receive['messages'][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	}
?>
					</tr>
					<tr class="r-spionage">
						<th class="c-nachrichtentyp">Spionage</th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[2][1]"<?=$receive_settings[2][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][2]"<?=$messenger_receive['messages'][2] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	}
?>
					</tr>
					<tr class="r-transport">
						<th class="c-nachrichtentyp">Transport</th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[3][0]"<?=$receive_settings[3][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[3][1]"<?=$receive_settings[3][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][3]"<?=$messenger_receive['messages'][3] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	}
?>
					</tr>
					<tr class="r-sammeln">
						<th class="c-nachrichtentyp">Sammeln</th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[4][1]"<?=$receive_settings[4][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][4]"<?=$messenger_receive['messages'][4] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	}
?>
					</tr>
					<tr class="r-besiedelung">
						<th class="c-nachrichtentyp">Besiedelung</th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[5][0]"<?=$receive_settings[5][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[5][1]"<?=$receive_settings[5][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][5]"<?=$messenger_receive['messages'][5] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
<?php
	}
?>
					</tr>
<?php
	if($show_im)
	{
?>
					<tr class="r-benutzernachrichten">
						<th class="c-nachrichtentyp">Benutzernachrichten</th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][6]"<?=$messenger_receive['messages'][6] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
					<tr class="r-verbuendete">
						<th class="c-nachrichtentyp">Verbündete</th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][7]"<?=$messenger_receive['messages'][7] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</fieldset>
		<fieldset class="benachrichtigungen-fertigstellung">
			<legend>Benachrichtigung bei Fertigstellung</legend>
			<table>
				<thead>
					<tr>
						<th class="c-gegenstandsart">Gegenstandsart</th>
						<th class="c-uebersicht"><abbr title="Verbleibende">Verbl.</abbr> Bauzeit in der Übersicht</th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><abbr title="Instant-Messaging">IM</abbr>-Benachichtigung</th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-gebaeude">
						<th class="c-gegenstandsart">Gebäude</th>
						<td class="c-uebersicht">
							<select name="building[gebaeude]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['gebaeude']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($show_building['gebaeude']==1) ? ' selected="selected"' : ''?>>Jedes einzelne</option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][gebaeude]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['gebaeude']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($messenger_receive['building']['gebaeude']==1) ? ' selected="selected"' : ''?>>Jedes einzelne</option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-forschung">
						<th class="c-gegenstandsart">Forschung</th>
						<td class="c-uebersicht">
							<select name="building[forschung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['forschung']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($show_building['forschung']==1) ? ' selected="selected"' : ''?>>Jede einzelne</option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][forschung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['forschung']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($messenger_receive['building']['forschung']==1) ? ' selected="selected"' : ''?>>Jede einzelne</option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-roboter">
						<th class="c-gegenstandsart">Roboter</th>
						<td class="c-uebersicht">
							<select name="building[roboter]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['roboter']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($show_building['roboter']==1) ? ' selected="selected"' : ''?>>Jeder einzelne</option>
								<option value="2"<?=($show_building['roboter']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($show_building['roboter']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][roboter]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['roboter']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($messenger_receive['building']['roboter']==1) ? ' selected="selected"' : ''?>>Jeder einzelne</option>
								<option value="2"<?=($messenger_receive['building']['roboter']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($messenger_receive['building']['roboter']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-schiffe">
						<th class="c-gegenstandsart">Schiffe</th>
						<td class="c-uebersicht">
							<select name="building[schiffe]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['schiffe']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($show_building['schiffe']==1) ? ' selected="selected"' : ''?>>Jedes einzelne</option>
								<option value="2"<?=($show_building['schiffe']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($show_building['schiffe']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][schiffe]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['schiffe']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($messenger_receive['building']['schiffe']==1) ? ' selected="selected"' : ''?>>Jedes einzelne</option>
								<option value="2"<?=($messenger_receive['building']['schiffe']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($messenger_receive['building']['schiffe']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-verteidigung">
						<th class="c-gegenstandsart">Verteidigung</th>
						<td class="c-uebersicht">
							<select name="building[verteidigung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['verteidigung']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($show_building['verteidigung']==1) ? ' selected="selected"' : ''?>>Jede einzelne</option>
								<option value="2"<?=($show_building['verteidigung']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($show_building['verteidigung']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][verteidigung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['verteidigung']==0) ? ' selected="selected"' : ''?>>Ausgeschaltet</option>
								<option value="1"<?=($messenger_receive['building']['verteidigung']==1) ? ' selected="selected"' : ''?>>Jede einzelne</option>
								<option value="2"<?=($messenger_receive['building']['verteidigung']==2) ? ' selected="selected"' : ''?>>Alle eines Typs</option>
								<option value="3"<?=($messenger_receive['building']['verteidigung']==3) ? ' selected="selected"' : ''?>>Alle</option>
							</select>
						</td>
<?php
	}
?>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</fieldset>

	<div class="einstellungen-speichern-1"><input type="submit" title="[W]" value="Speichern" tabindex="<?=$tabindex++?>" /></div>
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
	<fieldset class="benutzeraccount">
		<legend>Benutzeraccount</legend>
		<dl>
			<dt class="c-ip-schutz"><label for="ipcheck">IP-Schutz</label></dt>
			<dd class="c-ip-schutz"><input type="checkbox" name="ipcheck" id="ipcheck"<?=$me->checkSetting('ipcheck') ? ' checked="checked"' : ''?> title="Wenn diese Option deaktiviert ist, kann Ihre Session von mehreren IP-Adressen gleichzeitig genutzt werden. (Unsicher!)" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-email-adresse"><label for="email">E-Mail-Adresse</label></dt>
			<dd class="c-email-adresse"><input type="text" name="email" id="email" value="<?=utf8_htmlentities($me->checkSetting('email'))?>" title="Ihre E-Mail-Adresse wird benötigt, wenn Sie Ihr Passwort vergessen haben. [Z]" tabindex="<?=$tabindex++?>" accesskey="z" /></dd>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung">Ben<kbd>u</kbd>tzerbeschreibung</label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung" cols="50" rows="10" accesskey="u" tabindex="<?=$tabindex++?>"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', utf8_htmlentities($me->getUserDescription(false)))?></textarea></dd>
		</dl>
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
	</fieldset>
	<div class="einstellungen-speichern-2"><input type="hidden" name="change-checkboxes" value="1" /><button type="submit" tabindex="<?=$save_tabindex?>" accesskey="w" title="[W]">Speichern</button></div>
</form>
<?php
	login_gui::html_foot();
?>
