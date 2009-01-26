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
	 * Verschiedene Aktionen, die die Flottenseite unterstützen:
	 * - Handel einer Flotte einstellen
	 * - Lesezeichen verwalten
	 * - Flugroute einsehen
	 * - Bündnisangriff starten
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('../include.php');

	if(!isset($_GET['action']))
		$_GET['action'] = false;

	$gui->init();

	switch($_GET['action'])
	{
		case 'handel':
		{
			if(!isset($_GET['id'])) $flotten_id = false;
			else $flotten_id = $_GET['id'];

			if($flotten_id)
			{
				$fleet = Classes::Fleet($flotten_id);
				$flotten_id = $fleet->getName();

				if($flotten_id)
				{
					$planet_key = $me->getPlanetByPos($fleet->getCurrentTarget());
					$type = $fleet->getCurrentType();

					if($planet_key === false || $type != '4' || $fleet->isFlyingBack())
						$flotten_id = false;
				}
			}

			if(!$flotten_id)
			{
?>
<p class="error"><?=L::h(_("Ungültigen Transport ausgewählt."))?></p>
<?php
				$gui->end();
				exit();
			}

			if(!$me->permissionToAct())
			{
?>
<p class="error"><?=L::h(_("Sie haben keine Berechtigung, Flottenaufträge aufzugeben."))?></p>
<?php
				$gui->end();
				exit();
			}

			$active_planet = $me->getActivePlanet();
			$me->setActivePlanet($planet_key);
			$available_ress = $me->getRess();
			$available_robs = array();
			foreach($me->getItemsList('roboter') as $id)
				$available_robs[$id] = $me->getItemLevel($id);
?>
<h2 id="handel">Handel</h2>
<p>Die Handelsfunktion ermöglicht es Ihnen, herannahenden Transporten Rohstoffe oder Roboter mit auf den Weg zu geben, ohne dass Sie dazu einen zusätzlichen Transport starten müssen.</p>
<?php
			foreach($fleet->getUsersList() as $no=>$username)
			{
				$verb = $me->isVerbuendet($username);

				if($username == $me->getName()) $class = 'eigen';
				elseif($verb) $class = 'verbuendet';
				else $class = 'fremd';
?>
<form action="flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($_GET['id']).'&'.global_setting("URL_SUFFIX"))?>" method="post" class="handel <?=$class?>">
	<fieldset>
		<legend><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($username).'&'.global_setting("URL_SUFFIX"))?>" title="<?=L::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($username)?></a></legend>
<?php
				$trans = $fleet->getTransportCapacity($username);
				$handel = $fleet->getHandel($username);
				$used = $fleet->getTransport($username);
				$keepres = $fleet->dontPutRes($username);
				if($me->getName() == $fleet->getFirstUser() && isset($_POST["handel_username"]) && $_POST["handel_username"] == $username && $fleet->dontPutRes($username, isset($_POST["keepres"])))
					$keepres = isset($_POST["keepres"]);
				if($keepres)
				{
					$trans[0] -= array_sum($used[0]);
					$trans[1] -= array_sum($used[1]);
				}

				$remaining_trans = array($trans[0]-array_sum($handel[0]), $trans[1]-array_sum($handel[1]));

				if(isset($_POST['handel_username']) && $_POST['handel_username'] == $username && isset($_POST['handel']) && is_array($_POST['handel']))
				{
					if(!isset($_POST['handel_type']) || ($_POST['handel_type'] != 'set' && $_POST['handel_type'] != 'add'))
						$type = ($verb ? 'set' : 'add');
					else $type = $_POST['handel_type'];

					$new_handel = array(array(0,0,0,0,0),array());
					if(isset($_POST['handel'][0]) && is_array($_POST['handel'][0]))
					{
						if(isset($_POST['handel'][0][0])) $new_handel[0][0] = $_POST['handel'][0][0];
						if(isset($_POST['handel'][0][1])) $new_handel[0][1] = $_POST['handel'][0][1];
						if(isset($_POST['handel'][0][2])) $new_handel[0][2] = $_POST['handel'][0][2];
						if(isset($_POST['handel'][0][3])) $new_handel[0][3] = $_POST['handel'][0][3];
						if(isset($_POST['handel'][0][4])) $new_handel[0][4] = $_POST['handel'][0][4];
					}
					if(isset($_POST['handel'][1]) && is_array($_POST['handel'][1]))
						$new_handel[1] = $_POST['handel'][1];

					foreach($new_handel[0] as $i=>$v)
					{
						$av = $available_ress[$i];
						if($type == 'set')
						{
							$add = $handel[0][$i];
							if(!$verb && $v < $add) $v = $add;
							$av += $add;
						}

						if($v > $av) $v = $av;
						$new_handel[0][$i] = $v;
					}
					foreach($new_handel[1] as $i=>$v)
					{
						if(!isset($available_robs[$i]))
						{
							unset($new_handel[1][$i]);
							continue;
						}
						$av = $available_robs[$i];
						if($type == 'set')
						{
							$add = 0;
							if(isset($handel[1][$i])) $add = $handel[1][$i];
							if(!$verb && $v < $add) $v = $add;
							$av += $add;
						}
						if($v > $av) $v = $av;
						$new_handel[1][$i] = $v;
					}

					if($type == 'set') $max = $trans;
					else $max = $remaining_trans;

					$new_handel = array(Functions::fitToMax($new_handel[0], $max[0]), Functions::fitToMax($new_handel[1], $max[1]));

					if($type == 'set') $fleet->setHandel($_POST['handel_username'], $new_handel[0], $new_handel[1]);
					else $fleet->addHandel($_POST['handel_username'], $new_handel[0], $new_handel[1]);
				}

				$new_handel = $fleet->getHandel($username);
				if($handel != $new_handel)
				{
					# Gueter vom Planeten abziehen
					if($type == 'set')
					{
						$ress_sub = array($new_handel[0][0]-$handel[0][0],
											$new_handel[0][1]-$handel[0][1],
											$new_handel[0][2]-$handel[0][2],
											$new_handel[0][3]-$handel[0][3],
											$new_handel[0][4]-$handel[0][4]);
						$rob_sub = array();
						foreach($me->getItemsList('roboter') as $id)
						{
							$old = $new = 0;
							if(isset($handel[1][$id])) $old = $handel[1][$id];
							if(isset($new_handel[1][$id])) $new = $new_handel[1][$id];
							if($new != $old)
								$rob_sub[$id] = $new-$old;
						}
					}
					else list($ress_sub, $rob_sub) = $new_handel;

					$me->subtractRess($ress_sub, false);
					$available_ress = $me->getRess();
					foreach($rob_sub as $id=>$sub)
					{
						$available_robs[$id] -= $sub;
						$me->changeItemLevel($id, -$sub, 'roboter');
					}

					if($type == 'set') $handel = $new_handel;
					else
					{
						$handel[0][0] += $new_handel[0][0];
						$handel[0][1] += $new_handel[0][1];
						$handel[0][2] += $new_handel[0][2];
						$handel[0][3] += $new_handel[0][3];
						$handel[0][4] += $new_handel[0][4];
						foreach($new_handel[1] as $k=>$v)
							$handel[1][$k] += $v;
					}
					$remaining_trans = array($trans[0]-array_sum($handel[0]), $trans[1]-array_sum($handel[1]));
				}

				if($verb)
				{
					$mess1 = _('Sie können das Handelsangebot zu diesem Spieler ändern, da Sie mit ihm verbündet sind.');
					if($username == $me->getName()) $mess2 = _('Die Flotte hat Platz für %1$s Tonnen Rohstoffe (%3$s verbleibend) und %2$s Roboter (%4$s verbleibend).');
					else $mess2 = _('Die Flotte hat Platz für %1$s Tonnen Rohstoffe (%3$s verbleibend).');
					$input_name = 'set';
					$value = '%s';
					$disabled = '';
					$show_submit = true;
				}
				else
				{
					$mess1 = _('Sie können das bereits eingelagerte Handelsangebot für diesen Spieler nicht ändern, da Sie nicht mit ihm verbündet sind. Sie können nur weitere Rohstoffe einlagern.');
					$mess2 = _('Es verbleibt Platz für %3$s Tonnen Rohstoffe.');
					$input_name = 'add';
					$value = '0';
					if($remaining_trans[0] == 0)
					{
						$disabled = ' disabled="disabled"';
						$show_submit = false;
					}
					else
					{
						$disabled = '';
						$show_submit = true;
					}
				}
?>
		<script type="text/javascript">
			res_now['handel-<?=$no?>'] = [ <?=implode(", ", $me->getRess())?> ];
		</script>
		<input type="hidden" name="handel_username" value="<?=htmlspecialchars($username)?>" />
		<input type="hidden" name="handel_type" value="<?=$input_name?>" />
		<p><?=htmlspecialchars($mess1)?></p>
		<p><?php printf($mess2, F::ths($trans[0]), F::ths($trans[1]), F::ths($remaining_trans[0]), F::ths($remaining_trans[1]))?></p>
<?php
				if($me->getName() == $fleet->getFirstUser())
				{
?>
		<dl>
			<dt class="c-rohstoffe-behalten"><label for="i-rohstoffe-behalten"><?=L::h(_("Transportgut der Flotte nicht abliefern"))?></label></dt>
			<dd class="c-rohstoffe-behalten"><input type="checkbox" id="i-rohstoffe-behalten" name="keepres"<?=$keepres ? " checked=\"checked\"" : ""?> title="<?=L::h(_("Diese Funktion erleichtert es Ihnen, mit einer Flugroute Rohstoffe von mehreren Planeten einzusammeln."))?>" /></dd>
		</dl>
<?php
				}
?>
		<table>
			<thead>
				<tr>
					<th class="c-gut"><?=L::h(_("Gut"))?></th>
					<th class="c-einlagern"><?=L::h(_("Einlagern"))?></th>
<?php
				if(!$verb)
				{
?>
					<th class="c-bereits-eingelagert"><?=L::h(_("Bereits eingelagert"))?></th>
<?php
				}
?>
					<th class="c-verfuegbar"><?=L::h(_("Verfügbar"))?></th>
				</tr>
			</thead>
			<tbody>
<?php
				if($trans[0] > 0)
				{
?>
				<tr class="c-carbon">
					<th class="c-gut"><label for="handel-<?=$no?>-0-0"><?=L::h(_("[ress_0]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[0][0]" id="handel-<?=$no?>-0-0" value="<?php printf($value, $handel[0][0])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=F::ths($handel[0][0])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-0-0').value=Math.floor(res_now['handel-<?=$no?>'][0]);" id="handel-<?=$no?>-carbon"><?=F::ths($available_ress[0])?></a></td>
				</tr>
				<tr class="c-aluminium">
					<th class="c-gut"><label for="handel-<?=$no?>-0-1"><?=L::h(_("[ress_1]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[0][1]" id="handel-<?=$no?>-0-1" value="<?php printf($value, $handel[0][1])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=F::ths($handel[0][1])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-0-1').value=Math.floor(res_now['handel-<?=$no?>'][1]);" id="handel-<?=$no?>-aluminium"><?=F::ths($available_ress[1])?></a></td>
				</tr>
				<tr class="c-wolfram">
					<th class="c-gut"><label for="handel-<?=$no?>-0-2"><?=L::h(_("[ress_2]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[0][2]" id="handel-<?=$no?>-0-2" value="<?php printf($value, $handel[0][2])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=F::ths($handel[0][2])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-0-2').value=Math.floor(res_now['handel-<?=$no?>'][2]);" id="handel-<?=$no?>-wolfram"><?=F::ths($available_ress[2])?></a></td>
				</tr>
				<tr class="c-radium">
					<th class="c-gut"><label for="handel-<?=$no?>-0-3"><?=L::h(_("[ress_3]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[0][3]" id="handel-<?=$no?>-0-3" value="<?php printf($value, $handel[0][3])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=F::ths($handel[0][3])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-0-3').value=Math.floor(res_now['handel-<?=$no?>'][3]);" id="handel-<?=$no?>-radium"><?=F::ths($available_ress[3])?></a></td>
				</tr>
				<tr class="c-tritium">
					<th class="c-gut"><label for="handel-<?=$no?>-0-4"><?=L::h(_("[ress_4]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[0][4]" id="handel-<?=$no?>-0-4" value="<?php printf($value, $handel[0][4])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=F::ths($handel[0][4])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-0-4').value=Math.floor(res_now['handel-<?=$no?>'][4]);" id="handel-<?=$no?>-tritium"><?=F::ths($available_ress[4])?></a></td>
				</tr>
<?php
				}
				if($username == $me->getName() && $trans[1] > 0)
				{
					foreach($me->getItemsList('roboter') as $id)
					{
						$h = 0;
						if(isset($handel[1][$id])) $h = $handel[1][$id];
?>
				<tr class="c-ro-<?=htmlspecialchars($id)?>">
					<th class="c-gut"><label for="handel-<?=$no?>-1-<?=$id?>"><?=L::h(_("[item_".$id."]"))?></label></th>
					<td class="c-einlagern"><input type="text" name="handel[1][<?=$id?>]" id="handel-<?=$no?>-1-<?=$id?>" value="<?=htmlspecialchars($h)?>" /></td>
					<td class="c-verfuegbar"><a onclick="document.getElementById('handel-<?=$no?>-1-<?=JS::jsentities($id)?>').value=<?=$available_robs[$id]?>"><?=F::ths($available_robs[$id])?></a></td>
				</tr>
<?php
					}
				}
?>
			</tbody>
<?php
				if($show_submit)
				{
?>
			<tfoot>
				<tr>
					<td colspan="<?=3-$verb?>" class="button"><button type="submit"><?=L::h(_("Handel ändern"))?></button></td>
				</tr>
			</tfoot>
<?php
				}
?>
		</table>
<?php
				if((!isset($_SESSION["disable_javascript"]) || !$_SESSION["disable_javascript"]) && $me->checkSetting("ress_refresh") > 0)
				{
					$prod = $me->getProduction();
					$limit = $me->getProductionLimit();
?>
		<script type="text/javascript">
		// <![CDATA[
			refresh_ress(<?=$me->checkSetting('ress_refresh')*1000?>, 'handel-<?=$no?>', [ <?=implode(", ", $me->getRess())?> ], [ <?=implode(", ", $me->getProduction())?> ] , [ <?=implode(", ", $me->getProductionLimit())?> ]);

			if(!last_res) var last_res = { };
			last_res['handel-<?=$no?>'] = [ ];
			refresh_callbacks['handel-<?=$no?>'] = [ function(){
				var changed = false;
				for(var i=0; i<=4; i++)
				{
					if(last_res['handel-<?=$no?>'][i] && document.getElementById('handel-<?=$no?>-0-'+i).value == last_res['handel-<?=$no?>'][i])
					{
						document.getElementById('handel-<?=$no?>-0-'+i).value = Math.floor(res_now['handel-<?=$no?>'][i]);
						changed = true;
					}
					last_res['handel-<?=$no?>'][i] = Math.floor(res_now['handel-<?=$no?>'][i]);
				}
			} ];
		// ]]>
		</script>
<?php
				}
?>
	</fieldset>
</form>
<?php
			}

			$me->setActivePlanet($active_planet);

			break;
		}
		case 'shortcuts':
		{
			if(isset($_GET['up'])) $me->movePosShortcutUp($_GET['up']);
			if(isset($_GET['down'])) $me->movePosShortcutDown($_GET['down']);
			if(isset($_GET['remove'])) $me->removePosShortcut($_GET['remove']);
			$shortcuts = $me->getPosShortcutsList();
			$count = count($shortcuts);
			if($count <= 0)
			{
?>
<p class="nothingtodo"><?=L::h(_("Sie haben keine Planetenlesezeichen gespeichert. In der Karte können Sie Lesezeichen anlegen."))?></p>
<?php
			}
			else
			{
?>
<fieldset>
	<legend><?=L::h(_("Lesezeichen verwalten"))?></legend>
	<ol class="shortcuts-verwalten order-list">
<?php
				$i = 0;
				foreach($shortcuts as $shortcut)
				{
					$owner = $shortcut->getOwner();
					$s = Planet::format($shortcut).': ';
					if($owner)
					{
						$user_obj = Classes::User($owner);
						$s .= $shortcut->getName().' (';
						$alliance = $user_obj->getAlliance();
						if($alliance) $s .= '['.$alliance.'] ';
						$s .= $owner.')';
					}
					else $s .= '['._("unbesiedelt").']';
?>
		<li><?=htmlspecialchars($s)?> <span class="aktionen"><?php if($i>0){?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;up=<?=htmlspecialchars(urlencode($shortcut))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="hoch">[<?=L::h(_("Hoch"))?>]</a><?php } if($i<$count-1){?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;down=<?=htmlspecialchars(urlencode($shortcut))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="runter">[<?=L::h(_("Runter"))?>]</a><?php }?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;remove=<?=htmlspecialchars(urlencode($shortcut))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="loeschen">[<?=L::h(_("Löschen"))?>]</a></span></li>
<?php
					$i++;
				}
?>
	</ol>
</fieldset>
<?php
			}
			break;
		}
		case "buendnisangriff":
		{
			if(!isset($_GET['id'])) $flotten_id = false;
			else $flotten_id = $_GET['id'];

			if($flotten_id)
			{
				$fleet = Classes::Fleet($flotten_id);
				$flotten_id = $fleet->getName();

				if($flotten_id && ($fleet->getCurrentType() != 3 || $fleet->isFlyingBack() || array_search($me->getName(), $fleet->getUsersList()) !== 0))
					$flotten_id = false;
			}

			if(!$flotten_id)
			{
?>
<p class="error"><?=L::h(_("Ungültigen Angriff ausgewählt."))?></p>
<?php
				$gui->end();
				exit();
			}

			if(isset($_POST["fleet_passwd"]) && ($_POST["fleet_passwd"] = trim($_POST["fleet_passwd"])) != $fleet->password())
			{
				try
				{
					$me->resolveFleetPasswd($_POST["fleet_passwd"]);
					$fleet->password(trim($_POST["fleet_passwd"]));
				}
				catch(UserException $e)
				{
?>
<p class="error"><?=L::h(_("Dieses Passwort wurde schon für eine andere Flotte verwendet."))?></p>
<?php
				}
			}
			$passwd = $fleet->password();
?>
<p class="buendnisangriff-beschreibung-1"><?=L::h(_("Hier können Sie der ausgewählten Flotte ein Flottenpasswort zuweisen, welches es anderen Spielern ermöglicht, Ihrem Angriff eigene Flotten beizusteuern. Möchte ein anderer Spieler dem Flottenverbund beitreten, so muss er im Flottenmenü Ihren Benutzernamen in Verbindung mit dem hier festgelegten Passwort angeben. Übermitteln Sie ihm hierzu das Passwort selbst, zum Beispiel durch eine Nachricht."))?></p>
<p class="buendnisangriff-beschreibung-2"><?=L::h(_("Beachten Sie, dass ein Spieler dem Flottenverbund nicht mehr beitreten kann, wenn seine Flugzeit zum ausgewählten Ziel länger ist als die verbleibende Flugzeit der Flotte."))?></p>
<p class="buendnisangriff-beschreibung-3"><?=L::h(_("Wenn hier kein Passwort eingetragen ist, ist die Flottenverbundfunktion für diese Flotte deaktiviert."))?></p>
<form action="flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($_GET['id']).'&'.global_setting("URL_SUFFIX"))?>" method="post" class="buendnisangriff">
	<dl class="form">
		<dt><label for="i-flottenpasswort"><?=L::h(_("Flottenpasswort&[login/info/flotten_actions.php|1]"))?></label></dt>
		<dd><input type="text" name="fleet_passwd"<?php if($passwd !== null){?> value="<?=htmlspecialchars($passwd)?>"<?php }?><?=L::accesskeyAttr(_("Flottenpasswort&[login/info/flotten_actions.php|1]"))?> /></dd>
	</dl>
	<div class="button"><button type="submit"<?=L::accesskeyAttr(_("Speichern&[login/info/flotten_actions.php|1]"))?>><?=L::h(_("Speichern&[login/info/flotten_actions.php|1]"))?></button></div>
</form>
<?php
			break;
		}
		case "route":
		{
?>
<h2 id="flugroute"><?=L::h(_("Flugroute"))?></h2>
<?php
			if(!isset($_GET['id'])) $flotten_id = false;
			else $flotten_id = $_GET['id'];

			if($flotten_id)
			{
				$fleet = Classes::Fleet($flotten_id);
				$flotten_id = $fleet->getName();

				if($flotten_id && array_search($me->getName(), $fleet->getUsersList()) !== 0)
					$flotten_id = false;
			}

			if(!$flotten_id)
			{
?>
<p class="error"><?=L::h(_("Ungültigen Flug ausgewählt."))?></p>
<?php
				$gui->end();
				exit();
			}
?>
<table class="route paragraph">
	<thead>
		<tr>
			<th class="c-ziel"><?=L::h(_("Ziel"))?></th>
			<th class="c-auftrag"><?=L::h(_("Auftrag"))?></th>
			<th class="c-ankunft"><?=L::h(_("Ankunft"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
			$targets = $fleet->getOldTargets();
			$old_count = count($targets);
			$targets = array_merge($targets, $fleet->getTargets());
			$countdowns = array();
			foreach($targets as $i=>$target)
			{
				$type = $fleet->getTargetType($i);
				if($type[1]) continue;
				$countdowns[$i] = $fleet->getArrival($i);
?>
		<tr id="target-<?=$i?>" class="type-<?=htmlspecialchars($type[0])?><?=$i<$old_count ? " type-weak" : ($i == $old_count ? " active" : "")?>">
			<td class="c-ziel"><?=F::formatPlanetH($target)?></td>
			<td class="c-auftrag"><?=L::h(_("[fleet_".$type[0]."]"))?></td>
			<td class="c-ankunft" id="restbauzeit-<?=$i?>"><?=L::h(sprintf(_("Ankunft: %s"), sprintf(_("%s (Serverzeit)"), date(_("H:i:s, Y-m-d"), $countdowns[$i]))))?></td>
		</tr>
<?php
			}
?>
	</tbody>
</table>
<script type="text/javascript">
// <![CDATA[
<?php
			foreach($countdowns as $i=>$time)
			{
?>
	init_countdown('<?=JS::jsentities($i)?>', <?=$time?>, false, undefined, undefined, function(){var el1=document.getElementById('target-<?=$i?>');el1.className=el1.className.replace(/(\s|^)active(\s|$)/, "$1type-weak$2");var el2=document.getElementById('target-<?=$i+1?>');if(el2)el2.className+=" active";});
<?php
			}
?>
// ]]>
</script>
<?php
			break;
		}
		default:
		{
?>
<p class="error"><?=L::h(_("Ungültige Aktion."))?></p>
<?php
			break;
		}
	}

	$gui->end();
?>
