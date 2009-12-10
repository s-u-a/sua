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
	/**
	 * Einstellungen zum Spiel
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	$changed = false;

	$receive_settings = $me->checkSetting('receive');
	$show_building = $me->checkSetting('show_building');

	$db_config = $DATABASE->getConfig();
	$messengers = isset($db_config["instantmessaging"]) ? $db_config["instantmessaging"] : array();
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
		$sonden = floor($_POST['spionagesonden']);
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
		$me->setSetting("fingerprint", false);

	if(isset($_FILES["gpg"]) && !$_FILES["gpg"]["error"] && ($gpg = GPG::init()))
	{
		$info = $gpg->import(file_get_contents($_FILES["gpg"]["tmp_name"]));
		if(isset($info["fingerprint"]))
			$me->setSetting("fingerprint", $info["fingerprint"]);
		else
			$error[] = _("Das war kein gültiger GPG-Schlüssel.");
		unlink($_FILES["gpg"]["tmp_name"]);
	}

	if(isset($_POST['new-password']) && isset($_POST['new-password2']) && (isset($_SESSION["admin_username"]) && isset($_POST["change-password"]) || isset($_POST['old-password']) && ($_POST['old-password'] != $_POST['new-password'] || $_POST['new-password'] != $_POST['new-password2'])))
	{
		# Passwort aendern
		if(!isset($_SESSION["admin_username"]) && !$me->checkPassword($_POST['old-password']))
			$error[] = _('Das alte Passwort stimmt nicht.');
		elseif($_POST['new-password'] != $_POST['new-password2'])
			$error[] = _('Die beiden neuen Passworte stimmen nicht überein.');
		else
			$me->setPassword($_POST['new-password']);
	}

	if(isset($_POST["im-protocol"]) && isset($_POST["im-uin"]) && (trim($_POST["im-protocol"]) != $me->checkSetting("im-protocol") || trim($_POST["im-uin"]) != $me->checkSetting("im-uin")))
	{
		if(strlen(trim($_POST["im-uin"])) > 0)
			$imfile->addCheck(trim($_POST["im-protocol"]), trim($_POST["im-uin"]), $me->getName());
		else
		{
			$me->setSetting("im-protocol", $_POST["im-protocol"]);
			$me->setSetting("im-uin", $_POST["im-uin"]);
		}
	}

	if(isset($_SESSION["admin_username"]))
	{
		if(isset($_POST["rename"]) && trim($_POST["rename"]) && $_POST["rename"] != $me->getName())
		{
			$_POST["rename"] = trim(str_replace(" ", " ", $_POST["rename"]));
			if(strlen($_POST["rename"]) > 24)
				$error[] = _('Der Benutzername darf maximal 24 Bytes groß sein.');
			elseif(preg_match('/[\xf8-\xff\x00-\x1f\x7f]/', $_POST['rename'])) # Steuerzeichen
				$error[] = _('Der Benutzername enthält ungültige Zeichen.');
			elseif(User::UserExists($_POST['rename']))
				$error[] = _('Dieser Spieler existiert bereits.');
			elseif($me->rename($_POST["rename"]))
				$_SESSION["username"] = $me->getName();
		}

		if(isset($_POST["remove"]))
		{
			if($me->destroy())
			{
				unset($_SESSION["username"]);
				$gui->setOption("user", null);
				$gui->fatal(_("Der Benutzer wurde gelöscht."));
			}
		}
	}

	$gui->init();

	$fieldset = 0;
	$show_im = isset($messengers['jabber']);
?>
<h2><?=L::h(_("Einstellungen"))?></h2>
<?php
	foreach($error as $m)
	{
?>
<p class="error"><?=htmlspecialchars($m)?></p>
<?php
	}
?>
<script type="text/javascript">
// <![CDATA[
	document.write('<p><?=JS::jsentities(h(_("Klicken Sie auf einen der Punkte, um die zugehörigen Einstellungen auszuklappen.")))?></p>');
// ]]>
</script>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].global_setting("h_root").'/login/einstellungen.php?'.global_setting("URL_SUFFIX"))?>" method="post" class="einstellungen-formular" enctype="multipart/form-data">
	<fieldset class="aussehen" id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Aussehen&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Aussehen&[login/einstellungen.php|1]"))?></a></legend>
		<dl class="form">
			<dt class="c-skin"><label for="skin-choice"><?=L::h(_("Skin&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-skin">
				<select name="skin-choice" id="skin-choice"<?=L::accesskeyAttr(_("Skin&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" onchange="recalc_skin();" onkeyup="recalc_skin();">
<?php
	foreach($skins as $skin=>$skin_info)
	{
?>
					<option value="<?=htmlspecialchars($skin)?>"<?=$my_skin[0] == $skin ? " selected=\"selected\"" : ""?>><?=htmlspecialchars($skin_info[0])?></option>
<?php
	}
?>
					<option value="custom"<?=$my_skin[0] == "custom" ? ' selected="selected"' : ''?>><?=L::h(_("Benutzerdefiniert"))?></option>
				</select>
				<input type="text" name="skin" id="skin" value="<?=htmlspecialchars($my_skin[1])?>" tabindex="<?=$tabindex++?>" title="<?=L::h(_("Skin-Pfad&[login/einstellungen.php|1]"), false)?>"<?=L::accesskeyAttr(_("Skin-Pfad&[login/einstellungen.php|1]"))?> onkeyup="update_skin_path_wait();" onchange="onkeyup();" />
				<input type="text" name="skin-parameters" id="i-skin-parameters" value="<?=isset($my_skin[2]) && !is_array($my_skin[2]) ? htmlspecialchars($my_skin[2]) : ""?>" title="<?=L::h(_("Skin-Parameter&[login/einstellungen.php|1]"), false)?>"<?=L::accesskeyAttr(_("Skin-Parameter&[login/einstellungen.php|1]"))?> onkeyup="update_options_wait();" onchange="onkeyup();" />
			</dd>

			<dt class="c-werbung-ausblenden"><label for="noads"><?=L::h(_("Werbung ausblenden&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-werbung-ausblenden"><input type="checkbox" name="noads" id="noads"<?=L::accesskeyAttr(_("Werbung ausblenden&[login/einstellungen.php|1]"))?><?=$me->checkSetting('noads') ? ' checked="checked"' : ''?> title="<?=L::h(_("Wenn Sie die Werbung eingeblendet lassen, helfen Sie, die Finanzen des Spiels zu decken."))?>" tabindex="<?=$tabindex++?>" /></dd>
		</dl>

		<fieldset id="skin-options-fieldset">
			<legend><?=L::h(_("Skin-spezifische Einstellungen"))?></legend>
			<dl id="skin-options" class="form">
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
				$skin_options2[] = "'".JS::jsentities($option)."'";
			$skin_options1[] = "[ '".JS::jsentities($setting_name)."', [ ".implode(", ", $skin_options2)." ] ]";
		}
		$skin_options[] = "'".JS::jsentities($skin)."' : [ ".implode(", ", $skin_options1)." ]";
	}
?>

		<script type="text/javascript">
		// <![CDATA[
			var skin_options = {
				<?=implode(",\n\t\t\t\t", $skin_options)."\n"?>
			};
			var skin_settings = {<?php if($my_skin[0] != "custom" && isset($my_skin[2]) && is_array($my_skin[2])){?> '<?=JS::jsentities($my_skin[0])?>' : [ <?=implode(", ", $my_skin[2])?> ]<?php }?> };
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
						l[i].href = '<?=JS::jsentities(global_setting("h_root"))?>/login/res/style/'+document.getElementById("skin-choice").value+'/style.css';
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
		// ]]>
		</script>
	</fieldset>

	<fieldset class="verhalten" id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Verhalten&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Verhalten&[login/einstellungen.php|1]"))?></a></legend>
		<dl class="form">
			<dt class="c-spionagesonden"><label for="spionagesonden"><?=L::h(_("Spionagesonden&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-spionagesonden"><input type="text" name="spionagesonden" id="spionagesonden"<?=L::accesskeyAttr(_("Spionagesonden&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->checkSetting('sonden'))?>" title="<?=L::h(_("Anzahl Spionagesonden, die bei der Spionage eines fremden Planeten aus der Karte geschickt werden sollen"))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-schnellbau"><label for="fastbuild"><?=L::h(_("Auto-Schnellbau&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-auto-schnellbau"><input type="checkbox" name="fastbuild" id="fastbuild"<?=L::accesskeyAttr(_("Auto-Schnellbau&[login/einstellungen.php|1]"))?><?=$me->checkSetting('fastbuild') ? ' checked="checked"' : ''?> title="<?=L::h(_("Wird ein Gebäude in Auftrag gegeben, wird automatisch zum nächsten unbeschäftigten Planeten gewechselt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-schnellbau-auf-ausgebaute"><label for="i-full"><?=L::h(_("Schnellbau auf ausgebaute&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-schnellbau-auf-ausgebaute"><input type="checkbox" name="fastbuild_full" id="i-full"<?=L::accesskeyAttr(_("Schnellbau auf ausgebaute&[login/einstellungen.php|1]"))?><?=$me->checkSetting('fastbuild_full') ? ' checked="checked"' : ''?> title="<?=L::h(_("Durch diese Option werden auch ausgebaute Planeten bei der Schnellbau-Funktion berücksichtigt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-schnell-shortcuts"><label for="shortcuts"><?=L::h(_("Schnell-Shortcuts&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-schnell-shortcuts"><input type="checkbox" name="shortcuts" id="shortcuts"<?=L::accesskeyAttr(_("Schnell-Shortcuts&[login/einstellungen.php|1]"))?><?=$me->checkSetting('shortcuts') ? ' checked="checked"' : ''?> title="<?=L::h(_("Mit dieser Funktion brauchen Sie zum Ausführen der Shortcuts keine weitere Taste zu drücken."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-javascript-tooltips"><label for="tooltips"><?=L::h(_("Javascript-Tooltips&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-javascript-tooltips"><input type="checkbox" name="tooltips" id="tooltips"<?=L::accesskeyAttr(_("Javascript-Tooltips&[login/einstellungen.php|1]"))?><?=$me->checkSetting('tooltips') ? ' checked="checked"' : ''?> title="<?=L::h(_("Nicht auf langsamen Computern verwenden! Ist dieser Punkt aktiviert, werden die normalen Tooltips durch hübsche JavaScript-Tooltips ersetzt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-auto-refresh"><label for="autorefresh"><?=L::h(_("Auto-Refresh&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-auto-refresh"><input type="text" name="autorefresh" id="autorefresh"<?=L::accesskeyAttr(_("Auto-Refresh&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->checkSetting('ress_refresh'))?>" title="<?=L::h(_("Wird hier eine Zahl größer als 0 eingetragen, wird in deren Sekundenabstand die Rohstoffanzeige oben automatisch aktualisiert. (Hinweis: Diese Funktion erzeugt keinen zusätzlichen Traffic.)"))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-externe-navigationslinks"><label for="show-extern"><?=L::h(_("Externe Navigationslinks&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-externe-navigationslinks"><input type="checkbox" name="show_extern" id="show-extern"<?=L::accesskeyAttr(_("Externe Navigationslinks&[login/einstellungen.php|1]"))?><?=$me->checkSetting('show_extern') ? ' checked="checked"' : ''?> title="<?=L::h(_("Wenn diese Option aktiviert ist, werden in der Navigation Links auf spielexterne Seiten wie das Board angezeigt."))?>" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-erweiterte-gebaeudeansicht"><label for="i-erweiterte-gebaeudeansicht"><?=L::h(_("Erweiterte Gebäudeansicht&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-erweiterte-gebaeudeansicht"><input type="checkbox" name="extended_buildings" id="i-erweiterte-gebaeudeansicht"<?=L::accesskeyAttr(_("Erweiterte Gebäudeansicht&[login/einstellungen.php|1]"))?><?=$me->checkSetting("extended_buildings") ? " checked=\"checked\"" : ""?> title="<?=L::h(_("In der Gebäudeansicht wird zusätzlich der Produktionsunterschied zur nächsten Stufe angezeigt."))?>" tabindex="<?=$tabindex++?>" /></dd>
		</dl>
	</fieldset>

	<fieldset class="benachrichtigung" id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Benachrichtigung&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Benachrichtigung&[login/einstellungen.php|1]"))?></a></legend>
<?php
	if($show_im)
	{
?>
		<p><?=L::h(_("Nach Änderung des Instant-Messaging-Accounts wird zunächst eine Bestätigungsnachricht versandt."))?></p>
<?php
		if(!Config::imserverRunning())
		{
?>
		<p class="error imserver"><?=sprintf(h(_("Der Instant-Messaging-Bot läuft im Moment %snicht%s!")), "<strong>", "</strong>")?></p>
<?php
		}
		else
		{
?>
		<p class="successful imserver"><?=L::h(_("Der Instant-Messaging-Bot ist gestartet."))?></p>
<?php
		}

		$messenger_settings = array($me->checkSetting("im-uin"), $me->checkSetting("im-protocol"));
		if(isset($messengers[$messenger_settings[1]]) && isset($messengers[$messenger_settings[1]]["uin"]))
		{
?>
		<p id="im-uin" class="infobox"><?=sprintf(h(_("Die UIN des IM-Bots lautet %s.")), "<strong>".htmlspecialchars($messengers[$messenger_settings[1]]["uin"])."</strong>")?></p>
<?php
		}
	}
?>
		<dl class="form" id="form-benachrichtigung-1">
			<dt class="c-nachrichteninformierung"><label for="notify"><?=L::h(_("Nachrichten auf jeder Seite&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-nachrichteninformierung"><input type="checkbox" name="notify" id="notify"<?=L::accesskeyAttr(_("Nachrichten auf jeder Seite&[login/einstellungen.php|1]"))?><?=$me->checkSetting('notify') ? ' checked="checked"' : ''?> title="<?=L::h(_("Wenn diese Option aktiviert ist, wird nicht nur in der Übersicht angezeigt, dass Sie eine neue Nachricht erhalten haben, sondern auf allen Seiten."))?>" tabindex="<?=$tabindex++?>" /></dd>
<?php
	if($show_im)
	{
?>
			<dt class="c-im-account"><label for="i-im-protocol"><?=L::h(_("IM-Account&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-im-account">
				<select name="im-protocol" id="i-im-protocol"<?=L::accesskeyAttr(_("IM-Account&[login/einstellungen.php|1]"))?> onchange="if(update_imaddr) update_imaddr();" onkeyup="this.onchange();" tabindex="<?=$tabindex++?>">
					<option value=""><?=L::h(_("Deaktiviert"))?></option>
<?php
		$im_addrs_js = array();
		foreach($messengers as $protocol=>$minfo)
		{
			$im_addrs_js[] = "'".JS::jsentities($protocol)."' : '".JS::jsentities($minfo["uin"])."'";
?>
					<option value="<?=htmlspecialchars($protocol)?>"<?=($messenger_settings[1] == $protocol) ? ' selected="selected"' : ''?>><?=htmlspecialchars(_("[messenger_".$protocol."]"))?></option>
<?php
		}
?>
				</select>
				<input type="text" name="im-uin" id="i-im-uin" title="<?=L::h(_("UIN"))?>"<?=$messenger_settings[0] ? ' value="'.htmlspecialchars($messenger_settings[0]).'"' : ''?> tabindex="<?=$tabindex++?>" />
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
			var im_addrs = { <?=implode(", ", $im_addrs_js)?> };
			function update_imaddr()
			{
				document.getElementById('i-im-uin').disabled = !document.getElementById('i-im-protocol').value;

				if(!document.getElementById('im-uin'))
				{
					var p = document.createElement("p");
					p.id = "im-uin";
					p.className = "infobox";
					document.getElementById('form-benachrichtigung-1').parentNode.insertBefore(p, document.getElementById('form-benachrichtigung-1'));
				}

				var el = document.getElementById('im-uin');
				while(el.firstChild) el.removeChild(el.firstChild);

				if(im_addrs[document.getElementById('i-im-protocol').value])
				{
					var strong_uin = document.createElement("strong");
					strong_uin.appendChild(document.createTextNode(im_addrs[document.getElementById('i-im-protocol').value]));
<?php
		echo "\t\t\t\t\tel.appendChild(document.createTextNode('".sprintf(JS::jsentities(_("Die UIN des IM-Bots lautet %s.")), "'));\n\t\t\t\t\tel.appendChild(strong_uin);\n\t\t\t\t\tel.appendChild(document.createTextNode('")."'));\n";
?>
					el.style.visibility = "visible";
				}
				else
				{
					el.style.visibility = "hidden";
					el.appendChild(document.createTextNode(" "));
				}
			}
			update_imaddr();
		</script>
<?php
	}
?>
		<fieldset class="benachrichtigungen-nachrichten">
			<legend><?=L::h(_("Benachrichtigung bei Nachrichten"))?></legend>
			<table>
				<thead>
					<tr>
						<th class="c-nachrichtentyp"><?=L::h(_("Nachrichtentyp"))?></th>
						<th class="c-ankunft"><?=L::h(_("Ankunft"))?></th>
						<th class="c-rueckkehr"><?=L::h(_("Rückkehr"))?></th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><?=L::h(_("IM-Benachrichtigung"))?></th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-kaempfe">
						<th class="c-nachrichtentyp"><?=L::h(_("[message_1]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[1][1]"<?=L::accesskeyAttr(_("[message_1]&[login/einstellungen.php|1]"))?><?=$receive_settings[1][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=L::h(_("[message_2]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[2][1]"<?=L::accesskeyAttr(_("[message_2]&[login/einstellungen.php|1]"))?><?=$receive_settings[2][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=L::h(_("[message_3]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[3][0]"<?=L::accesskeyAttr(_("[message_3]&[login/einstellungen.php|1]"))?><?=$receive_settings[3][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=L::h(_("[message_4]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr"><input type="checkbox" name="nachrichten[4][1]"<?=L::accesskeyAttr(_("[message_4]&[login/einstellungen.php|1]"))?><?=$receive_settings[4][1] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=L::h(_("[message_5]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft"><input type="checkbox" name="nachrichten[5][0]"<?=L::accesskeyAttr(_("[message_5]&[login/einstellungen.php|1]"))?><?=$receive_settings[5][0] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
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
						<th class="c-nachrichtentyp"><?=L::h(_("[message_6]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][6]"<?=L::accesskeyAttr(_("[message_6]&[login/einstellungen.php|1]"))?><?=$messenger_receive['messages'][6] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
					<tr class="r-verbuendete">
						<th class="c-nachrichtentyp"><?=L::h(_("[message_7]&[login/einstellungen.php|1]"))?></th>
						<td class="c-ankunft leer"></td>
						<td class="c-rueckkehr leer"></td>
						<td class="c-im-benachrichtigung"><input type="checkbox" name="im-receive[messages][7]"<?=L::accesskeyAttr(_("[message_7]&[login/einstellungen.php|1]"))?><?=$messenger_receive['messages'][7] ? ' checked="checked"' : ''?> tabindex="<?=$tabindex++?>" /></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</fieldset>
		<fieldset class="benachrichtigungen-fertigstellung">
			<legend><?=L::h(_("Benachrichtigung bei Fertigstellung"))?></legend>
			<table>
				<thead>
					<tr>
						<th class="c-gegenstandsart"><?=L::h(_("Gegenstandsart"))?></th>
						<th class="c-uebersicht"><?=L::h(_("Verbl. Bauzeit in der Übersicht"))?></th>
<?php
	if($show_im)
	{
?>
						<th class="c-im-benachrichtigung"><?=L::h(_("IM-Benachichtigung"))?></th>
<?php
	}
?>
					</tr>
				</thead>
				<tbody>
					<tr class="r-gebaeude">
						<th class="c-gegenstandsart"><?=L::h(_("Gebäude&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[gebaeude]"<?=L::accesskeyAttr(_("Gebäude&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['gebaeude']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['gebaeude']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jedes einzelne [Gebäude]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][gebaeude]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['gebaeude']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['gebaeude']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jedes einzelne [Gebäude]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-forschung">
						<th class="c-gegenstandsart"><?=L::h(_("Forschung&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[forschung]"<?=L::accesskeyAttr(_("Forschung&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['forschung']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['forschung']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jede einzelne [Forschung]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][forschung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['forschung']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['forschung']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jede einzelne [Forschung]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-roboter">
						<th class="c-gegenstandsart"><?=L::h(_("Roboter&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[roboter]"<?=L::accesskeyAttr(_("Roboter&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['roboter']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['roboter']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jeder einzelne [Roboter]"))?></option>
								<option value="2"<?=($show_building['roboter']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Roboter] eines Typs"))?></option>
								<option value="3"<?=($show_building['roboter']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Roboter]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][roboter]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['roboter']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['roboter']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jeder einzelne [Roboter]"))?></option>
								<option value="2"<?=($messenger_receive['building']['roboter']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Roboter] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['roboter']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Roboter]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-schiffe">
						<th class="c-gegenstandsart"><?=L::h(_("Schiffe&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[schiffe]"<?=L::accesskeyAttr(_("Schiffe&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['schiffe']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['schiffe']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jedes einzelne [Schiff]"))?></option>
								<option value="2"<?=($show_building['schiffe']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Schiffe] eines Typs"))?></option>
								<option value="3"<?=($show_building['schiffe']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Schiffe]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][schiffe]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['schiffe']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['schiffe']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jedes einzelne [Schiff]"))?></option>
								<option value="2"<?=($messenger_receive['building']['schiffe']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Schiffe] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['schiffe']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Schiffe]"))?></option>
							</select>
						</td>
<?php
	}
?>
					</tr>
					<tr class="r-verteidigung">
						<th class="c-gegenstandsart"><?=L::h(_("Verteidigung&[login/einstellungen.php|1]"))?></th>
						<td class="c-uebersicht">
							<select name="building[verteidigung]"<?=L::accesskeyAttr(_("Verteidigung&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>">
								<option value="0"<?=($show_building['verteidigung']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($show_building['verteidigung']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jede einzelne [Verteidigungsanlage]"))?></option>
								<option value="2"<?=($show_building['verteidigung']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Verteidigungsanlagen] eines Typs"))?></option>
								<option value="3"<?=($show_building['verteidigung']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Verteidigungsanlagen]"))?></option>
							</select>
						</td>
<?php
	if($show_im)
	{
?>
						<td class="c-im-benachrichtigung">
							<select name="im-receive[building][verteidigung]" tabindex="<?=$tabindex++?>">
								<option value="0"<?=($messenger_receive['building']['verteidigung']==0) ? ' selected="selected"' : ''?>><?=L::h(_("Ausgeschaltet"))?></option>
								<option value="1"<?=($messenger_receive['building']['verteidigung']==1) ? ' selected="selected"' : ''?>><?=L::h(_("Jede einzelne [Verteidigungsanlage]"))?></option>
								<option value="2"<?=($messenger_receive['building']['verteidigung']==2) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Verteidigungsanlagen] eines Typs"))?></option>
								<option value="3"<?=($messenger_receive['building']['verteidigung']==3) ? ' selected="selected"' : ''?>><?=L::h(_("Alle [Verteidigungsanlagen]"))?></option>
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

	<div class="einstellungen-speichern-1 button"><button tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Speicher&n[login/einstellungen.php|1]"))?>><?=L::h(_("Speicher&n[login/einstellungen.php|1]"))?></button></div>
<?php
	$save_tabindex = $tabindex++;

	if(!$me->userLocked())
	{
?>
	<fieldset class="urlaubsmodus" id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Urlaubsmodus&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Urlaubsmodus&[login/einstellungen.php|1]"))?></a></legend>
<?php
		if(!$me->umode())
		{
			if(!$me->umodePossible())
			{
?>
		<p><?=L::h(_("Sie können derzeit nicht in den Urlaubsmodus wechseln, da Sie Flotten zu fremden Planeten unterwegs haben."))?></p>
<?php
			}
			elseif($me->permissionToUmode() || isset($_SESSION['admin_username']))
			{
?>
		<div class="button"><input type="submit" name="umode" value="<?=L::h(_("Urlaubsmodus&[login/einstellungen.php|1]"), false)?>"<?=L::accesskeyAttr(_("Urlaubsmodus&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" onclick="return confirm('<?=JS::jsentities(_("Wollen Sie den Urlaubsmodus wirklich betreten?"))?>');" /></div>
		<p><?=L::h(sprintf(_("Sie werden frühestens nach drei Tagen (%s) aus dem Urlaubsmodus zurückkehren können."), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i'), $me->getUmodeReturnTime()))))?></p>
<?php
			}
			else
			{
?>
		<p><?=L::h(sprintf(_("Sie können erst wieder ab dem %s in den Urlaubsmodus wechseln."), sprintf(_("%s (Serverzeit)"), date('Y-m-d, H:i', $me->getUmodeReturnTime()))))?></p>
<?php
			}
		}
		elseif($me->permissionToUmode() || isset($_SESSION['admin_username']))
		{
?>
		<div class="button"><input type="submit" name="umode" value="<?=L::h(_("Urlaubsmodus verlassen"))?>" tabindex="<?=$tabindex++?>" onclick="return confirm('<?=JS::jsentities(_("Wollen Sie den Urlaubsmodus wirklich verlassen?"))?>');" /></div>
<?php
		}
		else
		{
?>
		<p><?=L::h(sprintf(_("Sie können den Urlaubsmodus spätestens am %s verlassen."), sprintf(_("%s (Serverzeit)"), date(_('Y-m-d, H:i'), $me->getUmodeReturnTime()))))?></p>
<?php
		}
?>
	</fieldset>
<?php
	}
?>
	<fieldset class="benutzeraccount" id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Benutzeraccount&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Benutzeraccount&[login/einstellungen.php|1]"))?></a></legend>
		<dl class="form">
			<dt class="c-email-adresse"><label for="email"><?=L::h(_("E-Mail-Adresse&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-email-adresse"><input type="text" name="email" id="email"<?=L::accesskeyAttr(_("E-Mail-Adresse&[login/einstellungen.php|1]"))?> value="<?=htmlspecialchars($me->getTemporaryEMailAddress() !== null ? $me->getTemporaryEMailAddress() : $me->getEMailAddress())?>" title="<?=L::h(_("Ihre E-Mail-Adresse wird benötigt, wenn Sie Ihr Passwort vergessen haben."))?>" tabindex="<?=$tabindex++?>" /> <?=L::h(sprintf(_("Eine Änderung wird aus Sicherheitsgründen nicht sofort übernommen. Die Verzögerungsdauer beträgt %s."), F::formatBTime(global_setting("EMAIL_CHANGE_DELAY"), true)))?></dd>
<?php
	if(Functions::check_email($me->getTemporaryEMailAddress() !== null ? $me->getTemporaryEMailAddress() : $me->getEMailAddress()))
	{
?>
			<dd class="c-email-adresse validity successful"><?=L::h(_("Diese E-Mail-Adresse ist gültig."))?></dd>
<?php
	}
	else
	{
?>
			<dd class="c-email-adresse validity error"><?=L::h(_("Diese E-Mail-Adresse ist ungültig."))?></dd>
<?php
	}

	if($me->getTemporaryEMailAddress() !== null)
	{
?>
			<dd class="c-email-adresse temporary"><?=sprintf(h(_("Vorläufig ist noch Ihre alte E-Mail-Adresse %s aktiv.")), "<strong>".htmlspecialchars($me->getEMailAddress())."</strong>")?></dd>
<?php
	}

	if(GPG::init())
	{
?>

			<dt class="c-gpg-key"><?=L::h(_("OpenPGP-Key&[login/einstellungen.php|1]"))?></dt>
<?php
		$fingerprint = $me->checkSetting("fingerprint");
		if($fingerprint)
		{
?>
			<dd class="c-gpg-key"><?=htmlspecialchars($fingerprint)?></dd>
			<dd class="c-gpg-key"><input type="checkbox" name="remove_fingerprint" id="i-remove-fingerprint"<?=L::accesskeyAttr(_("Schlüssel löschen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /><label for="i-remove-fingerprint"> Schlüssel löschen</label></dd>
<?php
		}
?>
			<dd class="c-gpg-key"><input type="file" name="gpg" id="i-gpg"<?=L::accesskeyAttr(_("OpenPGP-Key&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
<?php /*			<dd class="c-gpg-key"><input type="checkbox" name="gpg_im" id="i-gpg-im"<?=$me->checkSetting("gpg_im") ? " checked=\"checked\"" : ""?><?=L::accesskeyAttr(_("Für Instant Messaging verwenden&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /><label for="i-gpg-im"> <?=L::h(_("Für Instant Messaging verwenden&[login/einstellungen.php|1]"))?></dd>*/?>
			<dd class="c-gpg-key"><a href="<?=htmlspecialchars(global_setting("h_root")."/gpg.asc.php")?>"><?=L::h(_("Den OpenPGP-Key des Spiels herunterladen"))?></a></dd>
<?php
	}
?>

			<dt class="c-benutzerbeschreibung"><label for="benutzerbeschreibung"><?=L::h(_("Ben&utzerbeschreibung[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-benutzerbeschreibung"><textarea name="benutzerbeschreibung" id="benutzerbeschreibung"<?=L::accesskeyAttr(_("Ben&utzerbeschreibung[login/einstellungen.php|1]"))?> cols="50" rows="10" tabindex="<?=$tabindex++?>"><?=preg_replace("/[\r\n\t]/e", '\'&#\'.ord(\'$0\').\';\'', htmlspecialchars($me->getUserDescription(false)))?></textarea></dd>
		</dl>
		<fieldset class="passwort-aendern">
			<legend><?=L::h(_("Passwort ändern"))?></legend>
			<dl class="form">
<?php
	if(!isset($_SESSION["admin_username"]))
	{
?>
				<dt class="c-altes-passwort"><label for="old-password"><?=L::h(_("Altes Passw&ort[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-altes-passwort"><input type="password" name="old-password" id="old-password"<?=L::accesskeyAttr(_("Altes Passw&ort[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
<?php
	}
	else
	{
?>
				<dt class="c-passwort-aendern"><label for="i-passwort-aendern"><?=L::h(_("Passwort ändern&[login-einstellungen.php|1]"))?></label></dt>
				<dd class="c-passwort-aendern"><input type="checkbox" name="change-password" id="i-passwort-aendern"<?=L::accesskeyAttr(_("Passwort ändern&[login-einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
<?php
	}
?>

				<dt class="c-neues-passwort"><label for="new-password"><?=L::h(_("Neues Passwort&[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-neues-passwort"><input type="password" name="new-password" id="new-password"<?=L::accesskeyAttr(_("Neues Passwort&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>

				<dt class="c-neues-passwort-wiederholen"><label for="new-password2"><?=L::h(_("Neues Passwort wiederholen&[login/einstellungen.php|1]"))?></label></dt>
				<dd class="c-neues-passwort-wiederholen"><input type="password" name="new-password2" id="new-password2"<?=L::accesskeyAttr(_("Neues Passwort wiederholen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
			</dl>
		</fieldset>
	</fieldset>
<?php
	if(isset($_SESSION["admin_username"]))
	{
?>
	<fieldset id="fieldset-<?=$fieldset++?>">
		<legend><a accesskey="<?=L::accesskeyAttr(_("Administration&[login/einstellungen.php|1]"))?>" tabindex="<?=$tabindex++?>"><?=L::h(_("Administration&[login/einstellungen.php|1]"))?></a></legend>
		<dl class="form">
			<dt class="c-benutzer-umbenennen"><label for="i-benutzer-umbenennen"><?=L::h(_("Benutzer umbenennen&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-benutzer-umbenennen"><input type="text" id="i-benutzer-umbenennen" name="rename" value="<?=htmlspecialchars($me->getName())?>"<?=L::accesskeyAttr(_("Benutzer umbenennen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-benutzer-loeschen"><label for="i-benutzer-loeschen"><?=L::h(_("Benutzer löschen&[login/einstellungen.php|1]"))?></label></dt>
			<dd class="c-benutzer-loeschen"><input type="checkbox" id="i-benutzer-loeschen" name="remove"<?=L::accesskeyAttr(_("Benutzer löschen&[login/einstellungen.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
		</dl>
	</fieldset>
<?php
	}
?>
	<div class="einstellungen-speichern-2 button"><input type="hidden" name="change-checkboxes" value="1" /><button type="submit" tabindex="<?=$save_tabindex?>"><?=L::h(_("Speicher&n"))?></button></div>
</form>
<script type="text/javascript">
// <![CDATA[
	var settings_hidden = { };
	var c = getCookies()['settings_expand_<?=md5(JS::jsentities($me->getName()))?>'];
	if(!c) c = 0;

	function toggleVisibility(i)
	{
		var el = document.getElementById("fieldset-"+i);
		if(!el) return;
		settings_hidden[i] = !settings_hidden[i];
		el = el.firstChild;
		while(el)
		{
			if(el.nodeType == 1 && el.nodeName.toLowerCase() != "legend")
				el.style.display = settings_hidden[i] ? "none" : "";
			el = el.nextSibling;
		}
		if(!settings_hidden[i])
			c = c | (1 << i);
		else if(c & (1 << i))
			c = c ^ (1 << i);
		document.cookie = 'settings_expand_<?=md5(JS::jsentities($me->getName()))?>=' + c;
	}

	var el;
	for(var i=0; el=document.getElementById("fieldset-"+i); i++)
	{
		var legend_el = el.getElementsByTagName("legend")[0];
		if(legend_el)
		{
			var legend_a_el = legend_el.getElementsByTagName("a")[0];
			if(legend_a_el)
			{
				legend_a_el.href = "javascript:toggleVisibility("+i+");";
				if(!(c & (1 << i)))
					toggleVisibility(i);
			}
		}
	}
// ]]>
</script>
<?php
	$gui->end();
?>
