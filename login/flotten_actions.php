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

	if(!isset($_GET['action']))
		$_GET['action'] = false;

	login_gui::html_head();

	switch($_GET['action'])
	{
		case 'handel':
		{
			if(!isset($_GET['id'])) $flotten_id = false;
			else $flotten_id = $_GET['id'];

			if($flotten_id)
			{
				$fleet = Classes::Fleet($flotten_id);
				if(!$fleet->getStatus()) $flotten_id = false;
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
<p class="error">Ungültigen Transport ausgewählt.</p>
<?php
				login_gui::html_foot();
				exit();
			}

			if(!$me->permissionToAct())
			{
?>
<p class="error">Sie haben keine Berechtigung, Flottenaufträge aufzugeben.</p>
<?php
				login_gui::html_foot();
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
			foreach($fleet->getUsersList() as $username)
			{
				$verb = $me->isVerbuendet($username);

				if($username == $_SESSION['username']) $class = 'eigen';
				elseif($verb) $class = 'verbuendet';
				else $class = 'fremd';
?>
<form action="flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($_GET['id']).'&'.urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="handel <?=$class?>">
	<fieldset>
		<legend><a href="help/playerinfo.php?player=<?=htmlspecialchars(urlencode($username).'&'.urlencode(session_name()).'='.urlencode(session_id()))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($username)?></a></legend>
<?php
				$trans = $fleet->getTransportCapacity($username);
				$handel = $fleet->getHandel($username);
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

					$new_handel = array(fit_to_max($new_handel[0], $max[0]), fit_to_max($new_handel[1], $max[1]));

					if($type == 'set') $status = $fleet->setHandel($_POST['handel_username'], $new_handel[0], $new_handel[1]);
					else $status = $fleet->addHandel($_POST['handel_username'], $new_handel[0], $new_handel[1]);
					if($status)
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
				}

				if($verb)
				{
					$mess1 = 'Sie können das Handelsangebot zu diesem Spieler ändern, da Sie mit ihm verbündet sind.';
					if($username == $_SESSION['username']) $mess2 = 'Die Flotte hat Platz für %1$s Tonnen Rohstoffe (%3$s verbleibend) und %2$s Roboter (%4$s verbleibend).';
					else $mess2 = 'Die Flotte hat Platz für %1$s Tonnen Rohstoffe (%3$s verbleibend).';
					$input_name = 'set';
					$value = '%u';
					$disabled = '';
					$show_submit = true;
				}
				else
				{
					$mess1 = 'Sie können das bereits eingelagerte Handelsangebot für diesen Spieler nicht ändern, da Sie nicht mit ihm verbündet sind. Sie können nur weitere Rohstoffe einlagern.';
					$mess2 = 'Es verbleibt Platz für %3$s Tonnen Rohstoffe.';
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
		<input type="hidden" name="handel_username" value="<?=utf8_htmlentities($username)?>" />
		<input type="hidden" name="handel_type" value="<?=$input_name?>" />
		<p><?=htmlspecialchars($mess1)?></p>
		<p><?php printf($mess2, ths($trans[0]), ths($trans[1]), ths($remaining_trans[0]), ths($remaining_trans[1]))?></p>
		<table>
			<thead>
				<tr>
					<th class="c-gut">Gut</th>
					<th class="c-einlagern">Einlagern</th>
<?php
				if(!$verb)
				{
?>
					<th class="c-bereits-eingelagert">Bereits eingelagert</th>
<?php
				}
?>
					<th class="c-verfuegbar">Verfügbar</th>
				</tr>
			</thead>
			<tbody>
<?php
				if($trans[0] > 0)
				{
?>
				<tr class="c-carbon">
					<th class="c-gut">Carbon</th>
					<td class="c-einlagern"><input type="text" name="handel[0][0]" value="<?php printf($value, $handel[0][0])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=ths($handel[0][0])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><?=ths($available_ress[0])?></td>
				</tr>
				<tr class="c-aluminium">
					<th class="c-gut">Aluminium</th>
					<td class="c-einlagern"><input type="text" name="handel[0][1]" value="<?php printf($value, $handel[0][1])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=ths($handel[0][1])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><?=ths($available_ress[1])?></td>
				</tr>
				<tr class="c-wolfram">
					<th class="c-gut">Wolfram</th>
					<td class="c-einlagern"><input type="text" name="handel[0][2]" value="<?php printf($value, $handel[0][2])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=ths($handel[0][2])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><?=ths($available_ress[2])?></td>
				</tr>
				<tr class="c-radium">
					<th class="c-gut">Radium</th>
					<td class="c-einlagern"><input type="text" name="handel[0][3]" value="<?php printf($value, $handel[0][3])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=ths($handel[0][3])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><?=ths($available_ress[3])?></td>
				</tr>
				<tr class="c-tritium">
					<th class="c-gut">Tritium</th>
					<td class="c-einlagern"><input type="text" name="handel[0][4]" value="<?php printf($value, $handel[0][4])?>"<?=$disabled?> /></td>
<?php
					if(!$verb)
					{
?>
					<td class="c-bereits-eingelagert"><?=ths($handel[0][4])?></td>
<?php
					}
?>
					<td class="c-verfuegbar"><?=ths($available_ress[4])?></td>
				</tr>
<?php
				}
				if($username == $_SESSION['username'] && $trans[1] > 0)
				{
					foreach($me->getItemsList('roboter') as $id)
					{
						$item_info = $me->getItemInfo($id, 'roboter');
						$h = 0;
						if(isset($handel[1][$id])) $h = $handel[1][$id];
?>
				<tr class="c-ro-<?=utf8_htmlentities($id)?>">
					<th class="c-gut"><?=utf8_htmlentities($item_info['name'])?></th>
					<td class="c-einlagern"><input type="text" name="handel[1][<?=$id?>]" value="<?=utf8_htmlentities($h)?>" /></td>
					<td class="c-verfuegbar"><?=ths($available_robs[$id])?></td>
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
					<td colspan="<?=3-$verb?>"><button type="submit">Handel ändern</button></td>
				</tr>
			</tfoot>
<?php
				}
?>
		</table>
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
<p class="nothingtodo">
	Sie haben keine Planetenlesezeichen gespeichert. In der Karte können Sie Lesezeichen anlegen.
</p>
<?php
			}
			else
			{
?>
<fieldset>
	<legend>Lesezeichen verwalten</legend>
	<ul class="shortcuts-verwalten">
<?php
				$i = 0;
				foreach($shortcuts as $shortcut)
				{
					$s_pos = explode(':', $shortcut);
					$galaxy_obj = Classes::Galaxy($s_pos[0]);
					$owner = $galaxy_obj->getPlanetOwner($s_pos[1], $s_pos[2]);
					$s = $shortcut.': ';
					if($owner)
					{
						$s .= $galaxy_obj->getPlanetName($s_pos[1], $s_pos[2]).' (';
						$alliance = $galaxy_obj->getPlanetOwnerAlliance($s_pos[1], $s_pos[2]);
						if($alliance) $s .= '['.$alliance.'] ';
						$s .= $owner.')';
					}
					else $s .= '[unbesiedelt]';
?>
		<li><?=htmlspecialchars($s)?> <span class="aktionen"><?php if($i>0){?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;up=<?=htmlentities(urlencode($shortcut))?>&amp;<?=htmlentities(urlencode(session_name()).'='.session_id())?>" class="hoch">[Hoch]</a><?php } if($i<$count-1){?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;down=<?=htmlentities(urlencode($shortcut))?>&amp;<?=htmlentities(urlencode(session_name()).'='.session_id())?>" class="runter">[Runter]</a><?php }?> &ndash; <a href="flotten_actions.php?action=shortcuts&amp;remove=<?=htmlentities(urlencode($shortcut))?>&amp;<?=htmlentities(urlencode(session_name()).'='.session_id())?>" class="loeschen">[Löschen]</a></span></li>
<?php
					$i++;
				}
?>
	</ul>
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
				if(!$fleet->getStatus()) $flotten_id = false;
				$flotten_id = $fleet->getName();

				if($flotten_id && ($fleet->getCurrentType() != 3 || $fleet->isFlyingBack() || array_search($me->getName(), $fleet->getUsersList()) !== 0))
					$flotten_id = false;
			}

			if(!$flotten_id)
			{
?>
<p class="error">Ungültigen Angriff ausgewählt.</p>
<?php
				login_gui::html_foot();
				exit();
			}

			if(isset($_POST["fleet_passwd"]))
			{
				$passwd = $me->getFleetPasswd($flotten_id);
				$_POST["fleet_passwd"] = trim($_POST["fleet_passwd"]);
				if($_POST["fleet_passwd"] != $passwd && $me->resolveFleetPasswd($_POST["fleet_passwd"]) !== null)
				{
?>
<p class="error">Dieses Passwort wurde schon für eine andere Flotte verwendet.</p>
<?php
				}
				elseif(!$me->changeFleetPasswd($flotten_id, trim($_POST["fleet_passwd"])))
				{
?>
<p class="error">Das Passwort konnte nicht geändert werden.</p>
<?php
				}
			}
			$passwd = $me->getFleetPasswd($flotten_id);
?>
<p class="buendnisangriff-beschreibung-1">Hier können Sie der ausgewählten Flotte ein Flottenpasswort zuweisen, welches es anderen Spielern ermöglicht, Ihrem Angriff eigene Flotten beizusteuern. Möchte ein anderer Spieler dem Flottenverbund beitreten, so muss er im Flottenmenü Ihren Benutzernamen in Verbindung mit dem hier festgelegten Passwort angeben. Übermitteln Sie ihm hierzu das Passwort selbst, zum Beispiel durch eine Nachricht.</p>
<p class="buendnisangriff-beschreibung-2">Beachten Sie, dass ein Spieler dem Flottenverbund nicht mehr beitreten kann, wenn seine Flugzeit zum ausgewählten Ziel länger ist als die verbleibende Flugzeit der Flotte.</p>
<p class="buendnisangriff-beschreibung-3">Wenn hier kein Passwort eingetragen ist, ist die Flottenverbundfunktion für diese Flotte deaktiviert.</p>
<form action="flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($_GET['id']).'&'.urlencode(session_name()).'='.urlencode(session_id()))?>" method="post" class="buendnisangriff">
	<dl>
		<dt><label for="i-flottenpasswort">Flottenpasswort</label></dt>
		<dd><input type="text" name="fleet_passwd"<?php if($passwd !== null){?> value="<?=htmlspecialchars($passwd)?>"<?php }?> /></dd>
	</dl>
	<div><button type="submit">Speichern</button></div>
</form>
<?php
			break;
		}
		default:
		{
?>
<p class="error">Ungültige Aktion.</p>
<?php
			break;
		}
	}

	login_gui::html_foot();
?>
