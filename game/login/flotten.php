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
	 * Losschicken von Flotten.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	 * @todo Temporäre Flotten wieder löschen
	*/
	namespace sua\frontend;

	if(isset($_GET['action'])) define('ignore_action', true);
	require_once('include.php');

	$max_flotten = $me->getMaxParallelFleets();
	$my_flotten = Fleet::userSlots($me->getName());

	class LoginFlottenException extends SuaException
	{
		protected $type = 0;

		static $TYPE_NONE = 0;
		static $TYPE_FLEETS = 1;
		static $TYPE_TYPE = 2;

		const FORMULAR_FLEETS = 1;
		const FORMULAR_TYPE = 2;
		const FORMULAR_SENT = 3;

		function __construct($message = null, $code = 0, $type = 0)
		{
			$this->type = $type;
			parent::__construct($message, $code);
		}

		function getType()
		{
			return $this->type;
		}

		function getClass()
		{
			switch($this->getCode())
			{
				case 1: return "successful";
				case 2: return "nothingtodo";
				default: return "error";
			}
		}

		function getFormularType()
		{
			if($this->getCode())
			{
				switch($this->getType())
				{
					case self::TYPE_FLEETS: return self::FORMULAR_TYPE;
					case self::TYPE_TYPE: return self::FORMULAR_SENT;
					default: return self::FORMULAR_FLEETS;
				}
			}
			else
			{
				switch($this->getType())
				{
					case self::TYPE_TYPE: return self::FORMULAR_TYPE;
					default: return self::FORMULAR_FLEETS;
				}
			}
		}
	}

	try
	{
		$fast_action = false;
		if(isset($_GET['action_galaxy']) && isset($_GET['action_system']) && isset($_GET['action_planet']) && isset($_GET['action']) && in_array($_GET['action'], array('spionage', 'besiedeln', 'sammeln', 'shortcut')))
		{
			$fast_action = true;
			if($_GET['action'] == 'shortcut')
			{
				$result = $me->addPosShortcut(Planet::fromKoords($_GET['action_galaxy'], $_GET['action_system'], $_GET['action_planet']));
				if($result === 2)
					throw new LoginFlottenException(_('Dieser Planet ist schon in Ihren Lesezeichen.'), 2, LoginFlottenException::TYPE_TYPE);
				elseif($result)
					throw new LoginFlottenException(_('Der Planet wurde zu den Lesezeichen hinzugefügt.'), 1, LoginFlottenException::TYPE_TYPE);
				else
					throw new LoginFlottenException(_('Datenbankfehler.'), 0, LoginFlottenException::TYPE_FLEETS);
			}

			$planet = Planet::fromKoords($_GET['action_galaxy'], $_GET['action_system'], $_GET['action_planet']);
			$planet_owner = $planet->getOwner();

			$_POST['galaxie'] = array($_GET['action_galaxy']);
			$_POST['system'] = array($_GET['action_system']);
			$_POST['planet'] = array($_GET['action_planet']);

			$_POST['speed'] = 1;

			if($_GET['action'] == 'spionage')
			{
				$_POST['auftrag'] = array(Fleet::TYPE_SPIONIEREN);
				$_POST['flotte'] = array('S5' => 1);
				if($planet_owner && !$me->isVerbuendet($planet_owner))
					$_POST['flotte']['S5'] = $me->checkSetting('sonden');
				if($me->getItemLevel('S5', 'schiffe') < 1)
					throw new LoginFlottenException(_("Keine Spionagesonden vorhanden."), 0, LoginFlottenException::TYPE_FLEETS);
			}
			elseif($_GET['action'] == 'besiedeln')
			{
				$_POST['auftrag'] = array(Fleet::TYPE_BESIEDELN);
				$_POST['flotte'] = array('S6' => 1);
				if($me->getItemLevel('S6', 'schiffe') < 1)
					throw new LoginFlottenException(_("Kein Besiedelungsschiff vorhanden."), 0, LoginFlottenException::TYPE_FLEETS);
			}
			elseif($_GET['action'] == 'sammeln')
			{
				$_POST['auftrag'] = array(Fleet::TYPE_SAMMELN);

				$truemmerfeld = $planet->getTruemmerfeld();

				$anzahl = 0;
				if($truemmerfeld !== false)
				{
					# Transportkapazitaet eines Sammlers
					$sammler_info = $me->getItemInfo('S3', 'schiffe', array("trans"));
					$transport = $sammler_info['trans'][0];

					$anzahl = ceil(array_sum($truemmerfeld)/$transport);
				}
				if($anzahl <= 0)
					$anzahl = 1;

				$_POST['flotte'] = array('S3' => $anzahl);

				if($me->getItemLevel('S3', 'schiffe') < 1)
					throw new LoginFlottenException(_("Keine Sammler vorhanden."), 0, LoginFlottenException::TYPE_FLEETS);
			}
		}

		$buendnisflug = (isset($_POST["buendnisflug"]) && $_POST["buendnisflug"]);

		if(!isset($_POST['flotte']) || !is_array($_POST['flotte']) || !$buendnisflug && (!isset($_POST["auftrag"]) || !is_array($_POST["auftrag"]) || !isset($_POST["auftrag"][0]) || !isset($_POST['galaxie']) || !is_array($_POST["galaxie"]) || !isset($_POST['system']) || !is_array($_POST["system"]) || !isset($_POST['planet']) || !is_array($_POST["planet"]) ) || $buendnisflug && (!isset($_POST["buendnis_benutzername"]) || !isset($_POST["buendnis_flottenpasswort"])))
			throw new LoginFlottenException("", 2, LoginFlottenException::TYPE_NONE);
		if(!$me->permissionToAct())
			throw new LoginFlottenException(_("Ihr Benutzeraccount ist gesperrt."), 0, LoginFlottenException::TYPE_FLEETS);
		if($my_flotten >= $max_flotten)
			throw new LoginFlottenException(_("Maximale Flottenzahl erreicht."), 0, LoginFlottenException::TYPE_FLEETS);

		$types_glob = array();
		foreach($_POST['flotte'] as $id=>$anzahl)
		{
			$_POST['flotte'][$id] = $anzahl = floor($anzahl);
			$item_info = $me->getItemInfo($id, 'schiffe', array("level", "types"));
			if(!$item_info)
			{
				unset($_POST['flotte'][$id]);
				continue;
			}
			if($anzahl > $item_info['level'])
				$_POST['flotte'][$id] = $anzahl = $item_info['level'];
			if($anzahl < 1)
			{
				unset($_POST['flotte'][$id]);
				continue;
			}

			foreach($item_info['types'] as $type)
			{
				if(!in_array($type, $types_glob)) $types_glob[] = $type;
			}
		}

		sort($types_glob, SORT_NUMERIC);

		if(count($_POST["flotte"]) <= 0)
			throw new LoginFlottenException(_("Keine vorhandenen Flotten ausgewählt."), 0, LoginFlottenException::TYPE_FLEETS);

		if(!$buendnisflug)
		{
			# Transportkapazitaet und Antriebsstaerke berechnen
			$speed = 0;
			$transport = array(0, 0);
			$ges_count = 0;
			foreach($_POST['flotte'] as $id=>$anzahl)
			{
				$item_info = $me->getItemInfo($id, null, array("speed", "trans"));
				if($speed == 0 || ($item_info['speed'] != 0 && $item_info['speed'] < $speed))
					$speed = $item_info['speed'];

				$transport[0] += $item_info['trans'][0]*$anzahl;
				$transport[1] += $item_info['trans'][1]*$anzahl;
				$ges_count += $anzahl;
			}
		}

		if($buendnisflug)
		{
			$buendnisflug_user = Classes::User($_POST["buendnis_benutzername"]);
			if($buendnisflug_user->getName() == $me->getName())
				throw new LoginFlottenException(_("Ungültiger Benutzername."), 0, LoginFlottenException::TYPE_FLEETS);
			try
			{
				$buendnisflug_id = $buendnisflug_user->resolveFleetPasswd($_POST["buendnis_flottenpasswort"]);
			}
			catch(UserException $e)
			{
				throw new LoginFlottenException(_("Ungültiges Flottenpasswort."), 0, LoginFlottenException::TYPE_FLEETS);
			}

			$buendnisflug_fleet = Classes::Fleet($buendnisflug_id);
			if($buendnisflug_fleet->userExists($me->getName()))
				throw new LoginFlottenException(_("Sie sind bereits Teil des Bündnisflugs."));
			$target_koords = array(explode(":", $buendnisflug_fleet->getCurrentTarget()));
			$target_planets = array(Classes::Planet(Classes::System(Classes::Galaxy($target_koords[0]), $target_koords[1]), $target_koords[2]));
			$auftraege = array($buendnisflug_fleet->getCurrentType());
		}
		else
		{
			$types = $auftraege = $planet_owner = $target_koords = $target_planets = array();
			reset($_POST["auftrag"]);
			for($i=0; list($k,$v) = each($_POST["auftrag"]); $i++)
			{
				if(!isset($_POST["galaxie"][$k]) || !isset($_POST["system"][$k]) || !isset($_POST["planet"][$k]) || !preg_match("/^[1-9]([0-9]*)$/", $_POST['galaxie'][$k]) || !preg_match("/^[1-9]([0-9]*)$/", $_POST['system'][$k]) || !preg_match("/^[1-9]([0-9]*)$/", $_POST['planet'][$k]))
					throw new LoginFlottenException(_("Ungültige Koordinaten."), 0, $i == 0 ? LoginFlottenException::TYPE_FLEETS : LoginFlottenException::TYPE_TYPE);

				if(in_array(array($_POST["galaxie"][$k], $_POST["system"][$k], $_POST["planet"][$k]), $target_koords))
					throw new LoginFlottenException(_("Die Flotte darf nicht zweimal zu einem Planeten fliegen."), 0, $i == 0 ? LoginFlottenException::TYPE_FLEETS : LoginFlottenException::TYPE_TYPE);

				$auftraege[$i] = $v;
				$target_koords[$i] = array($_POST["galaxie"][$k], $_POST["system"][$k], $_POST["planet"][$k]);
				try
				{
					$target_planets[$i] = Classes::Planet(Classes::System(Classes::Galaxy($target_koords[$i][0]), $target_koords[$i][1]), $target_koords[$i][2]);
				}
				catch(PlanetException $e)
				{
					throw new LoginFlottenException(_("Diesen Planeten gibt es nicht."), 0, $i == 0 ? LoginFlottenException::TYPE_FLEETS : LoginFlottenException::TYPE_TYPE);
				}

				$owner = $target_planets[$i]->getOwner();
				$planet_owner[$i] = $owner ? Classes::User($owner) : null;

				$types[$i] = array_flip($types_glob);
				if($planet_owner[$i] && isset($types[$i][Fleet::TYPE_BESIEDELN])) # Planet besetzt, Besiedeln nicht moeglich
					unset($types[$i][Fleet::TYPE_BESIEDELN]);
				if(!$planet_owner[$i]) # Planet nicht besetzt
				{
					if(isset($types[$i][Fleet::TYPE_ANGRIFF])) # Angriff nicht moeglich
						unset($types[$i][Fleet::TYPE_ANGRIFF]);
					if(isset($types[$i][Fleet::TYPE_TRANSPORT])) # Transport nicht moeglich
						unset($types[$i][Fleet::TYPE_TRANSPORT]);
					if(isset($types[$i][Fleet::TYPE_STATIONIEREN])) # Stationieren nicht moeglich
						unset($types[$i][Fleet::TYPE_STATIONIEREN]);

					if(!$me->checkPlanetCount()) # Planetenlimit erreicht, Besiedeln nicht moeglich
						unset($types[$i][Fleet::TYPE_BESIEDELN]);
				}

				$truemmerfeld = $target_planets[$i]->getTruemmerfeld();
				if(($truemmerfeld === false || array_sum($truemmerfeld) <= 0) && isset($types[$i][2]))
					unset($types[$i][Fleet::TYPE_SAMMELN]); # Kein Truemmerfeld, Sammeln nicht moeglich

				if($me->getPosString() == implode(":", $target_koords[$i]) || ($planet_owner[$i] && $planet_owner[$i]->umode()))
				{ # Selber Planet / Urlaubsmodus, nur Sammeln
					if($truemmerfeld && isset($types[$i][2]))
						$types[$i] = array(Fleet::TYPE_SAMMELN => 0);
					else
						$types[$i] = array();
				}
				elseif($planet_owner && $planet_owner[$i]->getName() == $me->getName())
				{ # Eigener Planet
					if(isset($types[$i][Fleet::TYPE_ANGRIFF])) # Angriff nicht moeglich
						unset($types[$i][Fleet::TYPE_ANGRIFF]);
					if(isset($types[$i][Fleet::TYPE_SPIONIEREN])) # Spionage nicht moeglich
						unset($types[$i][Fleet::TYPE_SPIONIEREN]);
				}
				else
				{ # Fremder Planet
					if($planet_owner[$i] && !$me->isVerbuendet($planet_owner[$i]->getName()) && isset($types[$i][Fleet::TYPE_STATIONIEREN])) # Fremdstationierung nur bei Verbuendeten
						unset($types[$i][Fleet::TYPE_STATIONIEREN]);
					if($planet_owner[$i] && $me->isVerbuendet($planet_owner[$i]->getName()) && isset($types[$i][Fleet::TYPE_ANGRIFF])) # Verbuendet, Angriff nicht moeglich
						unset($types[$i][Fleet::TYPE_ANGRIFF]);
				}

				if(Config::fleets_locked()) # Flottensperre
				{
					if($planet_owner[$i] && !$me->isVerbuendet($planet_owner[$i]->getName()) && isset($types[$i][Fleet::TYPE_SPIONIEREN])) # Feindliche Spionage nicht moeglich
						unset($types[$i][Fleet::TYPE_SPIONIEREN]);
					if(isset($types[$i][Fleet::TYPE_ANGRIFF])) # Angriff nicht erlaubt
						unset($types[$i][Fleet::TYPE_ANGRIFF]);
				}

				if(count($types[$i]) <= 0)
					throw new LoginFlottenException(_("Sie haben nicht die richtigen Schiffe ausgewählt, um diesen Planeten anzufliegen."), 0, $i == 0 ? LoginFlottenException::TYPE_FLEETS : LoginFlottenException::TYPE_TYPE);

				$types[$i] = array_flip($types[$i]);

				if(!$buendnisflug && $v && !in_array($v, $types[$i]))
					throw new LoginFlottenException(_("Ungültigen Auftrag ausgewählt."), 0, LoginFlottenException::TYPE_TYPE);

				if($v == Fleet::TYPE_STATIONIEREN || $v == Fleet::TYPE_BESIEDELN)
					break;
			}
		}

		if(!$buendnisflug && in_array(0, $_POST["auftrag"]))
			throw new LoginFlottenException("", 2, LoginFlottenException::TYPE_FLEETS);

		$this_punkte = $me->getScores();
		$this_punkte_all = $me->getScores();
		if($buendnisflug)
		{
			foreach($buendnisflug_fleet->getUsersList() as $attacking_user)
			{
				$attacking_user_obj = Classes::User($attacking_user);
				$this_punkte_all += $attacking_user_obj->getScores();
			}
		}

		$that_user = array();
		foreach($auftraege as $i=>$auftrag)
		{
			if($planet_owner[$i] && (($auftrag == Fleet::TYPE_ANGRIFF || $auftrag == Fleet::TYPE_SPIONIEREN) && !$planet_owner[$i]->userLocked() || $auftrag == Fleet::TYPE_STATIONIEREN && $planet_owner[$i]->getName() != $me->getName()) && !file_exists(global_setting("DB_NONOOBS")))
			{
				# Anfaengerschutz ueberpruefen
				$that_punkte = $planet_owner[$i]->getScores();

				if($that_punkte > $this_punkte && $that_punkte*0.05 > $this_punkte)
					throw new LoginFlottenException(_("Das Imperium dieses Spielers ist so groß, dass Ihre Sensoren beim Versuch, einen Anflugspunkt auszumachen, durcheinanderkommen. (Aka Anfängerschutz.)"), 0, LoginFlottenException::TYPE_TYPE);
				if($that_punkte < $this_punkte_all && $that_punkte < $this_punkte_all*0.05)
					throw new LoginFlottenException(_("Dieser Spieler ist noch so klein, dass Ihre Sensoren das Ziel nicht ausmachen und deshalb den Flugkurs nicht berechnen können. (Aka Anfängerschutz.)"), 0, LoginFlottenException::TYPE_TYPE);
			}
		}

		if(!$buendnisflug)
		{
			$fleet_obj = Classes::Fleet(Fleet::create());
		}
		else
			$fleet_obj = $buendnisflug_fleet;

		if($my_flotten + $fleet_obj->getNeededSlots($me->getName()) > $max_flotten)
			throw new LoginFlottenException(_("Flottenlimit überschritten."), 0, LoginFlottenException::TYPE_TYPE);

		if(!$buendnisflug)
		{
			# Geschwindigkeitsfaktor
			if(!isset($_POST['speed']) || $_POST['speed'] < 0.05 || $_POST['speed'] > 1)
				$_POST['speed'] = 1;

			foreach($auftraege as $i=>$auftrag)
				$fleet_obj->addTarget($target_planets[$i], $auftrag, false);
			if($auftrag != Fleet::TYPE_STATIONIEREN && $auftrag != Fleet::TYPE_BESIEDELN)
				$fleet_obj->addTarget($me->getPlanet(), $auftrag, true);
		}

		if($buendnisflug)
			$fleet_obj->addUser($me->getName(), $me->getPosObj());
		else
			$fleet_obj->addUser($me->getName(), $me->getPosObj(), $_POST['speed']);

		foreach($_POST['flotte'] as $id=>$anzahl)
			$fleet_obj->addFleet($id, $anzahl, $me->getName());

		$tritium = $fleet_obj->calcNeededTritium($me->getName());

		$ress = $me->getRess();
		if($ress[4] < $tritium)
			throw new LoginFlottenException(_('Nicht genug Tritium vorhanden.'), 0, LoginFlottenException::TYPE_TYPE);

		$me->subtractRess(array(0, 0, 0, 0, $tritium));

		$ress = $me->getRess();
		if(($auftrag == 1 || $auftrag == 4 || $auftrag == 6))
		{
			if(!isset($_POST['transport'])) $_POST['transport'] = array(0,0,0,0,0);
			if(!isset($_POST['rtransport'])) $_POST['rtransport'] = array();
			if($_POST['transport'][0] > $ress[0]) $_POST['transport'][0] = $ress[0];
			if($_POST['transport'][1] > $ress[1]) $_POST['transport'][1] = $ress[1];
			if($_POST['transport'][2] > $ress[2]) $_POST['transport'][2] = $ress[2];
			if($_POST['transport'][3] > $ress[3]) $_POST['transport'][3] = $ress[3];
			if($_POST['transport'][4] > $ress[4]) $_POST['transport'][4] = $ress[4];

			foreach($_POST['rtransport'] as $id=>$anzahl)
			{
				if($anzahl > $me->getItemLevel($id, 'roboter'))
					$_POST['rtransport'][$id] = $me->getItemLevel($id, 'roboter');
			}
			if($planet_owner[$i] && $planet_owner[$i]->getName() == $me->getName())
				$fleet_obj->addTransport($me->getName(), $_POST['transport'], $_POST['rtransport']);
			else $fleet_obj->addTransport($me->getName(), $_POST['transport']);
			list($_POST['transport'], $_POST['rtransport']) = $fleet_obj->getTransport($me->getName());
		}
		else
		{
			$_POST['transport'] = array(0,0,0,0,0);
			$_POST['rtransport'] = array();
		}

		# Flotten abziehen
		foreach($_POST['flotte'] as $id=>$anzahl)
			$me->changeItemLevel($id, -$anzahl, 'schiffe');

		# Rohstoffe abziehen
		$me->subtractRess($_POST['transport'], false);

		# Roboter abziehen
		foreach($_POST['rtransport'] as $id=>$anzahl)
			$me->changeItemLevel($id, -$anzahl, 'roboter');

		$fleet_obj->start();

		throw new LoginFlottenException(_("Die Flotte wurde versandt."), 1, LoginFlottenException::TYPE_TYPE);
	}
	catch(LoginFlottenException $e)
	{
		if(defined("ajax"))
		{ # Nur die Fehlermeldung zurückliefern
			if(!$e->getMessage())
				return array("error", _("Fehler."));
			return array($e->getClass(), $e->getMessage());
		}

		if($fast_action && $e->getCode())
		{ # Bei Erfolg nichts anzeigen
			header($_SERVER['SERVER_PROTOCOL'].' 204 No Content');
			ob_end_clean();
			die();
		}

		$gui->init();
?>
<h2><?=l::h(_("Flotten"))?></h2>
<?php
		switch($e->getFormularType())
		{

#################################################################################
### login/flotten.php|1
#################################################################################

			case LoginFlottenException::FORMULAR_FLEETS:
			{
?>
<h3 class="strong"><?=l::h(_("Flotte versenden"))?></h3>
<p class="flotte-anzahl<?=($my_flotten >= $max_flotten) ? ' voll' : ''?>">
	<?=l::h(sprintf(ngettext("Sie haben derzeit %s von %s möglichen Flotte unterwegs.", "Sie haben derzeit %s von %s möglichen Flotten unterwegs.", $max_flotten), F::ths($my_flotten), F::ths($max_flotten)))?><br />
	<?=l::_i(h(_("Bauen Sie [item_F0_def_acc] aus, um die maximale Anzahl zu erhöhen.")))."\n"?>
</p>
<?php
				$this_pos = $me->getPos();
				if(isset($_GET['action_galaxy'])) $this_pos[0] = $_GET['action_galaxy'];
				if(isset($_GET['action_system'])) $this_pos[1] = $_GET['action_system'];
				if(isset($_GET['action_planet'])) $this_pos[2] = $_GET['action_planet'];
?>
<form action="flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="flotte-versenden">
<?php
				if($e->getMessage())
				{
?>
	<p class="<?=htmlspecialchars($e->getClass())?>"><?=l::h($e->getMessage())?></p>
<?php
				}

				if($my_flotten < $max_flotten && $me->permissionToAct())
				{
?>
	<fieldset class="flotte-koords" id="fieldset-flotte-koords">
		<legend><input type="radio" name="buendnisflug" value="0"<?php if(!$buendnisflug){?> checked="checked"<?php }?> id="i-eigenes-ziel" onclick="update_active_fieldset()" onchange="onclick()" /> <label for="i-eigenes-ziel">Eigenes Ziel</label></legend>
		<dl class="form">
			<dt class="c-ziel"><label for="ziel-galaxie"><?=l::h(_("&Ziel[login/flotten.php|2]"))?></label></dt>
			<dd class="c-ziel"><input type="text" id="ziel-galaxie" name="galaxie[0]" value="<?=htmlspecialchars(isset($_POST["galaxie"]) && isset($_POST["galaxie"][0]) ? $_POST["galaxie"][0] : $this_pos[0])?>" onfocus="document.getElementById('i-eigenes-ziel').checked=true; update_active_fieldset();" title="<?=l::h("Ziel: Galaxie")?>"<?=l::accesskey_attr(_("&Ziel[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="<?=strlen(Galaxy::getNumber())?>" class="number number-koords" />:<input type="text" id="ziel-system" name="system[0]" value="<?=htmlspecialchars(isset($_POST["system"]) && isset($_POST["system"][0]) ? $_POST["system"][0] : $this_pos[1])?>" onfocus="document.getElementById('i-eigenes-ziel').checked=true; update_active_fieldset();" title="<?=l::h(_("Ziel: System&[login/flotten.php|2]"), false)?>"<?=l::accesskey_attr(_("Ziel: System&[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="3" class="number number-koords" />:<input type="text" id="ziel-planet" name="planet[0]" value="<?=htmlspecialchars(isset($_POST["planet"]) && isset($_POST["planet"][0]) ? $_POST["planet"][0] : $this_pos[2])?>" onfocus="document.getElementById('i-eigenes-ziel').checked=true; update_active_fieldset();" title="<?=l::h(_("Ziel: Planet&[login/flotten.php|2]"), false)?>"<?=l::accesskey_attr(_("Ziel: Planet&[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);" onchange="syncronise(true);" onkeyup="syncronise(true);" maxlength="2" class="number number-koords" /></dd>
			<script type="text/javascript">
				// <![CDATA[
				document.write('<dt class="c-planet"><label for="ziel-planet-wahl"><?=l::h(_("Pla&net[login/flotten.php|2]"))?></label></dt>');
				document.write('<dd class="c-planet">');
				document.write('<select id="ziel-planet-wahl"<?=l::accesskey_attr(_("Pla&net[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onchange="syncronise(false);" onkeyup="syncronise(false);" onfocus="document.getElementById(\'i-eigenes-ziel\').checked=true; update_active_fieldset();">');
				document.write('<option value=""><?=l::h(_("Benutzerdefiniert"))?></option>');
<?php
					$shortcuts = $me->getPosShortcutsList();
					if(count($shortcuts) > 0)
					{
?>
				document.write('<optgroup label="<?=l::h(_("Lesezeichen"))?>">');
<?php
						foreach($shortcuts as $shortcut)
						{
							$owner = $shortcut->getOwner();
							$s = $shortcut.': ';
							if($owner)
							{
								$user_obj = Classes::User($owner);
								$s .= $shortcut->getName().' (';
								$alliance = Alliance::getUserAlliance($owner);
								if($alliance) $s .= '['.$alliance.'] ';
								$s .= $owner.')';
							}
							else $s .= "["._('unbesiedelt')."]";
?>
				document.write('<option value="<?=htmlspecialchars($shortcut)?>"><?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($s))?></option>');
<?php
						}
?>
				document.write('</optgroup>');
<?php
					}
?>
				document.write('<optgroup label="<?=l::h(_("Eigene Planeten"))?>">');
<?php
					$planets = $me->getPlanetsList();
					$active_planet = $me->getActivePlanet();
					foreach($planets as $planet)
					{
						$me->setActivePlanet($planet);
?>
				document.write('<option value="<?=htmlspecialchars($me->getPosString())?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=l::h(vsprintf(_("%d:%d:%d"), $me->getPos()))?>: <?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($me->planetName()))?></option>');
<?php
					}
					$me->setActivePlanet($active_planet);
?>
				document.write('</select>');
				document.write('</optgroup>');
<?php
					if(count($shortcuts) > 0)
					{
?>
				document.write('<ul class="actions"><li><a href="info/flotten_actions.php?action=shortcuts&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="lesezeichen-verwalten-link"<?=l::accesskey_attr(_("Lesezeichen verwalten&[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>"><?=l::h(_("Lesezeichen verwalten&[login/flotten.php|2]"))?></a></li></ul>');
<?php
					}
?>
				document.write('</dd>');
			// ]]>
			</script>
		</dl>
	</fieldset>
	<fieldset class="buendnisflug" id="fieldset-buendnisflug">
		<legend><input type="radio" name="buendnisflug" value="1"<?php if($buendnisflug){?> checked="checked"<?php }?> id="i-buendnisflug" onclick="update_active_fieldset()" onchange="onclick()" tabindex="<?=$tabindex++?>" /> <label for="i-buendnisflug"><?=l::h(_("Bündnisflug"))?></label></legend>
		<dl class="form">
			<dt class="c-benutzername"><label for="i-buendnis-benutzername"><?=l::h(_("Benutzername&[login/flotten.php|2]"))?></label></dt>
			<dd class="c-benutzername"><input type="text" id="i-buendnis-benutzername"<?=l::accesskey_attr(_("Benutzername&[login/flotten.php|2]"))?> name="buendnis_benutzername"<?php if(isset($_POST["buendnis_benutzername"])){?> value="<?=htmlspecialchars($_POST["buendnis_benutzername"])?>"<?php }?> onfocus="document.getElementById('i-buendnisflug').checked=true; update_active_fieldset();" tabindex="<?=$tabindex++?>" /></dd>

			<dt class="c-passwort"><label for="i-buendnis-flottenpasswort"><?=l::h(_("Flottenpasswort&[login/flotten.php|2]"))?></label></dt>
			<dd class="c-passwort"><input type="text" id="i-buendnis-flottenpasswort"<?=l::accesskey_attr(_("Flottenpasswort&[login/flotten.php|2]"))?> name="buendnis_flottenpasswort"<?php if(isset($_POST["buendnis_flottenpasswort"])){?> value="<?=htmlspecialchars($_POST["buendnis_flottenpasswort"])?>"<?php }?> onfocus="document.getElementById('i-buendnisflug').checked=true; update_active_fieldset();" tabindex="<?=$tabindex++?>" /></dd>
		</dl>
	</fieldset>
	<script type="text/javascript">
	// <![CDATA[
		function update_active_fieldset()
		{
			var active,inactive;
			if(document.getElementById('i-buendnisflug').checked)
			{
				inactive = document.getElementById("fieldset-flotte-koords");
				active = document.getElementById("fieldset-buendnisflug");
			}
			else
			{
				active = document.getElementById("fieldset-flotte-koords");
				inactive = document.getElementById("fieldset-buendnisflug");
			}
			inactive.className = inactive.className.replace(/ active/, "");
			active.className = active.className.replace(/ active/, "") + " active";
		}

		function syncronise(input_select)
		{
			var select_obj = document.getElementById('ziel-planet-wahl');
			if(!input_select)
			{
				var pos = select_obj.options[select_obj.selectedIndex].value;
				if(pos != '')
				{
					pos = pos.split(/:/);
					document.getElementById('ziel-galaxie').value = pos[0];
					document.getElementById('ziel-system').value = pos[1];
					document.getElementById('ziel-planet').value = pos[2];
				}
			}
			else
			{
				var pos = new Array(3);
				pos[0] = document.getElementById('ziel-galaxie').value;
				pos[1] = document.getElementById('ziel-system').value;
				pos[2] = document.getElementById('ziel-planet').value;
				pos = pos.join(':');

				var one = false;
				for(var sindex=0; sindex<select_obj.options.length; sindex++)
				{
					if(pos == select_obj.options[sindex].value)
					{
						select_obj.selectedIndex = sindex;
						one = true;
						break;
					}
				}

				if(!one)
					select_obj.selectedIndex = 0;
			}
		}

		syncronise(true);
		update_active_fieldset();

		activate_users_list(document.getElementById("i-buendnis-benutzername"));
		// ]]>
	</script>
<?php
				}
?>
	<fieldset class="flotte-schiffe">
		<legend><?=l::h(_("Schiffe"))?></legend>
		<dl class="categories">
<?php
				$i = 0;
				foreach($me->getItemsList('schiffe') as $id)
				{
					if($me->getItemLevel($id, 'schiffe') < 1) continue;
					$item_info = $me->getItemInfo($id, 'schiffe', array("level", "name"));
?>
			<dt><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item_info['name'])?></a> <a onclick="document.getElementById('i-flotte-<?=JS::jsentities($id)?>').value=<?=$item_info["level"]?>;" class="vorhanden">(<?=l::h(sprintf(_("%s vorhanden"), F::ths($item_info['level'])))?>)</a></dt>
			<dd><input type="text" name="flotte[<?=htmlspecialchars($id)?>]" id="i-flotte-<?=htmlspecialchars($id)?>" value="<?=htmlspecialchars(isset($_POST["flotte"]) && isset($_POST["flotte"][$id]) ? $_POST["flotte"][$id] : 0)?>" class="number number-items" tabindex="<?=$tabindex++?>"<?=($my_flotten >= $max_flotten || !$me->permissionToAct()) ? ' readonly="readonly"' : ''?> /></dd>
<?php
					$i++;
				}
?>
		</dl>
	</fieldset>
<?php
				if($i>0 && $my_flotten < $max_flotten && $me->permissionToAct())
				{
?>
	<div class="button"><input type="hidden" name="auftrag[0]" value="0" /><button type="submit"<?=l::accesskey_attr(_("&Weiter[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>"><?=l::h(_("&Weiter[login/flotten.php|2]"))?></button></div>
<?php
				}
?>
</form>
<?php
				if(isset($_POST["callback_foreign"]) && is_array($_POST["callback_foreign"]))
				{
					foreach($_POST["callback_foreign"] as $coords=>$is)
					{
						if(is_array($is))
						{
							foreach(array_keys($is) as $i)
								$me->callBackForeignFleet($coords, $i);
						}
					}
				}

				$foreign_users = $me->getForeignUsersList();
				if(count($foreign_users) > 0)
				{
?>
<h3 id="fremdstationierungen-auf-diesem-planeten" class="strong"><?=l::h(_("Fremdstationierungen auf diesem Planeten"))?></h3>
<?php
					foreach($foreign_users as $user)
					{
						$fleets = array();
						foreach($me->getForeignFleetsList($user) as $fi)
							$fleets = Item::iadd($fleets, $fi[0]);
						Item::isort($fleets);
?>
<fieldset>
	<legend><?=htmlspecialchars($user)?></legend>
<?php
						Item::makeItemList($fleets, 1);
?>
</fieldset>
<?php
					}
				}

				$foreign_coords = $me->getMyForeignFleets();
				if(count($foreign_coords) > 0)
				{
?>
<h3 id="ihre-fremdstationierungen" class="strong"><?=l::h(_("Ihre Fremdstationierungen"))?></h3>
<fieldset>
<?php
					$me->cacheActivePlanet();
					foreach($foreign_coords as $coords)
					{
						$user_obj = Classes::User($coords->getOwner());
						$user_obj->cacheActivePlanet();
						$user_obj->setActivePlanet($user_obj->getPlanetByPos($coords));

						$fleet = $user_obj->getForeignFleetsList($me->getName());
						foreach($fleet as $i=>$fi)
						{
							$me->setActivePlanet($me->getPlanetByPos($fi[1]));
?>
	<legend><?=F::formatPlanet($coords)?></legend>
<?php
							Item::makeItemList($fi[0], 1);
?>
	<form action="flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>#fremdstationierungen" method="post" class="ihre-fremdstationierungen"><div class="button"><button type="submit" name="callback_foreign[<?=htmlspecialchars($coords)?>][<?=htmlspecialchars($i)?>]" tabindex="<?=$tabindex++?>"><?=l::h(sprintf(_("Zurückrufen zum Planeten %s"), sprintf(_("„%s“ (%s)"), $me->planetName(), vsprintf(_("%d:%d:%d"), $me->getPos()))))?></button></div></form>
<?php
						}
						$user_obj->restoreActivePlanet();
					}
					$me->restoreActivePlanet();
?>
</fieldset>
<?php
				}

				break;
			}

#################################################################################
### login/flotten.php|2
#################################################################################

			case LoginFlottenException::FORMULAR_TYPE:
			{
				$fleet_obj = Classes::Fleet(Fleet::create());
				$fleet_obj->addUser($me->getName(), $me->getPosObj());
				foreach($_POST['flotte'] as $id=>$anzahl)
					$fleet_obj->addFleet($id, $anzahl, $me->getName());
				$tritium_all = array();
				foreach($target_planets as $i=>$planet)
				{
					$fleet_obj->addTarget($planet, Fleet::TYPE_NULL, false);
					$tritium_all[$i] = $fleet_obj->calcNeededTritium($me->getName());
				}
				$tritium = $tritium_all[$i];

				$this_ress = $me->getRess();
				$transport = $fleet_obj->getTransportCapacity($me->getName());

				# Kein Robotertransport zu fremden Planeten
				if($planet_owner[0] && $planet_owner[0]->getName() != $me->getName()) $transport[1] = 0;
?>
<form action="flotten.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="flotte-versenden-2" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'<?=JS::jsentities(_("Doppelklickschutz: Sie haben ein zweites Mal auf „Absenden“ geklickt. Dadurch wird Ihre Flotte auch zweimal abgesandt (sofern die nötigen Schiffe verfügbar sind). Sind Sie sicher, dass Sie diese Aktion durchführen wollen?"))?>\');');">
<?php
				if($e->getMessage())
				{
?>
	<p class="<?=htmlspecialchars($e->getClass())?>"><?=l::h($e->getMessage())?></p>
<?php
				}

				$time = array();
				$distance = 0;
				$last_target = $me->getPosObj();

				$i = 0;
				foreach($fleet_obj->getTargetsList() as $id=>$target)
				{
					$distance += Fleet::getDistance($last_target, $target);
					$last_target = $target;
					$time[$i] = $fleet_obj->getArrival($id)-time();
					$time_string = '';
					if($time[$i] >= 86400)
					{
						$time_string .= floor($time[$i]/86400).h(_("[unit_separator]")).'<abbr title="'.h(_("Tage")).'">d</abbr>';
						$time2 = $time[$i]%86400;
					}
					else
						$time2 = $time[$i];
					$time_string .= F::add_nulls(floor($time2/3600), 2).':'.F::add_nulls(floor(($time2%3600)/60), 2).':'.F::add_nulls(($time2%60), 2);
?>
	<fieldset id="target-<?=$i?>">
		<legend><?=Planet::format($target)?> &ndash; <?=$planet_owner[$i] ? htmlspecialchars($target->getPlanetName()).' <span class="playername">('.htmlspecialchars($planet_owner[$i]).')</span>' : 'Unbesiedelt'?></legend>
		<dl>
			<dt class="c-entfernung"><?=l::h(_("Entfernung"))?></dt>
			<dd class="c-entfernung"><?=F::ths($distance)?><?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(ngettext("Orbit", "Orbits", $distance))?>"><?=l::h(_("Or"))?></abbr></dd>
<?php
					if($i == 0)
					{
?>

			<dt class="c-antrieb"><?=l::h(_("Antrieb"))?></dt>
			<dd class="c-antrieb"><?=F::ths($speed)?><?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $speed))?>"><?=l::h(_("µOr⁄s²"))?></abbr></dd>

			<dt class="c-tritiumverbrauch"><?=l::h(_("Tritiumverbrauch"))?></dt>
			<dd class="c-tritiumverbrauch <?=($this_ress[4] >= $tritium) ? 'genug' : 'fehlend'?>" id="tritium-verbrauch"><?=F::ths($tritium)?><?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(ngettext("Tonne", "Tonnen", $tritium))?>">t</abbr></dd>

			<dt class="c-geschwindigkeit"><label for="speed"><?=l::h(_("Gesch&windigkeit[login/flotten.php|1]"))?></label></dt>
			<dd class="c-geschwindigkeit">
				<select name="speed" id="speed"<?=l::accesskey_attr(_("Gesch&windigkeit[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
						for($j=1,$pr=100; $j>0; $j-=.05,$pr-=5)
						{
?>
					<option value="<?=htmlspecialchars($j)?>"<?=(isset($_POST["speed"]) && $_POST["speed"] == "".$j) ? " selected=\"selected\"" : ""?>><?=htmlspecialchars($pr)?><?=l::h(_("[unit_separator]"))?>%</option>
<?php
						}
?>
				</select>
			</dd>
<?php
					}
?>
			<dt class="c-flugzeit"><?=l::h(_("Flugzeit"))?></dt>
			<dd class="c-flugzeit" id="flugzeit-<?=$i?>" title="<?=l::h(sprintf(_("Ankunft: %s"), sprintf(_("%s (Serverzeit)"), date(_("H:i:s, Y-m-d"), time()+$time[$i]))))?>"><?=$time_string?></dd>
<?php
					if($i == 0)
					{
?>

			<dt class="c-transportkapazitaet"><?=l::h(_("Transportkapazität"))?></dt>
			<dd class="c-transportkapazitaet"><?=F::ths($transport[0])?><?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(ngettext("Tonne", "Tonnen", $transport[0]))?>">t</abbr>, <?=F::ths($transport[1])?>&nbsp;<?=l::h(ngettext("Roboter", "Roboter", $transport[1]))?></dd>

			<script type="text/javascript">
				// <![CDATA[
				document.write('<dt class="c-verbleibend" id="transport-verbleibend-dt"><?=l::h(_("Verbleibend"))?></dt>');
				document.write('<dd class="c-verbleibend" id="transport-verbleibend-dd"><?=F::ths($transport[0])?><?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(ngettext("Tonne", "Tonnen", $transport[0]))?>">t</abbr>, <?=F::ths($transport[1])?>&nbsp;<?=l::h(ngettext("Roboter", "Roboter", $transport[1]))?></dd>');
				// ]]>
			</script>
<?php
					}
?>

			<dt class="c-auftrag"><label for="auftrag-<?=$i?>"><?=l::h(_("A&uftrag[login/flotten.php|1]"))?></label></dt>
			<dd class="c-auftrag">
				<select name="auftrag[<?=$i?>]" id="auftrag-<?=$i?>"<?=l::accesskey_attr(_("A&uftrag[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onchange="recalc_values();" onkeyup="recalc_values();">
<?php
					foreach($types[$i] as $type)
					{
?>
					<option value="<?=htmlspecialchars($type)?>"<?=($auftrag == $type) ? " selected=\"selected\"" : ""?>><?=l::h(_("[fleet_".$type."]"))?></option>
<?php
					}
?>
				</select>
			</dd>
<?php
					if($i == 0 && ($transport[0] > 0 || $transport[1] > 0))
					{
?>

			<dt class="c-transport" id="transport-dt"><?=l::h(_("Transport"))?></dt>
			<dd class="c-transport" id="transport-dd">
				<dl class="form">
<?php
						if($transport[0] > 0)
						{
?>
					<dt><label for="transport-carbon"><?=l::h(_("[ress_0]&[login/flotten.php|1]"))?></label></dt>
					<dd><input type="text" name="transport[0]" id="transport-carbon" value="<?=(isset($_POST["transport"]) && isset($_POST["transport"][0])) ? htmlspecialchars($_POST["transport"][0]) : "0"?>" onchange="recalc_values();"<?=l::accesskey_attr(_("[ress_0]&[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('transport-carbon').value=Math.floor(res_now[0]); recalc_values();" class="max" id="fleet-max-0"><?=F::ths($this_ress[0])?></a></dd>

					<dt><label for="transport-aluminium"><?=l::h(_("[ress_1]&[login/flotten.php|1]"))?></label></dt>
					<dd><input type="text" name="transport[1]" id="transport-aluminium" value="<?=(isset($_POST["transport"]) && isset($_POST["transport"][1])) ? htmlspecialchars($_POST["transport"][1]) : "0"?>" onchange="recalc_values();"<?=l::accesskey_attr(_("[ress_1]&[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('transport-aluminium').value=Math.floor(res_now[1]); recalc_values();" class="max" id="fleet-max-1"><?=F::ths($this_ress[1])?></a></dd>

					<dt><label for="transport-wolfram"><?=l::h(_("[ress_2]&[login/flotten.php|1]"))?></label></dt>
					<dd><input type="text" name="transport[2]" id="transport-wolfram" value="<?=(isset($_POST["transport"]) && isset($_POST["transport"][2])) ? htmlspecialchars($_POST["transport"][2]) : "0"?>" onchange="recalc_values();"<?=l::accesskey_attr(_("[ress_2]&[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('transport-wolfram').value=Math.floor(res_now[2]); recalc_values();" class="max" id="fleet-max-2"><?=F::ths($this_ress[2])?></a></dd>

					<dt><label for="transport-radium"><?=l::h(_("[ress_3]&[login/flotten.php|1]"))?></label></dt>
					<dd><input type="text" name="transport[3]" id="transport-radium" value="<?=(isset($_POST["transport"]) && isset($_POST["transport"][3])) ? htmlspecialchars($_POST["transport"][3]) : "0"?>" onchange="recalc_values();"<?=l::accesskey_attr(_("[ress_3]&[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('transport-radium').value=Math.floor(res_now[3]); recalc_values();" class="max" id="fleet-max-3"><?=F::ths($this_ress[3])?></a></dd>

					<dt><label for="transport-tritium"><?=l::h(_("[ress_4]&[login/flotten.php|1]"))?></label></dt>
					<dd><input type="text" name="transport[4]" id="transport-tritium" value="<?=(isset($_POST["transport"]) && isset($_POST["transport"][4])) ? htmlspecialchars($_POST["transport"][4]) : "0"?>" onchange="recalc_values();"<?=l::accesskey_attr(_("[ress_4]&[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('transport-tritium').value=Math.floor(res_now[4]); recalc_values();" class="max" id="fleet-max-4"><?=F::ths($this_ress[4])?></a></dd>
<?php
						}
						if($transport[1] > 0)
						{
							if($transport[0] > 0)
								echo "\n";

							foreach($me->getItemsList('roboter') as $rob)
							{
								$item_info = $me->getItemInfo($rob, 'roboter', array("name", "level"));
?>
					<dt><label for="rtransport-<?=htmlspecialchars($rob)?>"><?=htmlspecialchars($item_info['name'])?></label></dt>
					<dd><input type="text" name="rtransport[<?=htmlspecialchars($rob)?>]" id="rtransport-<?=htmlspecialchars($rob)?>" value="<?=(isset($_POST["rtransport"]) && isset($_POST["rtransport"][$rob])) ? htmlspecialchars($_POST["rtransport"][$rob]) : "0"?>" onchange="recalc_values();" tabindex="<?=$tabindex++?>" onkeyup="recalc_values();" onclick="recalc_values();" class="number number-ress" /> <a onclick="document.getElementById('rtransport-<?=JS::jsentities($rob)?>').value=<?=$item_info["level"]?>; recalc_values();" class="max"><?=F::ths($item_info["level"])?></a></dd>
<?php
							}
						}
?>
				</dl>
			</dd>
<?php
					}
?>
		</dl>
	</fieldset>
<?php
					$i++;
				}
				if($my_flotten+$fleet_obj->getNeededSlots() < $max_flotten)
				{
					$i++;
					$this_pos = $me->getPos();
?>
	<fieldset class="flotte-koords" id="target-<?=$i?>">
		<legend><input type="checkbox" name="auftrag[<?=$i?>]" value="0" id="i-weiteres-ziel" onchange="update_active_fieldset();" tabindex="<?=$tabindex++?>" /> <label for="i-weiteres-ziel">Weiteres Ziel eingeben (nicht bei Stationieren)</label></legend>
		<dl class="form">
			<dt class="c-ziel"><label for="ziel-galaxie"><?=l::h(_("&Ziel[login/flotten.php|2]"))?></label></dt>
			<dd class="c-ziel"><input type="text" id="ziel-galaxie" name="galaxie[<?=$i?>]" value="<?=htmlspecialchars($this_pos[0])?>" title="<?=l::h("Ziel: Galaxie")?>"<?=l::accesskey_attr(_("&Ziel[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);" onkeyup="syncronise(true);" onchange="document.getElementById('i-weiteres-ziel').checked = true; update_active_fieldset(); syncronise(true);" maxlength="<?=strlen(Galaxy::getNumber())?>" class="number number-koords" />:<input type="text" id="ziel-system" name="system[<?=$i?>]" value="<?=htmlspecialchars($this_pos[1])?>" title="<?=l::h(_("Ziel: System&[login/flotten.php|2]"), false)?>"<?=l::accesskey_attr(_("Ziel: System&[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);" onchange="document.getElementById('i-weiteres-ziel').checked = true; update_active_fieldset(); syncronise(true);" onkeyup="syncronise(true);" maxlength="3" class="number number-koords" />:<input type="text" id="ziel-planet" name="planet[<?=$i?>]" value="<?=htmlspecialchars($this_pos[2])?>" title="<?=l::h(_("Ziel: Planet&[login/flotten.php|2]"), false)?>"<?=l::accesskey_attr(_("Ziel: Planet&[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onclick="syncronise(true);"  onchange="document.getElementById('i-weiteres-ziel').checked = true; update_active_fieldset(); syncronise(true);" onkeyup="syncronise(true);" maxlength="2" class="number number-koords" /></dd>
			<script type="text/javascript">
				// <![CDATA[
					document.write('<dt class="c-planet"><label for="ziel-planet-wahl"><?=l::h(_("Pla&net[login/flotten.php|2]"))?></label></dt>');
					document.write('<dd class="c-planet">');
					document.write('<select id="ziel-planet-wahl"<?=l::accesskey_attr(_("Pla&net[login/flotten.php|2]"))?> tabindex="<?=$tabindex++?>" onchange="document.getElementById(\'i-weiteres-ziel\').checked = true; update_active_fieldset(); syncronise(false);" onkeyup="syncronise(false);">');
					document.write('<option value=""><?=l::h(_("Benutzerdefiniert"))?></option>');
<?php
						$shortcuts = $me->getPosShortcutsList();
						if(count($shortcuts) > 0)
						{
?>
					document.write('<optgroup label="<?=l::h(_("Lesezeichen"))?>">');
<?php
							foreach($shortcuts as $shortcut)
							{
								$owner = $shortcut->getOwner();
								$s = $shortcut.': ';
								if($owner)
								{
									$user_obj = Classes::User($owner);
									$s .= $shortcut->getName().' (';
									$alliance = Alliance::getUserAlliance($owner);
									if($alliance) $s .= '['.$alliance.'] ';
									$s .= $owner.')';
								}
								else $s .= "["._('unbesiedelt')."]";
?>
					document.write('<option value="<?=htmlspecialchars($shortcut)?>"><?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($s))?></option>');
<?php
							}
?>
					document.write('</optgroup>');
<?php
						}
?>
					document.write('<optgroup label="<?=l::h(_("Eigene Planeten"))?>">');
<?php
						$planets = $me->getPlanetsList();
						$active_planet = $me->getActivePlanet();
						foreach($planets as $planet)
						{
							$me->setActivePlanet($planet);
?>
					document.write('<option value="<?=htmlspecialchars($me->getPosString())?>"<?=($planet == $active_planet) ? ' selected="selected"' : ''?>><?=l::h(vsprintf(_("%d:%d:%d"), $me->getPos()))?>: <?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($me->planetName()))?></option>');
<?php
						}
						$me->setActivePlanet($active_planet);
?>
					document.write('</select>');
					document.write('</optgroup>');
					document.write('</dd>');

					function syncronise(input_select)
					{
						var select_obj = document.getElementById('ziel-planet-wahl');
						if(!input_select)
						{
							var pos = select_obj.options[select_obj.selectedIndex].value;
							if(pos != '')
							{
								pos = pos.split(/:/);
								document.getElementById('ziel-galaxie').value = pos[0];
								document.getElementById('ziel-system').value = pos[1];
								document.getElementById('ziel-planet').value = pos[2];
							}
						}
						else
						{
							var pos = new Array(3);
							pos[0] = document.getElementById('ziel-galaxie').value;
							pos[1] = document.getElementById('ziel-system').value;
							pos[2] = document.getElementById('ziel-planet').value;
							pos = pos.join(':');

							var one = false;
							for(var sindex=0; sindex<select_obj.options.length; sindex++)
							{
								if(pos == select_obj.options[sindex].value)
								{
									select_obj.selectedIndex = sindex;
									one = true;
									break;
								}
							}

							if(!one)
								select_obj.selectedIndex = 0;
						}
					}

					function update_active_fieldset()
					{
						var el = document.getElementById("target-<?=$i?>");
						if(document.getElementById("i-weiteres-ziel").checked)
							el.className = el.className.replace(/ active/, "") + " active";
						else
							el.className = el.className.replace(/ active/, "");
					}

					update_active_fieldset();
					syncronise(true);
				// ]]>
			</script>
		</dl>
	</fieldset>
<?php
				}
?>
	<div class="button">
<?php
				foreach($_POST['flotte'] as $id=>$anzahl)
				{
?>
		<input type="hidden" name="flotte[<?=htmlspecialchars($id)?>]" value="<?=htmlspecialchars($anzahl)?>" />
<?php
				}

				foreach($target_koords as $i=>$koords)
				{
?>
		<input type="hidden" name="galaxie[<?=$i?>]" value="<?=htmlspecialchars($koords[0])?>" />
		<input type="hidden" name="system[<?=$i?>]" value="<?=htmlspecialchars($koords[1])?>" />
		<input type="hidden" name="planet[<?=$i?>]" value="<?=htmlspecialchars($koords[2])?>" />
<?php
				}
?>
		<button type="submit"<?=l::accesskey_attr(_("Absen&den[login/flotten.php|1]"))?> tabindex="<?=$tabindex++?>"><?=l::h(_("Absen&den[login/flotten.php|1]"))?></button>
	</div>
</form>
<script type="text/javascript">
	// <![CDATA[
<?php
				if($transport[0] > 0)
				{
?>
		var last_res = [ ];
		if(!refresh_callbacks['ress']) refresh_callbacks['ress'] = [ ];
		refresh_callbacks['ress'].push(function(){
			var changed = false;
			for(var i=0; i<=4; i++)
			{
				document.getElementById('fleet-max-'+i).firstChild.data = F::ths(res_now['ress'][i]);
				if(last_res[i] && document.getElementById('transport-'+res_ids[i]).value == last_res[i])
				{
					document.getElementById('transport-'+res_ids[i]).value = Math.floor(res_now['ress'][i]);
					changed = true;
				}
				last_res[i] = Math.floor(res_now['ress'][i]);
			}
			if(changed) recalc_values();
		});
<?php
				}

?>
		function recalc_values()
		{
<?php
				if($transport[0] > 0 || $transport[1] > 0)
				{
?>
			// Transport
			var auftraege = new Array();
			auftraege[1] = true;
			auftraege[2] = false;
			auftraege[3] = false;
			auftraege[4] = true;
			auftraege[5] = false;
			auftraege[6] = true;

			var auftrag = document.getElementById('auftrag-0');
			var use_transport = auftraege[auftrag.options[auftrag.selectedIndex].value];
			document.getElementById('transport-dt').style.display = (use_transport ? 'block' : 'none');
			document.getElementById('transport-dd').style.display = (use_transport ? 'block' : 'none');
			document.getElementById('transport-verbleibend-dt').style.display = (use_transport ? 'block' : 'none');
			document.getElementById('transport-verbleibend-dd').style.display = (use_transport ? 'block' : 'none');
<?php
				}
?>

			// Flugzeit
			var speed_obj = document.getElementById('speed');
			var speed = parseFloat(speed_obj.options[speed_obj.selectedIndex].value);
			var time = [ <?=implode(", ", $time)?> ];
			var tritium;
			var tritium_all = [ <?=implode(", ", $tritium_all)?> ];
			var display = true;
			for(var i=0; i<time.length; i++)
			{
				if(!isNaN(speed))
					time[i] /= speed;
				time[i] = Math.round(time[i]);
				if(time[i] < <?=global_setting("MIN_BUILDING_TIME")?>)
					time[i] = <?=global_setting("MIN_BUILDING_TIME")?>;

				var time_string = '';
				if(time[i] >= 86400)
				{
					time_string += Math.floor(time[i]/86400)+'<?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(_("Tage"))?>">d</abbr> ';
					var time2 = time%86400;
				}
				else
					var time2 = time[i];
				time_string += mk2(Math.floor(time2/3600))+':'+mk2(Math.floor((time2%3600)/60))+':'+mk2(Math.floor(time2%60));
				document.getElementById('flugzeit-'+i).innerHTML = time_string;

				if(display)
					tritium = tritium_all[i];
				if(document.getElementById('auftrag-'+i).value == <?=Fleet::TYPE_STATIONIEREN?> || document.getElementById('auftrag-'+i).value == <?=Fleet::TYPE_BESIEDELN?>)
					display = false;
				document.getElementById('target-'+(i+1)).style.display = (display ? "block" : "none");
			}

			setInterval(function(){
				for(var i=0; i<time.length; i++)
				{
					var jetzt = new Date();
					var ankunft_client = new Date(jetzt.getTime()+(time[i]*1000));
					var ankunft_server = new Date(ankunft_client.getTime()-time_diff);

					set_title(document.getElementById('flugzeit-'+i), '<?=sprintf(str_replace("'", "\\'", _("Ankunft: %s")), sprintf(str_replace("'", "\\'", _("%s (Lokalzeit)")), "'+mk2(ankunft_client.getHours())+':'+mk2(ankunft_client.getMinutes())+':'+mk2(ankunft_client.getSeconds())+', '+ankunft_client.getFullYear()+'-'+mk2(ankunft_client.getMonth()+1)+'-'+mk2(ankunft_client.getDate())+' (Lokalzeit); '+mk2(ankunft_server.getHours())+':'+mk2(ankunft_server.getMinutes())+':'+mk2(ankunft_server.getSeconds())+', '+ankunft_server.getFullYear()+'-'+mk2(ankunft_server.getMonth()+1)+'-'+mk2(ankunft_server.getDate())+'"))?>');
				}
			}, 1000);

			// Tritiumverbrauch
			if(!isNaN(speed))
				tritium = Math.floor(tritium*speed);
			document.getElementById('tritium-verbrauch').innerHTML = F::ths(tritium)+'<?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(_("Tonnen"))?>">t</abbr>';
			document.getElementById('tritium-verbrauch').className = 'c-tritiumverbrauch '+((<?=$this_ress[4]?> >= tritium) ? 'genug' : 'fehlend');
<?php
				if($transport[0] > 0 || $transport[1] > 0)
				{
?>

			// Verbleibendes Ladevermoegen
			if(use_transport)
			{
<?php
					if($transport[0] > 0)
					{
?>
				var ges_ress = myParseInt(document.getElementById('transport-carbon').value)+myParseInt(document.getElementById('transport-aluminium').value)+myParseInt(document.getElementById('transport-wolfram').value)+myParseInt(document.getElementById('transport-radium').value)+myParseInt(document.getElementById('transport-tritium').value);
<?php
					}
					else
					{
?>
				var ges_ress = 0;
<?php
					}
					if($transport[1] > 0)
					{
						$robs_arr = array();
						foreach($me->getItemsList('roboter') as $rob)
							$robs_arr[] = "myParseInt(document.getElementById('rtransport-".$rob."').value)";
?>
				var ges_rob = <?=implode('+', $robs_arr)?>;
<?php
					}
					else
					{
?>
				var ges_rob = 0;
<?php
					}
?>
				var remain_ress = <?=$transport[0]?>;
				if(!isNaN(ges_ress))
					remain_ress -= ges_ress;
				var remain_rob = <?=$transport[1]?>;
				if(!isNaN(ges_rob))
					remain_rob -= ges_rob;
				if(remain_ress < 0)
					remain_ress = "\u22120";
				else
					remain_ress = F::ths(remain_ress);
				if(remain_rob < 0)
					remain_rob = "\u22120";
				else
					remain_rob = F::ths(remain_rob);
				document.getElementById('transport-verbleibend-dd').innerHTML = remain_ress+'<?=l::h(_("[unit_separator]"))?><abbr title="<?=l::h(_("Tonnen"))?>">t</abbr>, '+remain_rob+'&nbsp;<?=l::h(_("Roboter"))?>';
			}
<?php
				}
?>
		}

		recalc_values();
	// ]]>
</script>
<?php
				break;
			}

#################################################################################
### login/flotten.php|3
#################################################################################

			case LoginFlottenException::FORMULAR_SENT:
			{
?>
<div class="flotte-versandt">
<?php
				if($e->getMessage())
				{
?>
	<p class="<?=htmlspecialchars($e->getClass())?>"><?=l::h($e->getMessage())?></p>
<?php
				}

				foreach($auftraege as $i=>$auftrag)
				{
?>
	<dl>
		<dt class="c-ziel"><?=l::h(_("Ziel"))?></dt>
		<dd class="c-ziel"><?=F::formatPlanet($target_planets[$i])?></dd>

		<dt class="c-auftragsart"><?=l::h(_("Auftragsart"))?></dt>
		<dd class="c-auftragsart"><?=l::h(_("[fleet_".$auftrag."]"))?></dd>

		<dt class="c-ankunft"><?=l::h(_("Ankunft"))?></dt>
		<dd class="c-ankunft"><?=l::h(sprintf("%s (Serverzeit)", date(_("H:i:s, Y-m-d"), $fleet_obj->getNextArrival())))?></dd>
	</dl>
<?php
					if($i == 0)
					{
						if($auftrag == Fleet::TYPE_TRANSPORT && $planet_owner[$i] && $planet_owner[$i]->getName() == $me->getName())
						{
?>
	<div class="handel action"><a href="info/flotten_actions.php?action=handel&amp;id=<?=htmlspecialchars(urlencode($fleet_obj->getName()))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Geben Sie dieser Flotte Ladung mit auf den Rückweg"))?>"<?=l::accesskey_attr(_("Handel&[login/flotten.php|3]"))?> tabindex="<?=$tabindex++?>"><?=l::h(_("Handel&[login/flotten.php|3]"))?></a></div>
<?php
						}
						if($auftrag == Fleet::TYPE_ANGRIFF && !$buendnisflug)
						{
?>
	<div class="buendnisangriff actions"><a href="info/flotten_actions.php?action=buendnisangriff&amp;id=<?=htmlspecialchars(urlencode($flotte))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Erlauben Sie anderen Spielern, der Flotte eigene Schiffe beizusteuern."))?>"<?=l::accesskey_attr(_("Bündnisangriff&[login/flotten.php|3]"))?> tabindex="<?=$tabindex++?>"><?=l::h(_("Bündnisangriff&[login/flotten.php|3]"))?></a></div>
<?php
						}
					}
				}
?>
</div>
<?php
				break;
			}
		}

		$gui->end();
	}
?>
