<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/
	require('scripts/include.php');

	$changed = false;

	$receive_settings = $me->checkSetting('receive');
	$show_building = $me->checkSetting('show_building');

	$messengers = get_messenger_info();
	$messenger_settings = $me->getNotificationType();
	$messenger_receive = $me->checkSetting('messenger_receive');

	$error = array();

	$my_skin = $me->checkSetting("skin");
	if(isset($_POST["skin-choice"]))
		$my_skin[0] = $_POST["skin-choice"];
	if(isset($_POST["skin"]))
		$my_skin[1] = $_POST["skin"];
	if($my_skin[0] == "custom")
	{
		if(isset($_POST["skin-parameters"]))
			$my_skin[2] = $_POST["skin-parameters"];
	}
	elseif(isset($_POST["skin-setting"]) && is_array($_POST["skin-setting"]))
		$my_skin[2] = $_POST["skin-setting"];
	if($my_skin != $me->checkSetting("skin"))
		$me->setSetting("skin", $my_skin);

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
		$me->setSetting('fastbuild_full', isset($_POST['fastbuild_full']));
		$me->setSetting("gpg_im", isset($_POST["gpg_im"]));
		$me->setSetting("extended_buildings", isset($_POST["extended_buildings"]));

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
		$me->setEMailAddress($_POST['email']);

	if(isset($_POST["remove_fingerprint"]))
	{
		$me->setSetting("fingerprint", false);
		$imfile = Classes::IMFile();
		$imfile->changeFingerprint($me->getName(), "");
	}

	if(isset($_FILES["gpg"]) && !$_FILES["gpg"]["error"] && ($gpg = gpg_init()))
	{
		$info = $gpg->import(file_get_contents($_FILES["gpg"]["tmp_name"]));
		if(isset($info["fingerprint"]))
			$me->setSetting("fingerprint", $info["fingerprint"]);
		else
			$error[] = _("Das war kein gültiger GPG-Schlüssel.");
		unlink($_FILES["gpg"]["tmp_name"]);
		$imfile = Classes::IMFile();
		$imfile->changeFingerprint($me->getName(), $info["fingerprint"]);
	}

	if(isset($_POST['old-password']) && isset($_POST['new-password']) && isset($_POST['new-password2']) && ($_POST['old-password'] != $_POST['new-password'] || $_POST['new-password'] != $_POST['new-password2']))
	{
		# Passwort aendern
		if(!$me->checkPassword($_POST['old-password']))
			$error[] = _('Das alte Passwort stimmt nicht.');
		elseif($_POST['new-password'] != $_POST['new-password2'])
			$error[] = _('Die beiden neuen Passworte stimmen nicht überein.');
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

	if(isset($_POST['performance']) && in_array($_POST['performance'], array(0,1,2,3)))
		$me->setSetting('performance', $_POST['performance']);

	login_gui::html_head();

	$tabindex = 1;
	$show_im = isset($messengers['jabber']);
?>
<h2><?=h(_("Einstellungen"))?></h2>
<?php
	foreach($error as $m)
	{
?>
<p class="error"><?=htmlspecialchars($m)?></p>
<?php
	}
?>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].h_root.'/login/einstellungen.php?'.urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="einstellungen-formular" enctype="multipart/form-data">
	<fieldset class="aussehen">
		<legend><?=h(_("Aussehen"))?></legend>
		<dl>
			<dt class="c-skin"><label for="skin-choice"><?=h(_("Skin&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-skin">
				<select name="skin-choice" id="skin-choice"<?=accesskey_attr(_("Skin&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" onchange="recalc_skin();" onkeyup="recalc_skin();">
<?php
	foreach($skins as $skin=>$skin_info)
	{
?>
					<option value="<?=htmlspecialchars($skin)?>"<?=$my_skin[0] == $skin ? " selected=\"selected\"" : ""?>><?=htmlspecialchars($skin_info[0])?></option>
<?php
	}
?>
					<option value="custom"<?=$my_skin[0] == "custom" ? ' selected="selected"' : ''?>><?=h(_("Benutzerdefiniert"))?></option>
				</select>
				<input type="text" name="skin" id="skin" value="<?=htmlspecialchars($my_skin[1])?>" tabindex="<?=$tabindex++?>" title="<?=h(_("Skin-Pfad&[login/einstellungen.php|1]"), false)?>"<?=accesskey_attr(_("Skin-Pfad&[login/einstellungen.php|1]"))?> onkeyup="update_skin_path_wait();" onchange="onkeyup();" />
				<input type="text" name="skin-parameters" id="i-skin-parameters" value="<?=isset($my_skin[2]) && !is_array($my_skin[2]) ? htmlspecialchars($my_skin[2]) : ""?>" title="<?=h(_("Skin-Parameter&[login/einstellungen.php|1]"), false)?>"<?=accesskey_attr(_("Skin-Parameter&[login/einstellungen.php|1]"))?> onkeyup="update_options_wait();" onchange="onkeyup();" />
			</dd>

			<dt class="c-werbung-ausblenden"><label for="noads"><?=h(_("Werbung ausblenden&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-werbung-ausblenden"><input type="checkbox" name="noads" id="noads"<?=accesskey_attr(_("Werbung ausblenden&[login/einstellungen.php|1]"))?><?=$me->checkSetting('noads') ? ' checked="checked"' : ''?> title="<?=h(_("Wenn Sie die Werbung eingeblendet lassen, helfen Sie, die Finanzen des Spiels zu decken."))?>" tabindex="<?=$tabindex++?>" /></dd>
		</dl>

		<fieldset id="skin-options-fieldset">
			<legend><?=h(_("Skin-spezifische Einstellungen"))?></legend>
			<dl id="skin-options">
<?php
	if($my_skin && isset($skins[$my_skin[0]]))
	{
		$i = 0;
		foreach($skins[$my_skin[0]][1] as $option=>$settings)
		{
?>
			<dt class="c-skin-<?=htmlspecialchars($i)?>"><label for="i-skin-<?=htmlspecialchars($i)?>"><?=htmlspecialchars($option)?></label></dt>
			<dd class="c-skin-<?=htmlspecialchars($i)?>"><select id="i-skin-<?=htmlspecialchars($i)?>" name="skin-setting[<?=htmlspecialchars($i)?>]" onchange="update_options();" onkeyup="onchange();">
<?php
			foreach($settings as $j=>$setting)
			{
?>
				<option value="<?=htmlspecialchars($j)?>"<?php if(isset($my_skin[2]) && is_array($my_skin[2]) && isset($my_skin[2][$i]) && $my_skin[2][$i] == $j){?> selected="selected"<?php }?>><?=htmlspecialchars($setting)?></option>
<?php
			}
?>
			</select></dd>
<?php
			$i++;
		}
	}
?>
			</dl>
		</fieldset>
<?php

	$skin_options = array();
	foreach($skins as $skin=>$info)
	{
		$skin_options1 = array();
		foreach($info[1] as $setting_name=>$options)
		{
			$skin_options2 = array();
			foreach($options as $option)
				$skin_options2[] = "'".jsentities($option)."'";
			$skin_options1[] = "[ '".jsentities($setting_name)."', [ ".implode(", ", $skin_options2)." ] ]";
		}
		$skin_options[] = "'".jsentities($skin)."' : [ ".implode(", ", $skin_options1)." ]";
	}
?>

		<script type="text/javascript">
			var skin_options = {
				<?=implode(",\n\t\t\t\t", $skin_options)."\n"?>
			};
			var skin_settings = {<?php if($my_skin[0] != "custom" && isset($my_skin[2]) && is_array($my_skin[2])){?> '<?=jsentities($my_skin[0])?>' : [ <?=implode(", ", $my_skin[2])?> ]<?php }?> };
			var last_skin = document.getElementById("skin-choice").value;
			var options_el = document.getElementById("skin-options");
			var update_timeout1,update_timeout2;

			function update_skin_path_wait()
			{
				if(update_timeout1)
					clearTimeout(update_timeout1);
				update_timeout1 = setTimeout("update_skin_path()", 500);
			}

			function update_options_wait()
			{
				if(update_timeout2)
					clearTimeout(update_timeout2);
				update_timeout2 = setTimeout("update_options()", 500);
			}

			function update_skin_path()
			{
				var l = document.getElementsByTagName("link");
				for(var i=0; i<l.length; i++)
				{
					if(l[i].rel != "stylesheet") continue;
					if(document.getElementById("skin-choice").value == "custom")
						l[i].href = document.getElementById("skin").value;
					else
						l[i].href = '<?=jsentities(h_root)?>/login/style/'+document.getElementById("skin-choice").value+'/style.css';
					break;
				}
			}

			function update_options()
			{
				var j=1;
				var params = [ ];
				if(document.getElementById("skin-choice").value == "custom")
				{
					params = document.getElementById("i-skin-parameters").value.split(/\s+/);
					for(var i=0; i<params.length; i++)
						params[i] = "skin-"+params[i];
				}
				else
				{
					var select_el;
					for(var i=0; select_el=document.getElementById("i-skin-"+i); i++)
						params[i] = "skin-"+i+"-"+select_el.value;
				}
				params = params.join(" ");

				var el = document.getElementById("body-root");
				while(el)
				{
					el.className = el.className.replace(/\s*skin-[^\s]+\s*/g, "")+" "+params;
					el = document.getElementById("content-"+(j++));
				}
			}

			function recalc_skin()
			{
				if(last_skin)
				{
					var op;
					skin_settings[last_skin] = [ ];
					for(var i=0; op = document.getElementById("i-skin-"+i); i++)
						skin_settings[last_skin][i] = document.getElementById("i-skin-"+i).value;
				}

				while(options_el.firstChild) options_el.removeChild(options_el.firstChild);

				last_skin = document.getElementById('skin-choice').value;

				if(last_skin == 'custom')
				{
					document.getElementById('skin').removeAttribute('readonly');
					document.getElementById('i-skin-parameters').removeAttribute('readonly');
				}
				else
				{
					document.getElementById('skin').setAttribute('readonly', 'readonly');
					document.getElementById('i-skin-parameters').setAttribute('readonly', 'readonly');

					if(skin_options[last_skin])
					{
						for(var i=0; i<skin_options[last_skin].length; i++)
						{
							var dt = document.createElement("dt");
							dt.className = "c-skin-"+i;
							var label = document.createElement("label");
							label.htmlFor = "i-skin-"+i;
							label.appendChild(document.createTextNode(skin_options[last_skin][i][0]));
							dt.appendChild(label);

							var dd = document.createElement("dd");
							dd.className = "c-skin-"+i;
							var select_el = document.createElement("select");
							select_el.id = "i-skin-"+i;
							select_el.name = "skin-setting["+i+"]";
							select_el.onchange = function(){update_options();};
							select_el.onkeyup = select_el.onchange;
							for(var j=0; j<skin_options[last_skin][i][1].length; j++)
								select_el.options[select_el.length] = new Option(skin_options[last_skin][i][1][j], j, false, (skin_settings[last_skin] && skin_settings[last_skin][i] == j));
							dd.appendChild(select_el);
							options_el.appendChild(dt);
							options_el.appendChild(dd);
						}
					}
				}

				update_options();
				update_skin_path();
			}
			recalc_skin();
		</script>
	</fieldset>

	<fieldset class="verhalten">
		<legend><?=h(_("Verhalten"))?></legend>
		<dl>
			<dt class="c-spionagesonden"><label for="spionagesonden"><?=h(_("Spionagesonden&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-spionagesonden"><input type="text" name="spionagesonden" id="spionagesonden"<?=accesskey_attr(_("Spionagesonden&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->checkSetting('sonden'))?>" title="<?=h(_("Anzahl Spionagesonden, die bei der Spionage eines fremden Planeten aus der Karte geschickt werden sollen"))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-schnellbau"><label for="fastbuild"><?=h(_("Auto-Schnellbau&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-auto-schnellbau"><input type="checkbox" name="fastbuild" id="fastbuild"<?=accesskey_attr(_("Auto-Schnellbau&[login/einstellungen.php|1]"))?><?=$me->checkSetting('fastbuild') ? ' checked="checked"' : ''?> title="<?=h(_("Wird ein Gebäude in Auftrag gegeben, wird automatisch zum nächsten unbeschäftigten Planeten gewechselt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-schnellbau-auf-ausgebaute"><label for="i-full"><?=h(_("Schnellbau auf ausgebaute&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-schnellbau-auf-ausgebaute"><input type="checkbox" name="fastbuild_full" id="i-full"<?=accesskey_attr(_("Schnellbau auf ausgebaute&[login/einstellungen.php|1]"))?><?=$me->checkSetting('fastbuild_full') ? ' checked="checked"' : ''?> title="<?=h(_("Durch diese Option werden auch ausgebaute Planeten bei der Schnellbau-Funktion berücksichtigt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-schnell-shortcuts"><label for="shortcuts"><?=h(_("Schnell-Shortcuts&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-schnell-shortcuts"><input type="checkbox" name="shortcuts" id="shortcuts"<?=accesskey_attr(_("Schnell-Shortcuts&[login/einstellungen.php|1]"))?><?=$me->checkSetting('shortcuts') ? ' checked="checked"' : ''?> title="<?=h(_("Mit dieser Funktion brauchen Sie zum Ausführen der Shortcuts keine weitere Taste zu drücken."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-javascript-tooltips"><label for="tooltips"><?=h(_("Javascript-Tooltips&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-javascript-tooltips"><input type="checkbox" name="tooltips" id="tooltips"<?=accesskey_attr(_("Javascript-Tooltips&[login/einstellungen.php|1]"))?><?=$me->checkSetting('tooltips') ? ' checked="checked"' : ''?> title="<?=h(_("Nicht auf langsamen Computern verwenden! Ist dieser Punkt aktiviert, werden die normalen Tooltips durch hübsche JavaScript-Tooltips ersetzt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-refresh"><label for="autorefresh"><?=h(_("Auto-Refresh&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-auto-refresh"><input type="text" name="autorefresh" id="autorefresh"<?=accesskey_attr(_("Auto-Refresh&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->checkSetting('ress_refresh'))?>" title="<?=h(_("Wird hier eine Zahl größer als 0 eingetragen, wird in deren Sekundenabstand die Rohstoffanzeige oben automatisch aktualisiert. (Hinweis: Diese Funktion erzeugt keinen zusätzlichen Traffic.)"))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-externe-navigationslinks"><label for="show-extern"><?=h(_("Externe Navigationslinks&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-externe-navigationslinks"><input type="checkbox" name="show_extern" id="show-extern"<?=accesskey_attr(_("Externe Navigationslinks&[login/einstellungen.php|1]"))?><?=$me->checkSetting('show_extern') ? ' checked="checked"' : ''?> title="<?=h(_("Wenn diese Option aktiviert ist, werden in der Navigation Links auf spielexterne Seiten wie das Board angezeigt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-javascript-performance"><label for="performance"><?=h(_("JavaScript-Performance&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-javascript-performance">
				<select name="performance"<?=accesskey_attr(_("JavaScript-Performance&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
					<option value="0"<?=($me->checkSetting('performance')==0) ? ' selected="selected"' : ''?>><?=h(_("Keine Bildschirmänderungen"))?></option>
					<option value="1"<?=($me->checkSetting('performance')==1) ? ' selected="selected"' : ''?>><?=h(_("Ungenau, wenig CPU-Last"))?></option>
					<option value="2"<?=($me->checkSetting('performance')==2) ? ' selected="selected"' : ''?>><?=h(_("Praktisch, mittlere CPU-Last"))?></option>
					<option value="3"<?=($me->checkSetting('performance')==3) ? ' selected="selected"' : ''?>><?=h(_("Komfortabel und präzise, hohe CPU-Last"))?></option>
				</select>
			</dd>

			<dt class="c-erweiterte-gebaeudeansicht"><label for="i-erweiterte-gebaeudeansicht"><?=h(_("Erweiterte Gebäudeansicht&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-erweiterte-gebaeudeansicht"><input type="checkbox" name="extended_buildings" id="i-erweiterte-gebaeudeansicht"<?=accesskey_attr(_("Erweiterte Gebäudeansicht&[login/einstellungen.php|1]"))?><?=$me->checkSetting("extended_buildings") ? " checked=\"checked\"" : ""?> title="<?=h(_("In der Gebäudeansicht wird zusätzlich der Produktionsunterschied zur nächsten Stufe angezeigt."))?>" tabindex="<?=$tabindex++?>" /></dd>
		</dl>
	</fieldset>

	<fieldset class="benachrichtigung">
		<legend><?=h(_("Benachrichtigung"))?></legend>
<?php
	if($show_im)
	{
?>
		<p><?=h(_("Nach Änderung des Instant-Messaging-Accounts wird zunächst eine Bestätigungsnachricht versandt."))?></p>
<?php
	}
?>
		<dl>
			<dt class="c-nachrichteninformierung"><label for="notify"><?=h(_("Nachrichten auf jeder Seite&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-nachrichteninformierung"><input type="checkbox" name="notify" id="notify"<?=accesskey_attr(_("Nachrichten auf jeder Seite&[login/einstellungen.php|1]"))?><?=$me->checkSetting('notify') ? ' checked="checked"' : ''?> title="<?=h(_("Wenn diese Option aktiviert ist, wird nicht nur in der Übersicht angezeigt, dass Sie eine neue Nachricht erhalten haben, sondern auf allen Seiten."))?>" tabindex="<?=$tabindex++?>" /></dd>
<?php
	if($show_im)
	{
?>

			<dt class="c-im-account"><label for="i-im-protocol"><?=h(_("IM-Account&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-im-account">
				<select name="im-protocol" id="i-im-protocol"<?=accesskey_attr(_("IM-Account&[login/einstellungen.php|1]"))?> onchange="document.getElementById('i-im-uin').disabled = !this.value;" onkeyup="this.onchange();" tabindex="<?=$tabindex++?>">
					<option value=""><?=h(_("Deaktiviert"))?></option>
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
				<input type="text" name="im-uin" id="i-im-uin" title="<?=h(_("UIN"))?>"<?=$messenger_settings ? ' value="'.htmlspecialchars($messenger_settings[0]).'"' : ''?> tabindex="<?=$tabindex++?>" />
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
			<legend><?=h(_("Benachrichtigung bei Nachrichten"))?></legend>
			<table>
				<thead>
					<tr>
						<th class="c-nachrichtentyp"><?=h(_("Nachrichtentyp"))?></th>
						<th class="c-ankunft"><?=h(_("Ankunft"))?></th>
						<th class="c-rueckkehr"><?=h(_("Rückkehr"))?></th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><?=h(_("IM-Benachrichtigung"))?></th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-kaempfe">
						<th class="c-nachrichtentyp"><?=h(_("[message_1]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[1][1]"<?=accesskey_attr(_("[message_1]&[login/einstellungen.php|1]"))?><?=$receive_settings[1][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=h(_("[message_2]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[2][1]"<?=accesskey_attr(_("[message_2]&[login/einstellungen.php|1]"))?><?=$receive_settings[2][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=h(_("[message_3]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[3][0]"<?=accesskey_attr(_("[message_3]&[login/einstellungen.php|1]"))?><?=$receive_settings[3][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=h(_("[message_4]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[4][1]"<?=accesskey_attr(_("[message_4]&[login/einstellungen.php|1]"))?><?=$receive_settings[4][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=h(_("[message_5]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[5][0]"<?=accesskey_attr(_("[message_5]&[login/einstellungen.php|1]"))?><?=$receive_settings[5][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=h(_("[message_6]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][6]"<?=accesskey_attr(_("[message_6]&[login/einstellungen.php|1]"))?><?=$messenger_receive['messages'][6] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
					<tr class="r-verbuendete">
						<th class="c-nachrichtentyp"><?=h(_("[message_7]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][7]"<?=accesskey_attr(_("[message_7]&[login/einstellungen.php|1]"))?><?=$messenger_receive['messages'][7] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</fieldset>
		<fieldset class="benachrichtigungen-fertigstellung">
			<legend><?=h(_("Benachrichtigung bei Fertigstellung"))?></legend>
			<table>
				<thead>
					<tr>
						<th class="c-gegenstandsart"><?=h(_("Gegenstandsart"))?></th>
						<th class="c-uebersicht"><?=h(_("Verbl. Bauzeit in der Übersicht"))?></th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><?=h(_("IM-Benachichtigung"))?></th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-gebaeude">
						<th class="c-gegenstandsart"><?=h(_("Gebäude&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[gebaeude]"<?=accesskey_attr(_("Gebäude&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['gebaeude']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['gebaeude']==1) ? ' selected="selected"' : ''?>><?=h(_("Jedes einzelne [Gebäude]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][gebaeude]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['gebaeude']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['gebaeude']==1) ? ' selected="selected"' : ''?>><?=h(_("Jedes einzelne [Gebäude]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-forschung">
						<th class="c-gegenstandsart"><?=h(_("Forschung&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[forschung]"<?=accesskey_attr(_("Forschung&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['forschung']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['forschung']==1) ? ' selected="selected"' : ''?>><?=h(_("Jede einzelne [Forschung]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][forschung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['forschung']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['forschung']==1) ? ' selected="selected"' : ''?>><?=h(_("Jede einzelne [Forschung]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-roboter">
						<th class="c-gegenstandsart"><?=h(_("Roboter&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[roboter]"<?=accesskey_attr(_("Roboter&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['roboter']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['roboter']==1) ? ' selected="selected"' : ''?>><?=h(_("Jeder einzelne [Roboter]"))?></option>
								<option value="2"<?=($show_building['roboter']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Roboter] eines Typs"))?></option>
								<option value="3"<?=($show_building['roboter']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Roboter]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][roboter]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['roboter']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['roboter']==1) ? ' selected="selected"' : ''?>><?=h(_("Jeder einzelne [Roboter]"))?></option>
								<option value="2"<?=($messenger_receive['building']['roboter']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Roboter] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['roboter']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Roboter]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-schiffe">
						<th class="c-gegenstandsart"><?=h(_("Schiffe&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[schiffe]"<?=accesskey_attr(_("Schiffe&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['schiffe']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['schiffe']==1) ? ' selected="selected"' : ''?>><?=h(_("Jedes einzelne [Schiff]"))?></option>
								<option value="2"<?=($show_building['schiffe']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Schiffe] eines Typs"))?></option>
								<option value="3"<?=($show_building['schiffe']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Schiffe]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][schiffe]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['schiffe']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['schiffe']==1) ? ' selected="selected"' : ''?>><?=h(_("Jedes einzelne [Schiff]"))?></option>
								<option value="2"<?=($messenger_receive['building']['schiffe']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Schiffe] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['schiffe']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Schiffe]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-verteidigung">
						<th class="c-gegenstandsart"><?=h(_("Verteidigung&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[verteidigung]"<?=accesskey_attr(_("Verteidigung&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['verteidigung']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['verteidigung']==1) ? ' selected="selected"' : ''?>><?=h(_("Jede einzelne [Verteidigungsanlage]"))?></option>
								<option value="2"<?=($show_building['verteidigung']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Verteidigungsanlagen] eines Typs"))?></option>
								<option value="3"<?=($show_building['verteidigung']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Verteidigungsanlagen]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][verteidigung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['verteidigung']==0) ? ' selected="selected"' : ''?>><?=h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['verteidigung']==1) ? ' selected="selected"' : ''?>><?=h(_("Jede einzelne [Verteidigungsanlage]"))?></option>
								<option value="2"<?=($messenger_receive['building']['verteidigung']==2) ? ' selected="selected"' : ''?>><?=h(_("Alle [Verteidigungsanlagen] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['verteidigung']==3) ? ' selected="selected"' : ''?>><?=h(_("Alle [Verteidigungsanlagen]"))?></option>
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

	<div class="einstellungen-speichern-1"><button tabindex="<?=$tabindex++?>"<?=accesskey_attr(_("Speicher&n[login/einstellungen.php|1]"))?>><?=h(_("Speicher&n[login/einstellungen.php|1]"))?></button></div>
<?php
	$save_tabindex = $tabindex++;

	if(!$me->userLocked())
	{
?>
	<fieldset class="urlaubsmodus">
		<legend><?=h(_("Urlaubsmodus"))?></legend>
<?php
		if(!$me->umode())
		{
			if($me->permissionToUmode() || isset($_SESSION['admin_username']))
			{
?>
		<div><input type="submit" name="umode" value="<?=h(_("Urlaubsmodus&[login/einstellungen.php|1]"), false)?>"<?=accesskey_attr(_("Urlaubsmodus&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" onclick="return confirm('<?=jsentities(_("Wollen Sie den Urlaubsmodus wirklich betreten?"))?>');" /></div>
		<p><?=h(sprintf(_("Sie werden frühestens nach drei Tagen (%s) aus dem Urlaubsmodus zurückkehren können."), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i'), $me->getUmodeReturnTime()))))?></p>
<?php
			}
			else
			{
?>
		<p><?=h(sprintf(_("Sie können erst wieder ab dem %s in den Urlaubsmodus wechseln."), sprintf(_("%s (Serverzeit)"), date('Y-m-d, H:i', $me->getUmodeReturnTime()))))?></p>
<?php
			}
		}
		elseif($me->permissionToUmode() || isset($_SESSION['admin_username']))
		{
?>
		<div><input type="submit" name="umode" value="<?=h(_("Urlaubsmodus verlassen"))?>" tabindex="<?=$tabindex++?>" onclick="return confirm('<?=jsentities(_("Wollen Sie den Urlaubsmodus wirklich verlassen?"))?>');" /></div>
<?php
		}
		else
		{
?>
		<p><?=h(sprintf(_("Sie können den Urlaubsmodus spätestens am %s verlassen."), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i'), $me->getUmodeReturnTime()))))?></p>
<?php
		}
?>
	</fieldset>
<?php
	}
?>
	<fieldset class="benutzeraccount">
		<legend><?=h(_("Benutzeraccount"))?></legend>
		<dl>
			<dt class="c-ip-schutz"><label for="ipcheck"><?=h(_("IP-Schutz&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-ip-schutz"><input type="checkbox" name="ipcheck" id="ipcheck"<?=accesskey_attr(_("IP-Schutz&[login/einstellungen.php|1]"))?><?=$me->checkSetting('ipcheck') ? ' checked="checked"' : ''?> title="<?=h(_("Wenn diese Option deaktiviert ist, kann Ihre Session von mehreren IP-Adressen gleichzeitig genutzt werden. (Unsicher!)"))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-email-adresse"><label for="email"><?=h(_("E-Mail-Adresse&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-email-adresse"><input type="text" name="email" id="email"<?=accesskey_attr(_("E-Mail-Adresse&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->getTemporaryEMailAddress() !== null ? $me->getTemporaryEMailAddress() : $me->getEMailAddress())?>" title="<?=h(_("Ihre E-Mail-Adresse wird benötigt, wenn Sie Ihr Passwort vergessen haben."))?>" tabindex="<?=$tabindex++?>" /> <?=h(sprintf(_("Eine Änderung wird aus Sicherheitsgründen nicht sofort übernommen. Die Verzögerungsdauer beträgt %s."), format_btime(global_setting("EMAIL_CHANGE_DELAY"), true)))?></dd>
<?php
	if($me->getTemporaryEMailAddress() !== null)
	{
?>
			<dd class="c-email-adresse temporary"><?=sprintf(h(_("Vorläufig ist noch Ihre alte E-Mail-Adresse %s aktiv.")), "<strong>".htmlspecialchars($me->getEMailAddress())."</strong>")?></dd>
<?php
	}

	if(gpg_init())
	{
?>

			<dt class="c-gpg-key"><?=h(_("OpenPGP-Key&[login/einstellungen.php|1]"))?></dt>
<?php
		$fingerprint = $me->checkSetting("fingerprint");
		if($fingerprint)
		{
?>
			<dd class="c-gpg-key"><?=htmlspecialchars($fingerprint)?></dd>
			<dd class="c-gpg-key"><input type="checkbox" name="remove_fingerprint" id="i-remove-fingerprint"<?=accesskey_attr(_("Schlüssel löschen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /><label for="i-remove-fingerprint"> Schlüssel löschen</label></dd>
<?php
		}
?>
			<dd class="c-gpg-key"><input type="file" name="gpg" id="i-gpg"<?=accesskey_attr(_("OpenPGP-Key&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
<?php /*			<dd class="c-gpg-key"><input type="checkbox" name="gpg_im" id="i-gpg-im"<?=$me->checkSetting("gpg_im") ? " checked=\"checked\"" : ""?><?=accesskey_attr(_("Für Instant Messaging verwenden&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /><label for="i-gpg-im"> <?=h(_("Für Instant Messaging verwenden&[login/einstellungen.php|1]"))?></dd>*/?>
			<dd class="c-gpg-key"><a href="<?=htmlspecialchars(h_root."/gpg.asc.php")?>"><?=h(_("Den OpenPGP-Key des Spiels herunterladen"))?></a></dd>
<?php
	}
?>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung"><?=h(_("Ben&utzerbeschreibung[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung"<?=accesskey_attr(_("Ben&utzerbeschreibung[login/einstellungen.php|1]"))?> cols="50" rows="10" tabindex="<?=$tabindex++?>"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', htmlspecialchars($me->getUserDescription(false)))?></textarea></dd>
		</dl>
		<fieldset class="passwort-aendern">
			<legend><?=h(_("Passwort ändern"))?></legend>
			<dl>
				<dt class="c-altes-passwort"><label for="old-password"><?=h(_("Altes Passw&ort[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-altes-passwort"><input type="password" name="old-password" id="old-password"<?=accesskey_attr(_("Altes Passw&ort[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>

				<dt class="c-neues-passwort"><label for="new-password"><?=h(_("Neues Passwort&[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-neues-passwort"><input type="password" name="new-password" id="new-password"<?=accesskey_attr(_("Neues Passwort&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>

				<dt class="c-neues-passwort-wiederholen"><label for="new-password2"><?=h(_("Neues Passwort wiederholen&[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-neues-passwort-wiederholen"><input type="password" name="new-password2" id="new-password2"<?=accesskey_attr(_("Neues Passwort wiederholen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
			</dl>
		</fieldset>
	</fieldset>
	<div class="einstellungen-speichern-2"><input type="hidden" name="change-checkboxes" value="1" /><button type="submit" tabindex="<?=$save_tabindex?>"><?=h(_("Speicher&n"))?></button></div>
</form>
<?php
	login_gui::html_foot();
?>
