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
	 * Ermöglicht die Kontrolle der Allianz, in der man Mitglied ist.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\frontend;

	require('include.php');

	$gui->init();

	$action = false;
	if(isset($_GET['action']))
		$action = $_GET['action'];

	$tag = Alliance::getUserAlliance($me->getName());
	$tag_app = Alliance::getUserAllianceApplication($me->getName());

	if(!$tag)
	{
		if($tag_app)
		{
			$alliance = Classes::Alliance($tag_app);

			if($action == 'cancel' && $alliance->cancelApplication($me->getName()))
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|0]"))?>><?=l::h(_("Allian&z[login/allianz.php|0]"))?></a></h2>
<p class="successful"><?=l::h(_("Ihre Bewerbung wurde erfolgreich zurückgezogen."))?></p>
<?php
			}
			else
			{
?>
<h2><?=l::h(_("Allianz"))?></h2>
<p class="allianz-laufende-bewerbung successful"><?=sprintf(h(_("Sie haben derzeit eine laufende Bewerbung bei der Allianz %s.")), "<a href=\"info/allianceinfo.php?alliance=".htmlspecialchars(urlencode($alliance->getName())."&".global_setting("URL_SUFFIX"))."\">".htmlspecialchars($alliance->getName())."</a>")?></p>
<ul class="allianz-laufende-bewerbung-aktionen possibilities">
	<li><a href="allianz.php?action=cancel&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"<?=l::accesskey_attr(_("Bewerbung zurückziehen&[login/allianz.php|1]"))?>><?=l::h(_("Bewerbung zurückziehen&[login/allianz.php|1]"))?></a></li>
</ul>
<?php
			}
		}
		elseif($action == 'gruenden')
		{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|2]"))?>><?=l::h(_("Allian&z[login/allianz.php|2]"))?></a></h2>
<?php
			if(isset($_POST['tag']) && isset($_POST['name']))
			{
				$_POST['tag'] = trim($_POST['tag']);
				if(strlen($_POST['tag']) < 2)
				{
?>
<p class="error"><?=l::h(_("Das Allianztag muss mindestens zwei Bytes lang sein."))?></p>
<?php
				}
				elseif(strlen($_POST['tag']) > 6)
				{
?>
<p class="error"><?=l::h(_("Das Allianztag darf höchstens sechs Bytes lang sein."))?></p>
<?php
				}
				elseif($_POST["tag"] == "0")
				{
?>
<p class="error"><?=l::h(_("Dieses Tag ist nicht zulässig."))?></p>
<?php
				}
				elseif(Alliance::exists($_POST['tag']))
				{
?>
<p class="error"><?=l::h(_("Es gibt schon eine Allianz mit diesem Tag."))?></p>
<?php
				}
				else
				{
					$alliance_obj = Classes::Alliance(Alliance::create($_POST['tag']));
					$alliance_obj->name($_POST['name']);
					$alliance_obj->addUser($me->getName(), $me->getScores());
					$alliance_obj->setUserStatus($me->getName(), "Gr\xc3\xbcnder");
					$alliance_obj->setUserPermissions($me->getName(), 0, true);
					$alliance_obj->setUserPermissions($me->getName(), 1, true);
					$alliance_obj->setUserPermissions($me->getName(), 2, true);
					$alliance_obj->setUserPermissions($me->getName(), 3, true);
					$alliance_obj->setUserPermissions($me->getName(), 4, true);
					$alliance_obj->setUserPermissions($me->getName(), 5, true);
					$alliance_obj->setUserPermissions($me->getName(), 6, true);
					$alliance_obj->setUserPermissions($me->getName(), 7, true);
					$alliance_obj->setUserPermissions($me->getName(), 8, true);
					$me->allianceTag($_POST['tag']);
?>
<p class="successful"><?=sprintf(h(_("Die Allianz %s wurde erfolgreich gegründet.")), htmlspecialchars($_POST['tag']))?></p>
<?php
					$gui->end();
					exit();
				}
			}
?>
<form action="allianz.php?action=gruenden&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-gruenden-form">
	<dl class="form">
		<dt><label for="allianztag-input"><?=l::h(_("Allianztag&[login/allianz.php|2]"))?></label></dt>
		<dd><input type="text" name="tag" id="allianztag-input" value="<?=isset($_POST['tag']) ? htmlspecialchars($_POST['tag']) : ''?>" title="<?=l::h(_("Das Allianztag wird in der Karte und in den Highscores vor dem Benutzernamen angezeigt."))?>" maxlength="6" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allianztag&[login/allianz.php|1]"))?> /></dd>

		<dt><label for="allianzname-input"><?=l::h(_("Allianzname&[login/allianz.php|2]"))?></label></dt>
		<dd><input type="text" name="name" id="allianzname-input" value="<?=isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allianzname&[login/allianz.php|1]"))?> /></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z gründen[login/allianz.php|2]"))?>><?=l::h(_("Allian&z gründen[login/allianz.php|2]"))?></button></div>
</form>
<?php
		}
		elseif($action == 'apply')
		{
			if(!isset($_GET['for']))
				$_GET['for'] = '';
			if(!Alliance::exists($_GET['for']))
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|3]"))?>><?=l::h(_("Allian&z[login/allianz.php|3]"))?></a></h2>
<p class="error"><?=l::h(_("Diese Allianz gibt es nicht."))?></p>
<?php
			}
			else
			{
				$alliance = Classes::Alliance($_GET['for']);
				if(!$alliance->allowApplications())
				{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|4]"))?>><?=l::h(_("Allian&z[login/allianz.php|4]"))?></a></h2>
<p class="error"><?=l::h(_("Diese Allianz akzeptiert keine neuen Bewerbungen."))?></p>
<?php
				}
				elseif(!isset($_POST['text']))
				{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|5]"))?>><?=l::h(_("Allian&z[login/allianz.php|5]"))?></a></h2>
<form action="allianz.php?action=apply&amp;for=<?=htmlspecialchars(urlencode($_GET['for']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-bewerben-form">
	<dl class="form">
		<dt><label for="bewerbungstext-textarea"><?=l::h(_("Bewerbungste&xt[login/allianz.php|5]"))?></label></dt>
		<dd><textarea name="text" id="bewerbungstext-textarea" cols="50" rows="17" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Bewerbungste&xt[login/allianz.php|5]"))?>></textarea></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Bewerbu&ng absenden[login/allianz.php|5]"))?>><?=l::h(_("Bewerbu&ng absenden[login/allianz.php|5]"))?></button></div>
</form>
<?php
				}
				else
				{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|6]"))?>><?=l::h(_("Allian&z[login/allianz.php|6]"))?></a></h2>
<?php
					$alliance->newApplication($me->getName(), $_POST["text"]);
?>
<p class="successful"><?=l::h(_("Ihre Bewerbung wurde erfolgreich abgesandt."))?></p>
<?php
				}
			}
		}
		else
		{
?>
<h2><?=l::h(_("Allianz"))?></h2>
<p class="allianz-keine error"><?=l::h(_("Sie gehören derzeit keiner Allianz an."))?></p>
<form action="allianz.php" method="get" class="allianz-moeglichkeiten">
	<ul class="possibilities">
		<li><a href="allianz.php?action=gruenden&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex+2?>"<?=l::accesskey_attr(_("Eigene Allian&z gründen[login/allianz.php|7]"))?>><?=l::h(_("Eigene Allian&z gründen[login/allianz.php|7]"))?></a></li>
		<li><input type="text" name="search" value="<?=(isset($_GET['search'])) ? htmlspecialchars($_GET['search']) : ''?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allia&nz suchen[login/allianz.php|7]"))?> /> <button type="submit" tabindex="<?=$tabindex++?>"><?=l::h(_("Allia&nz suchen[login/allianz.php|7]"))?></button><?=global_setting("URL_FORMULAR")?></li>
	</ul>
</form>
<?php
			$tabindex += 2;
			if(isset($_GET['search']) && $_GET['search'])
			{
?>
<h3 id="allianz-suchergebnisse" class="strong"><?=l::h(_("Suchergebnisse"))?></h3>
<?php
				$alliances = Alliance::findAlliance($_GET['search']);

				if(count($alliances) <= 0)
				{
?>
<p class="error"><?=l::h(_("Es wurden keine Allianzen gefunden."))?></p>
<?php
				}
				else
				{
?>
<ul class="allianz-suchergebnisse possibilities strong">
<?php
					foreach($alliances as $alliance)
					{
?>
	<li><a href="info/allianceinfo.php?alliance=<?=htmlspecialchars(urlencode($alliance))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($alliance)?></a></li>
<?php
					}
?>
</ul>
<?php
				}
			}
		}
	}
	else
	{
		$alliance = Classes::Alliance($tag);
		if($action == 'liste')
		{
			$sort = (isset($_GET['sortby']) && in_array($_GET['sortby'], array('punkte', 'rang', 'time')));
			if(!isset($_GET['invert']) || !$_GET['invert'])
				$invert = '&amp;invert=1';
			else
				$invert = '';
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|8]"))?>><?=l::h(_("Allian&z[login/allianz.php|8]"))?></a></h2>
<?php
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_RANK) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_APPLICATIONS))
			{
?>
<form action="allianz.php?action=liste<?=isset($_GET['sortby']) ? '&amp;sortby='.htmlspecialchars(urlencode($_GET['sortby'])) : ''?><?=isset($_GET['invert']) ? '&amp;invert='.htmlspecialchars(urlencode($_GET['invert'])) : ''?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-liste-form">
<?php
			}
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK))
			{
?>
<script type="text/javascript">
	var kick_warned = false;
</script>
<?php
			}
?>
<table class="allianz-liste">
	<thead>
		<tr>
			<th class="c-name"><a href="allianz.php?action=liste<?=$sort ? '' : $invert?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Nach Namen sortieren"))?>"<?=l::accesskey_attr(_("Name&[login/allianz.php|8]"))?>><?=l::h(_("Name&[login/allianz.php|8]"))?></a></th>
			<th class="c-rang"><a href="allianz.php?action=liste&amp;sortby=rang<?=($sort && $_GET['sortby'] == 'rang') ? $invert : ''?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Nach Rang sortieren"))?>"<?=l::accesskey_attr(_("Rang&[login/allianz.php|8]"))?>><?=l::h(_("Rang&[login/allianz.php|8]"))?></a></th>
			<th class="c-punkte"><a href="allianz.php?action=liste&amp;sortby=punkte<?=($sort && $_GET['sortby'] == 'punkte') ? $invert : ''?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Nach Punkten sortieren"))?>"<?=l::accesskey_attr(_("P&unkte[login/allianz.php|8]"))?>><?=l::h(_("P&unkte[login/allianz.php|8]"))?></a></th>
			<th class="c-aufnahmezeit"><a href="allianz.php?action=liste&amp;sortby=time<?=($sort && $_GET['sortby'] == 'time') ? $invert : ''?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Nach Aufnahmezeit sortieren"))?>"<?=l::accesskey_attr(_("Auf&nahmezeit[login/allianz.php|8]"))?>><?=l::h(_("Auf&nahmezeit[login/allianz.php|8]"))?></a></th>
<?php
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK))
			{
?>
			<th class="c-kick"><?=l::h(_("Kick"))?></th>
<?php
			}
?>
		</tr>
	</thead>
	<tbody>
<?php
			if($sort)
				$liste = $alliance->getUsersList($_GET['sortby'], (isset($_GET['invert']) && $_GET['invert']));
			else $liste = $alliance->getUsersList(true, (isset($_GET['invert']) && $_GET['invert']));

			$liste_count = count($liste);
			foreach($liste as $i=>$member_name)
			{
				if((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK)) && isset($_POST['kick']) && isset($_POST['kick'][$i]) && $member_name != $me->getName())
				{
					$alliance->kickUser($member_name, $me->getName());
					continue;
				}
?>
		<tr>
			<th class="c-name"><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($member_name))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($member_name)?></a></th>
<?php
				if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_RANK))
				{
					if(isset($_POST['rang']) && isset($_POST['rang'][$i]))
						$alliance->setUserStatus($member_name, $_POST['rang'][$i]);
?>
			<td class="c-rang"><input type="text" name="rang[<?=htmlspecialchars($i)?>]" value="<?=htmlspecialchars($alliance->getUserStatus($member_name))?>" tabindex="<?=$tabindex++?>" /></td>
<?php
					$kick_tabindex = $tabindex+$liste_count;
				}
				else
				{
?>
			<td class="c-rang"><?=htmlspecialchars($alliance->getUserStatus($member_name))?></td>
<?php
					$kick_tabindex = $tabindex++;
				}
?>
			<td class="c-punkte"><?=F::ths($alliance->getUserScores($member_name))?></td>
			<td class="c-aufnahmezeit"><?=date(_('Y-m-d, H:i:s'), $alliance->getUserJoiningTime($member_name))?></td>
<?php
				if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK))
				{
?>
			<td class="c-kick"><input type="checkbox" name="kick[<?=htmlspecialchars($i)?>]" onchange="if(!kick_warned){ kick_warned=true; alert('<?=JS::jsentities(_("Die ausgewählten Benutzer werden beim Speichern der Änderungen aus der Allianz geworfen."))?>'); }"<?=($member_name == $me->getName()) ? ' disabled="disabled"' : ''?> tabindex="<?=$kick_tabindex?>" /></td>
<?php
				}
?>
		</tr>
<?php
			}
?>
	</tbody>
</table>
<?php
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_APPLICATIONS))
			{
				if(isset($_POST['toggle_allow_applications']) && $_POST['toggle_allow_applications'])
					$alliance->allowApplications(isset($_POST['allow_applications']));
?>
	<div><input type="hidden" name="toggle_allow_applications" value="1" /><input type="checkbox" name="allow_applications" id="i-allow-applications"<?=$alliance->allowApplications() ? ' checked="checked"' : ''?> /> <label for="i-allow-applications"><?=l::h(_("Neue Bewerbungen akzeptieren"))?></label></div>
<?php
			}
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_RANK) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_APPLICATIONS))
			{
?>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Ä&nderungen speichern[login/allianz.php|8]"))?>><?=l::h(_("Ä&nderungen speichern[login/allianz.php|8]"))?></button></div>
</form>
<?php
			}
			if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_KICK))
				$tabindex += $liste_count;
		}
		else
		{
			$austreten = !$alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_PERMISSIONS);
			if(!$austreten)
			{
				$members = $alliance->getUsersList();
				foreach($members as $name)
				{
					if($name == $me->getName())
						continue;
					if($alliance->checkUserPermissions($name, Alliance::PERMISSION_PERMISSIONS))
					{
						$austreten = true;
						break;
					}
				}
			}

			if((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_REMOVE)) && $action == 'aufloesen')
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|9]"))?>><?=l::h(_("Allian&z[login/allianz.php|9]"))?></a></h2>
<?php
				if($alliance->destroy($me->getName()))
				{
?>
<p class="successful"><?=l::h(_("Die Allianz wurde aufgelöst."))?></p>
<?php
				}
				else
				{
?>
<p class="error"><?=l::h(_("Datenbankfehler."))?></p>
<?php
				}
			}
			elseif($austreten && $action == 'austreten')
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|10]"))?>><?=l::h(_("Allian&z[login/allianz.php|10]"))?></a></h2>
<?php
				$alliance->quitUser($me->getName());
?>
<p class="successful"><?=l::h(_("Sie haben die Allianz verlassen."))?></p>
<?php
			}
			elseif((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_INTERNAL)) && $action == 'intern')
			{
				if(isset($_POST['intern-text']))
					$alliance->setInternalDescription($_POST['intern-text']);
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|11]"))?>><?=l::h(_("Allian&z[login/allianz.php|11]"))?></a></h2>
<form action="allianz.php?action=intern&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-intern-form">
	<dl class="form">
		<dt><label for="allianz-intern-textarea"><?=l::h(_("Interner Allianzte&xt[login/allianz.php|11]"))?></label></dt>
		<dd><textarea name="intern-text" id="allianz-intern-textarea" cols="50" rows="17" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Interner Allianzte&xt[login/allianz.php|11]"))?>><?=preg_replace("/[\t\r\n]/e", "'&#'.ord('\$0').';'", htmlspecialchars($alliance->getInternalDescription(false)))?></textarea></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Speicher&n[login/allianz.php|11]"))?>><?=l::h(_("Speicher&n[login/allianz.php|11]"))?></button></div>
</form>
<?php
				$tabindex++;
			}
			elseif((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_EXTERNAL)) && $action == 'extern')
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|12]"))?>><?=l::h(_("Allian&z[login/allianz.php|12]"))?></a></h2>
<?php
				if(isset($_POST['extern-text']))
					$alliance->setExternalDescription($_POST['extern-text']);
				if(isset($_POST['extern-name']) && strlen(trim($_POST['extern-name'])) > 0)
					$alliance->name($_POST['extern-name']);
				if(isset($_POST['extern-tag']) && trim($_POST['extern-tag']) != $alliance->getName() && $alliance->renameAllowed())
				{
					if(strlen($_POST['extern-tag']) < 2)
					{
?>
<p class="error"><?=l::h(_("Das Allianztag muss mindestens zwei Bytes lang sein."))?></p>
<?php
					}
					elseif(strlen($_POST['extern-tag']) > 6)
					{
?>
<p class="error"><?=l::h(_("Das Allianztag darf höchstens sechs Bytes lang sein."))?></p>
<?php
					}
					elseif(Alliance::exists($_POST['extern-tag']))
					{
?>
<p class="error"><?=l::h(_("Es gibt schon eine Allianz mit diesem Tag."))?></p>
<?php
					}
					else $alliance->rename($_POST['extern-tag']);
				}
?>
<form action="allianz.php?action=extern&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-extern-form">
	<dl class="form">
		<dt class="c-tag"><label for="i-allianz-tag"><?=l::h(_("Allianztag&[login/allianz.php|12]"))?></label></dt>
		<dd class="c-tag"><input type="text" name="extern-tag" id="i-allianz-tag" value="<?=htmlspecialchars($alliance->getName())?>" tabindex="<?=$tabindex+3?>"<?=l::accesskey_attr(_("Allianztag&[login/allianz.php|12]"))?><?php if(!$alliance->renameAllowed()){?> disabled="disabled"<?php }?> /> <span class="allianztag-aendern-hinweis"><?=l::h(sprintf(ngettext("Das Allianztag kann einmal am Tag geändert werden.", "Das Allianztag kann alle %s Tage geändert werden.", global_setting("ALLIANCE_RENAME_PERIOD")), global_setting("ALLIANCE_RENAME_PERIOD")))?></span></dd>

		<dt class="c-name"><label for="allianz-name-input"><?=l::h(_("Allianzname&[login/allianz.php|12]"))?></label></dt>
		<dd class="c-name"><input type="text" name="extern-name" id="allianz-name-input" value="<?=htmlspecialchars($alliance->name())?>" tabindex="<?=$tabindex+4?>"<?=l::accesskey_attr(_("Allianzname&[login/allianz.php|12]"))?> /></dd>

		<dt class="c-text"><label for="allianz-extern-textarea"><?=l::h(_("E&xterner Allianztext[login/allianz.php|12]"))?></label></dt>
		<dd class="c-text"><textarea name="extern-text" id="allianz-extern-textarea" cols="50" rows="17" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("E&xterner Allianztext[login/allianz.php|12]"))?>><?=preg_replace("/[\t\r\n]/e", "'&#'.ord('\$0').';'", htmlspecialchars($alliance->getExternalDescription(false)))?></textarea></dd>
	</dl>
	<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Speicher&n[login/allianz.php|12]"))?>><?=l::h(_("Speicher&n[login/allianz.php|12]"))?></button></div>
</form>
<?php
				$tabindex += 3;
			}
			elseif((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_PERMISSIONS)) && $action == 'permissions')
			{
?>
<h2><a href="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Zurück zur Allianzübersicht"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Allian&z[login/allianz.php|13]"))?>><?=l::h(_("Allian&z[login/allianz.php|13]"))?></a></h2>
<form action="allianz.php?action=permissions&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" method="post" class="allianz-rechte-form">
	<table>
		<thead>
			<tr>
				<th><?=l::h(_("Mitglied"))?></th>
				<th><abbr title="<?=l::h(_("Rundschreiben"))?>"><?=l::h(_("Rundschr."))?></abbr></th>
				<th title="<?=l::h(_("Koordinaten der anderen Allianzmitglieder einsehen"))?>"><?=l::h(_("Koordinaten"))?></th>
				<th title="<?=l::h(_("Internen Bereich bearbeiten"))?>"><?=l::h(_("Intern"))?></th>
				<th title="<?=l::h(_("Externen Bereich bearbeiten"))?>"><?=l::h(_("Extern"))?></th>
				<th title="<?=l::h(_("Bewerbungen annehmen oder ablehnen"))?>"><?=l::h(_("Bewerbungen"))?></th>
				<th title="<?=l::h(_("Mitglieder aus der Allianz werfen"))?>"><?=l::h(_("Kick"))?></th>
				<th title="<?=l::h(_("Ränge verteilen"))?>"><?=l::h(_("Ränge"))?></th>
				<th title="<?=l::h(_("Benutzerrechte verteilen"))?>"><?=l::h(_("Rechte"))?></th>
				<th title="<?=l::h(_("Bündnis auflösen"))?>"><?=l::h(_("Auflösen"))?></th>
			</tr>
		</thead>
		<tbody>
<?php
				$member_names = $alliance->getUsersList(true);

				foreach($member_names as $i=>$member_name)
				{
					if(isset($_POST['permissions']) && isset($_POST['permissions'][$i]))
					{
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_MAIL, isset($_POST['permissions'][$i][Alliance::PERMISSION_MAIL]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_COORDS, isset($_POST['permissions'][$i][Alliance::PERMISSION_COORDS]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_INTERNAL, isset($_POST['permissions'][$i][Alliance::PERMISSION_INTERNAL]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_EXTERNAL, isset($_POST['permissions'][$i][Alliance::PERMISSION_EXTERNAL]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_APPLICATIONS, isset($_POST['permissions'][$i][Alliance::PERMISSION_APPLICATIONS]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_KICK, isset($_POST['permissions'][$i][Alliance::PERMISSION_KICK]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_RANK, isset($_POST['permissions'][$i][Alliance::PERMISSION_RANK]));
						if($member_name != $me->getName() || isset($_SESSION["admin_username"]))
							$alliance->setUserPermissions($member_name, Alliance::PERMISSION_PERMISSIONS, isset($_POST['permissions'][$i][Alliance::PERMISSION_PERMISSIONS]));
						$alliance->setUserPermissions($member_name, Alliance::PERMISSION_REMOVE, isset($_POST['permissions'][$i][Alliance::PERMISSION_REMOVE]));
					}
?>
			<tr>
				<th><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($member_name))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=l::h(_("Informationen zu diesem Spieler anzeigen"))?>"><?=htmlspecialchars($member_name)?></a><input type="hidden" name="permissions[<?=htmlspecialchars($i)?>][9]" value="on" /></th>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_MAIL)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_MAIL) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_COORDS)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_COORDS) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_INTERNAL)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_INTERNAL) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_EXTERNAL)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_EXTERNAL) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_APPLICATIONS)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_APPLICATIONS) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_KICK)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_KICK) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_RANK)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_RANK) ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_PERMISSIONS)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_PERMISSIONS) ? ' checked="checked"' : ''?><?=($member_name == $me->getName() && !isset($_SESSION["username"])) ? ' disabled="disabled"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=htmlspecialchars($i)?>][<?=htmlspecialchars(Alliance::PERMISSION_REMOVE)?>]"<?=$alliance->checkUserPermissions($member_name, Alliance::PERMISSION_REMOVE) ? ' checked="checked"' : ''?> /></td>
			</tr>
<?php
				}
?>
		</tbody>
	</table>
	<div class="button"><button type="submit"<?=l::accesskey_attr(_("Speichern&[login/allianz.php|13]"))?>><?=l::h(_("Speichern&[login/allianz.php|13]"))?></button></div>
</form>
<?php
			}
			else
			{
?>
<h2><?=l::h(_("Allianz"))?></h2>
<?php
				if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_APPLICATIONS))
				{
					if($action == 'annehmen' && isset($_GET['which']))
						$alliance->acceptApplication($_GET['which'], $me->getName());
					elseif($action == 'ablehnen' && isset($_GET['which']))
						$alliance->rejectApplication($_GET['which'], $me->getName());
					$applications = $alliance->getApplicationsList();

					if(count($applications) > 0)
					{
?>
<h3 id="laufende-bewerbungen" class="strong"><?=l::h(_("Laufende Bewerbungen"))?></h3>
<dl class="allianz-laufende-bewerbungen player-list-actions">
<?php
						foreach($applications as $bewerbung)
						{
?>
	<dt><a href="info/playerinfo.php?player=<?=htmlspecialchars(urlencode($bewerbung))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=htmlspecialchars($bewerbung)?></a></dt>
	<dd><ul>
		<li><a href="allianz.php?action=annehmen&amp;which=<?=htmlspecialchars(urlencode($bewerbung))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=l::h(_("Annehmen"))?></a></li>
		<li><a href="allianz.php?action=ablehnen&amp;which=<?=htmlspecialchars(urlencode($bewerbung))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" onclick="return confirm('<?=JS::jsentities(sprintf(_("Sind Sie sicher, dass Sie die Bewerbung des Benutzers %s ablehnen wollen?"), $bewerbung))?>');"><?=l::h(_("Ablehnen"))?></a></li>
	</ul></dd>
<?php
						}
?>
</dl>
<h3 id="allianz-informationen" class="strong"><?=l::h(_("Allianz-Informationen"))?></h3>
<?php
					}
				}
?>
<dl class="allianceinfo">
	<dt class="c-allianztag"><?=l::h(_("Allianztag"))?></dt>
	<dd class="c-allianztag"><?=htmlspecialchars($alliance->getName())?></dd>

	<dt class="c-name"><?=l::h(_("Name"))?></dt>
	<dd class="c-name"><?=htmlspecialchars($alliance->name())?></dd>

	<dt class="c-ihr-rang"><?=l::h(_("Ihr Rang"))?></dt>
	<dd class="c-ihr-rang"><?=htmlspecialchars($alliance->getUserStatus($me->getName()))?></dd>

	<dt class="c-mitglieder"><?=l::h(_("Mitglieder"))?></dt>
	<dd class="c-mitglieder"><?=htmlspecialchars($alliance->getMembersCount())?> <span class="liste">(<a href="allianz.php?action=liste&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Mitgliederliste der Allianz einsehen [Z]" tabindex="<?=$tabindex++?>" accesskey="z">Liste</a>)</span></dd>

	<dt class="c-punkteschnitt"><?=l::h(_("Punkteschnitt"))?></dt>
	<dd class="c-punkteschnitt"><?=l::h(sprintf(_("%s (Platz %s von %s)"), F::ths($alliance->getScores(Alliance::HIGHSCORES_AVERAGE)), F::ths($alliance->getRank(Alliance::HIGHSCORES_AVERAGE)), F::ths(Alliance::getNumber())))?></dd>

	<dt class="c-gesamtpunkte"><?=l::h(_("Gesamtpunkte"))?></dt>
	<dd class="c-gesamtpunkte"><?=l::h(sprintf(_("%s (Platz %s von %s)"), F::ths($alliance->getScores(Alliance::HIGHSCORES_SUM)), F::ths($alliance->getRank(Alliance::HIGHSCORES_SUM)), F::ths(Alliance::getNumber())))?></dd>
</dl>
<?php
				if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_REMOVE) || $austreten || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_INTERNAL) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_EXTERNAL) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_PERMISSIONS))
				{
?>
<hr />
<ul class="allianz-aktionen possibilities">
<?php
					if($austreten)
					{
?>
	<li class="c-austreten"><a href="allianz.php?action=austreten&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" onclick="return confirm('<?=JS::jsentities(_("Wollen Sie wirklich aus der Allianz austreten?"))?>');"<?=l::accesskey_attr(_("Aus der Allianz austreten&[login/allianz.php|13]"))?>><?=l::h(_("Aus der Allianz austreten&[login/allianz.php|13]"))?></a></li>
<?php
					}
					if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_REMOVE))
					{
?>
	<li class="c-aufloesen"><a href="allianz.php?action=aufloesen&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" onclick="if(confirm('<?=JS::jsentities(_("Sind Sie sicher, dass Sie diese Allianz komplett auflösen und allen Mitgliedern die Mitgliedschaft kündigen wollen?"))?>')) return !confirm('<?=JS::jsentities(_("Haben Sie es sich doch noch anders überlegt?"))?>'); else return false;"<?=l::accesskey_attr(_("Allianz auflösen&[login/allianz.php|13]"))?>><?=l::h(_("Allianz auflösen&[login/allianz.php|13]"))?></a></li>
<?php
					}
					if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_INTERN))
					{
?>
	<li class="c-interner-bereich"><a href="allianz.php?action=intern&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("I&nternen Bereich bearbeiten[login/allianz.php|13]"))?>><?=l::h(_("I&nternen Bereich bearbeiten[login/allianz.php|13]"))?></a></li>
<?php
					}
					if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_EXTERN))
					{
?>
	<li class="c-externer-bereich"><a href="allianz.php?action=extern&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("E&xternen Bereich bearbeiten[login/alliance.php|13]"))?>><?=l::h(_("E&xternen Bereich bearbeiten[login/alliance.php|13]"))?></a></li>
<?php
					}
					if(isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_PERMISSIONS))
					{
?>
	<li class="c-benutzerrechte"><a href="allianz.php?action=permissions&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>"<?=l::accesskey_attr(_("Benutzerrechte ver&walten[login/alliance.php|3]"))?>><?=l::h(_("Benutzerrechte ver&walten[login/alliance.php|3]"))?></a></li>
<?php
					}
?>
</ul>
<?php
				}
?>
<h3 id="internes" class="strong"><?=l::h(_("Internes"))?></h3>
<div class="allianz-internes">
<?php
				print($alliance->getInternalDescription());
?>
</div>
<h3 id="externes" class="strong"><?=l::h(_("Externes"))?></h3>
<div class="allianz-externes">
<?php
				print($alliance->getExternalDescription());
?>
</div>
<?php
				if((isset($_SESSION["admin_username"]) || $alliance->checkUserPermissions($me->getName(), Alliance::PERMISSION_MAIL)) && $alliance->getMembersCount() > 1)
				{
?>
<h3 id="allianzrundschreiben" class="strong"><?=l::h(_("Allianzrundschreiben"))?></h3>
<?php
					if(isset($_POST['rundschreiben-text']) && strlen(trim($_POST['rundschreiben-text'])) > 0)
					{
						$message = Classes::Message(Message::create());
						$betreff = '';
						if(isset($_POST['rundschreiben-betreff']))
							$betreff = $_POST['rundschreiben-betreff'];
						if(strlen(trim($betreff)) <= 0)
							$betreff = 'Allianzrundschreiben';
						$message->subject($betreff);

						$message->from($me->getName());
						$message->text($_POST['rundschreiben-text']);

						$members = $alliance->getUsersList();
						foreach($members as $member)
						{
							if($member == $me->getName()) continue;
							$message->addUser($member, Message::TYPE_VERBUENDETE);
						}
?>
<p class="successful"><?=l::h(_("Das Allianzrundschreiben wurde erfolgreich versandt."))?></p>
<?php
					}
?>
<form action="allianz.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>#allianzrundschreiben" method="post" class="allianz-rundschreiben-form">
	<dl class="form">
		<dt class="c-betreff"><label for="allianz-rundschreiben-betreff-input"><?=l::h(_("Betreff&[login/allianz.php|13]"))?></label></dt>
		<dd class="c-betreff"><input type="text" name="rundschreiben-betreff" id="allianz-rundschreiben-betreff-input"<?=l::accesskey_attr(_("Betreff&[login/allianz.php|13]"))?> /></dd>

		<dt class="c-text"><label for="allianz-rundschreiben-text-textarea"><?=l::h(_("Text&[login/allianz.php|13]"))?></label></dt>
		<dd class="c-text"><textarea name="rundschreiben-text" id="allianz-rundschreiben-text-textarea" cols="50" rows="17"<?=l::accesskey_attr(_("Text&[login/allianz.php|13]"))?>></textarea></dd>
	</dl>
	<div class="button"><button type="submit"<?=l::accesskey_attr(_("Allianzrundschreiben verschicken&[login/allianz.php|13]"))?>><?=l::h(_("Allianzrundschreiben verschicken&[login/allianz.php|13]"))?></button></div>
</form>
<?php
				}
			}
		}
	}
?>
<?php
	$gui->end();
?>
