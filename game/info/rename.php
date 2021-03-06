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
	 * Benennt Planeten um, löst sie auf und verändert deren Reihenfolge.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('../include.php');

	$planet_error = false;
	if(isset($_POST['planet_name']))
	{
		if(trim($_POST['planet_name']) == '')
			$_POST['planet_name'] = $me->planetName();
		elseif(strlen($_POST['planet_name']) <= 24)
			$planet_error = !$me->planetName($_POST['planet_name']);
	}

	# Herausfinden, ob eigene Flotten zu/von diesem Planeten unterwegs sind
	$flotte_unterwegs = count(Fleet::userFleetsToPlanet($me->getName(), $me->getPosObj()));
	$planets = $me->getPlanetsList();

	if(isset($_POST['password']) && !$flotte_unterwegs && count($planets) > 1)
	{
		if(!$me->checkPassword($_POST['password']))
			$aufgeben_error = _('Sie haben ein falsches Passwort eingegeben.');
		else
		{
			$me->removePlanet($me->getActivePlanet());
			define_url_suffix();
			$planets = $me->getPlanetsList();
		}
	}

	if(isset($_GET['down']))
	{
		$me->movePlanetDown($_GET['down']);
		define_url_suffix();
		$planets = $me->getPlanetsList();
	}
	if(isset($_GET['up']))
	{
		$me->movePlanetUp($_GET['up']);
		define_url_suffix();
		$planets = $me->getPlanetsList();
	}

	$gui->init();

	if($planet_error)
	{
?>
<p class="error"><?=L::h(_("Datenbankfehler."))?></p>
<?php
	}
	elseif(isset($_POST['planet_name']) && strlen($_POST['planet_name']) > 24)
	{
?>
<p class="error"><?=L::h(_("Der Name darf maximal 24 Bytes lang sein."))?></p>
<?php
	}
?>
<form action="rename.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post">
	<fieldset>
		<legend><?=L::h(_("Planeten umbenennen"))?></legend>
		<dl class="form">
			<dt><label for="name"><?=L::h(_("&Neuer Name[login/info/rename.php|1]"))?></label></dt>
			<dd><input type="text" id="name" name="planet_name" value="<?=htmlspecialchars($me->planetName())?>" maxlength="24"<?=L::accesskeyAttr(_("&Neuer Name[login/info/rename.php|1]"))?> tabindex="<?=$tabindex++?>" /></dd>
		</dl>
		<div class="button"><button type="submit"<?=L::accesskeyAttr(_("&Umbenennen[login/info/rename.php|1]"))?> tabindex="<?=$tabindex++?>"><?=L::h(_("&Umbenennen[login/info/rename.php|1]"))?></button></div>
	</fieldset>
</form>
<?php
	if($flotte_unterwegs || count($planets) <= 1)
	{
?>
<p class="planeten-nicht-aufgeben"><?=L::h(_("Sie können diesen Planeten derzeit nicht aufgeben, da Flottenbewegungen Ihrerseits von/zu diesem Planeten unterwegs sind oder dies Ihr einziger Planet ist."))?></p>
<?php
	}
	else
	{
		if(isset($aufgeben_error) && trim($aufgeben_error) != '')
		{
?>
<p class="error"><?=htmlspecialchars($aufgeben_error)?></p>
<?php
		}
?>
<form action="<?=htmlspecialchars(global_setting("USE_PROTOCOL").'://'.$_SERVER['HTTP_HOST'].global_setting("h_root").'/login/info/rename.php?'.global_setting("URL_SUFFIX"))?>" method="post">
	<fieldset>
		<legend><?=L::h(_("Planeten aufgeben"))?></legend>
		<dl class="form">
			<dt><label for="password"><?=L::h(_("Passwort&[login/info/rename.php|1]"))?></label></dt>
			<dd><input type="password" id="password" name="password" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Passwort&[login/info/rename.php|1]"))?> /></dd>
		</dl>
		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Aufgeben&[login/info/rename.php|1]"))?>><?=L::h(_("Aufgeben&[login/info/rename.php|1]"))?></button></div>
	</fieldset>
</form>
<?php
		# Ueberpruefen, ob von diesem Planeten aus Flotten fremdstationiert sind
		$foreign = 0;
		foreach($me->getMyForeignFleets() as $koords)
		{
			$user_obj = Classes::User($koords->getOwner());
			$user_obj->cacheActivePlanet();
			$user_obj->setActivePlanet($user_obj->getPlanetByPos($koords));
			foreach($user_obj->getForeignFleetsList($me->getName()) as $i=>$fleet)
			{
				if($fleet[1]->equals($me->getPosObj()))
					$foreign++;
			}
			$user_obj->restoreActivePlanet();
		}

		if($foreign > 0)
		{
?>
<p><strong><?=sprintf(h(ngettext("Achtung! Von diesem Planeten ist noch eine Fremdstationierung aktiv. Wenn Sie den Planeten auflösen, wird diese Flotte zerstört werden.", "Achtung! Von diesem Planeten sind noch %s Fremdstationierungen aktiv. Wenn Sie den Planeten auflösen, werden diese Flotten zerstört werden.", $foreign)), F::ths($foreign))?></strong></p>
<?php
		}
	}

	if(count($planets) > 1)
	{
?>
<fieldset class="planeten-reihenfolge">
	<legend><?=L::h(_("Planeten-Reihenfolge"))?></legend>
	<ol class="order-list">
<?php
		$active_planet = $me->getActivePlanet();
		foreach($planets as $i=>$planet)
		{
			$me->setActivePlanet($planet);
?>
		<li><?=htmlspecialchars(F::formatPlanet($me->getPosObj(), false, false))?><span class="aktionen"><?php if($i != 0){?> &ndash; <a href="rename.php?up=<?=htmlspecialchars(urlencode($planet))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="hoch">[<?=L::h(_("Hoch"))?>]</a><?php } if($i != count($planets)-1){?> &ndash; <a href="rename.php?down=<?=htmlspecialchars(urlencode($planet))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="runter">[<?=L::h(_("Runter"))?>]</a><?php }?></span></li>
<?php
		}
		$me->setActivePlanet($active_planet);
?>
	</ol>
</fieldset>
<?php
	}

	$gui->end();
?>
