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

	# Werbung deaktivieren, weil beim vielen Herumklicken sonst das Laden ewig dauert
	$DISABLE_ADS = true;
	login_gui::html_head();

	$pos = $me->getPos();

	$galaxy_n = $pos[0];
	$system_n = $pos[1];
	if(isset($_GET["shortcut"]) && strpos($_GET["shortcut"], ":") !== false)
		list($galaxy_n, $system_n) = explode(":", $_GET["shortcut"]);
	else
	{
		if(isset($_GET['galaxy']))
			$galaxy_n = $_GET['galaxy'];
		if(isset($_GET['system']))
			$system_n = $_GET['system'];
	}
	
	$tabindex = 1;

	__autoload('Galaxy');
	$galaxy_count = getGalaxiesCount();

	$next_galaxy = $galaxy_n+1;
	$prev_galaxy = $galaxy_n-1;
	if($next_galaxy > $galaxy_count)
		$next_galaxy = 1;
	if($prev_galaxy < 1)
		$prev_galaxy = $galaxy_count;

	$galaxy = Classes::Galaxy($galaxy_n);

	$next_system = array(change_digit($system_n, 2, 1), change_digit($system_n, 1, 1), change_digit($system_n, 0, 1));
	$prev_system = array(change_digit($system_n, 2, -1), change_digit($system_n, 1, -1), change_digit($system_n, 0, -1));

	if($me->checkSetting('performance') > 1)
	{
		# Im JavaScript wird fuer JavaScript-Tooltips das Dummy-Attribut titleAttribute verwendet, damit sich die Tooltips nicht mit Browser-Tooltips ueberschneiden
		$title_attr = ($me->checkSetting('tooltips') ? 'titleAttribute' : 'title');

		$verb_list = $me->getVerbuendetList();
		if(count($verb_list) > 0)
		{
			$verb_jcheck = array();
			foreach($verb_list as $verb)
				$verb_jcheck[] = "username == '".str_replace("'", "\\'", $verb)."'";
			$verb_jcheck = implode(' || ', $verb_jcheck);
		}
		else $verb_jcheck = 'false';
?>
<script type="text/javascript">
// <![CDATA[
	var current_system = <?=$system_n?>;
	var min_preload = 5;
	var max_preload = 15;

	var my_planets = {
<?php
		$i = 1;
		$active_planet = $me->getActivePlanet();
		$echo = array();
		foreach($me->getPlanetsList() as $planet)
		{
			$me->setActivePlanet($planet);
			$echo[] = "\t\t'".jsentities($me->getPosString())."' : ".$i;
			$i++;
		}
		$me->setActivePlanet($active_planet);
		echo implode(",\n", $echo)."\n";
?>
	};
	var my_bookmarks = {
<?php
		$echo = array();
		$shortcuts_list = $me->getPosShortcutsList();
		foreach($shortcuts_list as $sc)
		{
			$echo[] = "\t\t'".jsentities($sc)."' : ".$i;
			$i++;
		}
		echo implode(",\n", $echo)."\n";
?>
	};
	var bookmark_active_tr = null;
	var prefer_bookmark = <?=isset($_GET["shortcut"]) ? "'".jsentities($_GET["shortcut"])."'" : "null"?>;

	function is_verbuendet(username)
	{
		return (<?=$verb_jcheck?>);
	}
	
	function add_bookmark(a_obj, a_i)
	{
		var new_system = '<?=$galaxy_n?>:'+current_system;
		if(typeof preloaded_systems[new_system] == 'undefined')
		{
			if(!preloading_systems[new_system])
				preload_systems(new_system);
			setTimeout(function(){add_bookmark(a_obj, new_system);}, 250);
			return true;
		}
		
		var info = preloaded_systems[new_system][a_i];
		var txt = new_system+':'+a_i+': ';
		if(!info.owner)
			txt += ' [<?=jsentities(_("unbesiedelt"))?>]';
		else
		{
			txt += info.name+' (';
			if(info.alliance) txt += '['+info.alliance+'] ';
			txt += info.owner+')';
		}
		
		a_obj.parentNode.parentNode.parentNode.parentNode.className += " active";
		if(bookmark_active_tr)
			bookmark_active_tr.className = bookmark_active_tr.className.replace(/ active$/g, "");
		my_bookmarks[new_system+':'+a_i] = document.getElementById("i-shortcut").options.length;
		document.getElementById("shortcut-optgroup-bookmarks").appendChild(new Option(txt, new_system+':'+i, true, true));
		a_obj.parentNode.parentNode.removeChild(a_obj.parentNode);
		return true;
	}

	function change_system(new_system)
	{
		system_split = new_system.split(/:/);
		if(typeof preloaded_systems[new_system] == 'undefined')
		{
			if(!preloading_systems[new_system])
				preload_systems(new_system);
			setTimeout("change_system('"+new_system+"');", 250);
			return;
		}

		tbody = document.getElementById('karte-system');
		sinfo = preloaded_systems[new_system];
		for(i=0,j=0; i<tbody.childNodes.length; i++)
		{
			if(tbody.childNodes[i].nodeType == 1)
			{
				if(j>=sinfo.length-1)
				{
					tbody.removeChild(tbody.childNodes[i]);
					i--;
				}
				else j++;
			}
			else
			{
				tbody.removeChild(tbody.childNodes[i]);
				i--;
			}
		}
		
		bookmark_active_tr = null;
		if(document.getElementById("i-shortcut").options[document.getElementById("i-shortcut").selectedIndex].value.replace(/^(\d+:\d+).*$/, "$1") != new_system)
		{
			if(prefer_bookmark && prefer_bookmark.replace(/^(\d+:\d+).*$/, "$1") == new_system && my_planets[prefer_bookmark])
				document.getElementById("i-shortcut").selectedIndex = my_planets[prefer_bookmark];
			else if(prefer_bookmark && prefer_bookmark.replace(/^(\d+:\d+).*$/, "$1") == new_system && my_bookmarks[prefer_bookmark])
				document.getElementById("i-shortcut").selectedIndex = my_bookmarks[prefer_bookmark];
			else
			{
				for(var i=0; i<document.getElementById("i-shortcut").options.length; i++)
				{
					if(document.getElementById("i-shortcut").options[i].value.replace(/^(\d+:\d+).*$/, "$1") == new_system)
					{
						document.getElementById("i-shortcut").selectedIndex = i;
						break;
					}
				}
			}
		}

		for(i=1; i<sinfo.length; i++)
		{
			if(tbody.childNodes[i-1])
			{
				new_tr = tbody.childNodes[i-1];
				while(new_tr.childNodes.length > 0) new_tr.removeChild(new_tr.firstChild);
			}
			else
			{
				new_tr = document.createElement('tr');
				tbody.appendChild(new_tr);
			}

			if(!sinfo[i]['owner'])
				new_tr.className = 'leer';
			else
			{
				if(sinfo[i]['owner'] == '<?=str_replace("'", "\\'", $me->getName())?>')
					new_tr.className = 'eigen';
				else if(is_verbuendet(sinfo[i]['owner']))
					new_tr.className = 'verbuendet';
				else
					new_tr.className = 'fremd';
				if(sinfo[i]['flag'] == 'U')
					new_tr.className += ' urlaub';
				else if(sinfo[i]['flag'] == 'g')
					new_tr.className += ' gesperrt';
			}

			var new_td = document.createElement('td');
			new_td.className = 'c-planet';
			new_td.appendChild(document.createTextNode(i));
			if(sinfo[i]['truemmerfeld'])
			{
				new_td.appendChild(document.createTextNode(' '));
				new_tf = document.createElement('abbr');
				new_tf.setAttribute('<?=$title_attr?>', 'Trümmerfeld: '+ths(sinfo[i]['truemmerfeld'][0])+'\u00a0Carbon, '+ths(sinfo[i]['truemmerfeld'][1])+'\u00a0Aluminium, '+ths(sinfo[i]['truemmerfeld'][2])+'\u00a0Wolfram, '+ths(sinfo[i]['truemmerfeld'][3])+'\u00a0Radium');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
				new_tf.onmouseover = show_title;
				new_tf.onmouseout = hide_title;
				new_tf.onmousemove = move_title;
<?php
		}
?>
				new_tf.appendChild(document.createTextNode('T'));
				new_td.appendChild(new_tf);
			}
			new_tr.appendChild(new_td);

			var new_td = document.createElement('td');
			new_td.className = 'c-name';
			if(sinfo[i]['owner'])
			{
				if(sinfo[i]['alliance'])
				{
					var new_alliance1 = document.createElement('span');
					if(sinfo[i]['alliance'] == '<?=str_replace("'", "\\'", $me->allianceTag())?>')
						new_alliance1.className = 'allianz-eigen';
					else
						new_alliance1.className = 'allianz-fremd';
					new_alliance1.appendChild(document.createTextNode('['));
					var new_alliance2 = document.createElement('a');
					new_alliance2.href = 'help/allianceinfo.php?alliance='+encodeURIComponent(sinfo[i]['alliance'])+'&<?=global_setting("URL_SUFFIX")?>';
					new_alliance2.className = "alliancename";
					new_alliance2.setAttribute('<?=$title_attr?>', 'Informationen zu dieser Allianz anzeigen');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
					new_alliance2.onmouseover = show_title;
					new_alliance2.onmouseout = hide_title;
					new_alliance2.onmousemove = move_title;
<?php
		}
?>
					new_alliance2.appendChild(document.createTextNode(sinfo[i]['alliance']));
					new_alliance1.appendChild(new_alliance2);
					new_alliance1.appendChild(document.createTextNode(']'));
					new_td.appendChild(new_alliance1);
					new_td.appendChild(document.createTextNode(' '));
				}
				new_td.appendChild(document.createTextNode(sinfo[i]['name']+' '));

				var playername1 = document.createElement('span');
				playername1.className = 'playername';
				playername1.appendChild(document.createTextNode('('));
				var playername2 = document.createElement('a');
				playername2.href = 'help/playerinfo.php?player='+encodeURIComponent(sinfo[i]['owner'])+'&<?=global_setting("URL_SUFFIX")?>';
				playername2.setAttribute('<?=$title_attr?>', 'Informationen zu diesem Spieler anzeigen');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
				playername2.onmouseover = show_title;
				playername2.onmouseout = hide_title;
				playername2.onmousemove = move_title;
<?php
		}
?>
				playername2.appendChild(document.createTextNode(sinfo[i]['owner']));
				playername1.appendChild(playername2);
				if(sinfo[i]['flag'])
					playername1.appendChild(document.createTextNode(' ('+sinfo[i]['flag']+')'));
				playername1.appendChild(document.createTextNode(')'));
				new_td.appendChild(playername1);
			}
			new_tr.appendChild(new_td);

			var new_td = document.createElement('td');
			new_td.className = 'c-aktionen';

			// Koordinaten verwenden
			new_td.appendChild(document.createElement('ul'));
			new_td.firstChild.appendChild(document.createElement('li'));
			new_td.firstChild.firstChild.className = 'c-koordinaten-verwenden';
			new_td.firstChild.firstChild.appendChild(document.createElement('a'));
			new_td.firstChild.firstChild.firstChild.setAttribute('<?=$title_attr?>', 'Die Koordinaten dieses Planeten ins Flottenmenü einsetzen');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
			new_td.onmouseover = show_title;
			new_td.onmouseout = hide_title;
			new_td.onmousemove = move_title;
<?php
		}
?>
			new_td.firstChild.firstChild.firstChild.href = 'flotten.php?action_galaxy='+encodeURIComponent(system_split[0])+'&action_system='+encodeURIComponent(system_split[1])+'&action_planet='+encodeURIComponent(i)+'&<?=global_setting("URL_SUFFIX")?>';
			new_td.firstChild.firstChild.firstChild.appendChild(document.createTextNode('Koordinaten verwenden'));

			if(typeof my_bookmarks[new_system+':'+i] == "undefined")
			{
				new_td.firstChild.appendChild(new_el1 = document.createElement('li'));
				new_el1.className = 'c-lesezeichen';
				new_el1.appendChild(new_el2 = document.createElement('a'));
				new_el2.href = 'flotten.php?action=shortcut&action_galaxy='+encodeURIComponent(system_split[0])+'&action_system='+encodeURIComponent(system_split[1])+'&action_planet='+encodeURIComponent(i)+'&<?=global_setting("URL_SUFFIX")?>';
				new_el2.setAttribute('<?=$title_attr?>', 'Die Koordinaten dieses Planeten zu den Lesezeichen hinzufügen');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
				new_el2.onmouseover = show_title;
				new_el2.onmouseout = hide_title;
				new_el2.onmousemove = move_title;
<?php
		}
?>
				new_el2.onclick = new Function('return (fast_action(this, "shortcut", '+system_split[0]+', '+system_split[1]+', '+i+') || !add_bookmark(this, '+i+'));');
				new_el2.appendChild(document.createTextNode('Lesezeichen'));
			}
			if(my_bookmarks[new_system+':'+i] == document.getElementById("i-shortcut").selectedIndex || my_planets[new_system+':'+i] == document.getElementById("i-shortcut").selectedIndex)
			{
				new_tr.className += " active";
				bookmark_active_tr = new_tr;
			}

			if(sinfo[i]['owner'] != '<?=str_replace("'", "\\'", $me->getName())?>')
			{
<?php
		if($me->permissionToAct() && $me->getItemLevel('S5', 'schiffe') > 0)
		{
?>
				// Spionieren
				if(sinfo[i]['flag'] != 'U'<?php if(fleets_locked()){?> && is_verbuendet(sinfo[i]['owner'])<?php }?>)
				{
					new_td.firstChild.appendChild(new_el1 = document.createElement('li'));
					new_el1.className = 'c-spionieren';
					new_el1.appendChild(new_el2 = document.createElement('a'));
					new_el2.href = 'flotten.php?action=spionage&action_galaxy='+encodeURIComponent(system_split[0])+'&action_system='+encodeURIComponent(system_split[1])+'&action_planet='+encodeURIComponent(i)+'&<?=global_setting("URL_SUFFIX")?>';
					new_el2.setAttribute('<?=$title_attr?>', 'Spionieren Sie diesen Planeten aus');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
					new_el2.onmouseover = show_title;
					new_el2.onmouseout = hide_title;
					new_el2.onmousemove = move_title;
<?php
		}
?>
					new_el2.onclick = new Function('return fast_action(this, "spionage", '+system_split[0]+', '+system_split[1]+', '+i+');');
					new_el2.appendChild(document.createTextNode('Spionieren'));
				}
<?php
		}
?>
				// Nachricht schreiben
				if(sinfo[i]['owner'])
				{
					new_td.firstChild.appendChild(new_el1 = document.createElement('li'));
					new_el1.className = 'c-nachricht';
					new_el1.appendChild(new_el2 = document.createElement('a'));
					new_el2.href = 'nachrichten.php?to='+encodeURIComponent(sinfo[i]['owner'])+'&<?=global_setting("URL_SUFFIX")?>';
					new_el2.setAttribute('<?=$title_attr?>', 'Schreiben Sie diesem Spieler eine Nachricht');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
					new_el2.onmouseover = show_title;
					new_el2.onmouseout = hide_title;
					new_el2.onmousemove = move_title;
<?php
		}
?>
					new_el2.appendChild(document.createTextNode('Nachricht'));
				}
<?php
		if($me->permissionToAct() && $me->checkPlanetCount() && $me->getItemLevel('S6', 'schiffe') > 0)
		{
?>
				// Besiedeln
				if(!sinfo[i]['owner'])
				{
					new_td.firstChild.appendChild(new_el1 = document.createElement('li'));
					new_el1.className = 'c-besiedeln';
					new_el1.appendChild(new_el2 = document.createElement('a'));
					new_el2.href = 'flotten.php?action=besiedeln&action_galaxy='+encodeURIComponent(system_split[0])+'&action_system='+encodeURIComponent(system_split[1])+'&action_planet='+encodeURIComponent(i)+'&<?=global_setting("URL_SUFFIX")?>';
					new_el2.setAttribute('<?=$title_attr?>', 'Schicken Sie ein Besiedelungsschiff zu diesem Planeten');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
					new_el2.onmouseover = show_title;
					new_el2.onmouseout = hide_title;
					new_el2.onmousemove = move_title;
<?php
		}
?>
					new_el2.onclick = new Function('return fast_action(this, "besiedeln", '+system_split[0]+', '+system_split[1]+', '+i+');');
					new_el2.appendChild(document.createTextNode('Besiedeln'));
				}
<?php
		}
		if($me->permissionToAct() && $me->getItemLevel('S3', 'schiffe') > 0)
		{
?>
				// Sammeln
				if(sinfo[i]['truemmerfeld'])
				{
					new_td.firstChild.appendChild(new_el1 = document.createElement('li'));
					new_el1.className = 'c-truemmerfeld';
					new_el1.appendChild(new_el2 = document.createElement('a'));
					new_el2.href = 'flotten.php?action=sammeln&action_galaxy='+encodeURIComponent(system_split[0])+'&action_system='+encodeURIComponent(system_split[1])+'&action_planet='+encodeURIComponent(i)+'&<?=global_setting("URL_SUFFIX")?>';
					new_el2.setAttribute('<?=$title_attr?>', 'Schicken Sie ausreichend Sammler zu diesem Trümmerfeld.');
<?php
		if($me->checkSetting('tooltips'))
		{
?>
					new_el2.onmouseover = show_title;
					new_el2.onmouseout = hide_title;
					new_el2.onmousemove = move_title;
<?php
		}
?>
					new_el2.onclick = new Function('return fast_action(this, "sammeln", '+system_split[0]+', '+system_split[1]+', '+i+');');
					new_el2.appendChild(document.createTextNode('Trümmerfeld'));
				}
<?php
		}
?>
			}
			new_tr.appendChild(new_td);
		}

		current_system = parseInt(system_split[1]);
		get_systems_around();
		var prev_system = get_system_before(current_system);
		var next_system = get_system_after(current_system);
		document.getElementById('current-system-number').value = current_system;
		document.getElementById('koords').firstChild.data = '(<?=$galaxy_n?>:'+current_system+')';
		document.getElementById('galaxy-prev-link').href = 'karte.php?galaxy=<?=urlencode($prev_galaxy)?>&system='+encodeURIComponent(current_system)+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('galaxy-next-link').href = 'karte.php?galaxy=<?=urlencode($next_galaxy)?>&system='+encodeURIComponent(current_system)+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-prev-link-1').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(prev_system[0])+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-prev-link-2').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(prev_system[1])+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-prev-link-3').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(prev_system[2])+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-next-link-1').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(next_system[0])+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-next-link-2').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(next_system[1])+'&<?=global_setting("URL_SUFFIX")?>';
		document.getElementById('system-next-link-3').href = 'karte.php?galaxy=<?=urlencode($galaxy_n)?>&system='+encodeURIComponent(next_system[2])+'&<?=global_setting("URL_SUFFIX")?>';
	}

	function get_systems_around()
	{
		var has_to_preload = false;
		var to_preload = new Object();

		if(typeof preloaded_systems['<?=$galaxy_n?>:'+current_system] == 'undefined' && !preloading_systems['<?=$galaxy_n?>:'+current_system])
			has_to_preload = true;
		to_preload['<?=$galaxy_n?>:'+current_system] = true;
		
		var next_systems = [ current_system, current_system, current_system ];
		var prev_systems = [ current_system, current_system, current_system ];
		for(i=0; i<max_preload; i++)
		{
			next_systems = [ get_system_after(next_systems[0])[0], get_system_after(next_systems[1])[1], get_system_after(next_systems[2])[2] ];
			prev_systems = [ get_system_before(prev_systems[0])[0], get_system_before(prev_systems[1])[1], get_system_before(prev_systems[3])[2] ];

			var systems = next_systems.concat(prev_systems);
			for(var j=0; j<systems.length; j++)
			{
				if(i<min_preload && typeof preloaded_systems['<?=$galaxy_n?>:'+systems[j]] == 'undefined' && !preloading_systems['<?=$galaxy_n?>:'+systems[j]])
					has_to_preload = true;
				to_preload['<?=$galaxy_n?>:'+systems[j]] = true;
			}
		}
		
		//var_dump(to_preload);

		to_preload2 = new Array();
		for(i in to_preload)
			to_preload2.push(i);

		if(has_to_preload)
		{
			preload_systems(to_preload2);
			for(i in preloaded_systems)
			{
				if(!to_preload[i])
					delete preloaded_systems[i];
			}
		}
	}

	function get_system_after(which_system)
	{
		return [ change_digit(which_system, 2, 1), change_digit(which_system, 1, 1), change_digit(which_system, 0, 1) ];
	}
	
	function get_system_before(which_system)
	{
		return [ change_digit(which_system, 2, -1), change_digit(which_system, 1, -1), change_digit(which_system, 0, -1) ];
	}

	function sw_next_system(idx)
	{
		change_system('<?=$galaxy_n?>:'+get_system_after(current_system)[idx]);
		return false;
	}

	function sw_prev_system(idx)
	{
		change_system('<?=$galaxy_n?>:'+get_system_before(current_system)[idx]);
		return false;
	}
	
	function doLookup()
	{
		if(document.getElementById("i-shortcut").options[document.getElementById("i-shortcut").selectedIndex].value)
		{
			var sc = document.getElementById("i-shortcut").options[document.getElementById("i-shortcut").selectedIndex].value.split(/:/);
			if(sc[0] != '<?=$galaxy_n?>') return true;
			if(sc[1] != current_system)
			{
				change_system('<?=$galaxy_n?>:'+sc[1]);
				return false;
			}
		}
		
		if(document.getElementById('current-galaxy-number').value == '<?=$galaxy_n?>')
		{
			change_system('<?=$galaxy_n?>:'+document.getElementById('current-system-number').value);
			return false;
		}
		
		document.getElementById("i-shortcut").selectedIndex = 0;
		return true;
	}
	
	onload = doLookup;
// ]]>
</script>
<?php
	}
?>
<h3 class="strong">Karte <span class="karte-koords" id="koords">(<?=htmlspecialchars($galaxy_n)?>:<?=htmlspecialchars($system_n)?>)</span></h3>
<form action="karte.php" method="get" class="karte-wahl"<?php if($me->checkSetting('performance') > 1){?> onsubmit="return doLookup();"<?php }?>>
	<fieldset>
		<legend>System</legend>
		<ul id="karte-navigation">
			<li id="karte-wahl-navigation-1"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($next_system[0]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="next" id="system-next-link-1"<?php if($me->checkSetting('performance') > 1){?> onclick="return sw_next_system(0);"<?php }?> title="<?=h(_("System-Dimension 1 erhöhen&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 1 erhöhen&[login/karte.php|1]"))?>>↑</a></li>
			<li id="karte-wahl-navigation-2"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($next_system[1]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="next" id="system-next-link-2"<?php if($me->checkSetting('performance') > 1){?> onclick="return sw_next_system(1);"<?php }?> title="<?=h(_("System-Dimension 2 erhöhen&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 2 erhöhen&[login/karte.php|1]"))?>>↗</a></li>
			<li id="karte-wahl-navigation-3"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($next_system[2]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="next" id="system-next-link-3"<?php if($me->checkSetting('performance') > 1){?> onclick="return sw_next_system(2);"<?php }?> title="<?=h(_("System-Dimension 3 erhöhen&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 3 erhöhen&[login/karte.php|1]"))?>>→</a></li>
			<li id="karte-wahl-navigation-4"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($prev_system[0]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="prev" id="system-prev-link-1"<?php if($me->checkSetting('performance')){?>  onclick="return sw_prev_system(0);"<?php }?> title="<?=h(_("System-Dimension 1 verringern&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 1 verringern&[login/karte.php|1]"))?>>↓</a></li>
			<li id="karte-wahl-navigation-5"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($prev_system[1]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="prev" id="system-prev-link-2"<?php if($me->checkSetting('performance')){?>  onclick="return sw_prev_system(1);"<?php }?> title="<?=h(_("System-Dimension 2 verringern&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 2 verringern&[login/karte.php|1]"))?>>↙</a></li>
			<li id="karte-wahl-navigation-6"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;system=<?=htmlspecialchars(urlencode($prev_system[2]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="prev" id="system-prev-link-3"<?php if($me->checkSetting('performance')){?>  onclick="return sw_prev_system(2);"<?php }?> title="<?=h(_("System-Dimension 3 verringern&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("System-Dimension 3 verringern&[login/karte.php|1]"))?>>←</a></li>
			<li id="karte-wahl-navigation-7"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($next_galaxy))?>&amp;system=<?=htmlspecialchars(urlencode($system_n))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="next" id="galaxy-next-link" title="<?=h(_("Zur nächsten Galaxie&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("Zur nächsten Galaxie&[login/karte.php|1]"))?>>↖</a></li>
			<li id="karte-wahl-navigation-8"><a href="karte.php?galaxy=<?=htmlspecialchars(urlencode($prev_galaxy))?>&amp;system=<?=htmlspecialchars(urlencode($system_n))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>" rel="prev" id="galaxy-prev-link" title="<?=h(_("Zur vorigen Galaxie&[login/karte.php|1]"), false)?>"<?=accesskey_attr(_("Zur vorigen Galaxie&[login/karte.php|1]"))?>>↘</a></li>
		</ul>
		<div id="karte-lesezeichen">
			<select name="shortcut" class="shortcuts" id="i-shortcut" tabindex="<?=($tabindex++)+2?>"<?php if($me->checkSetting('performance') > 1){?> onchange="if(doLookup()) this.form.submit();" onkeyup="onchange();"<?php }?>>
				<option value=""></option>
				<optgroup label="Eigene Planeten" id="shortcut-optgroup-own">
<?php
	foreach($me->getPlanetsList() as $planet)
	{
		$me->setActivePlanet($planet);
?>
					<option value="<?=htmlspecialchars($me->getPosString())?>"><?=h(vsprintf(_("%d:%d:%d"), $me->getPos()))?>: <?=preg_replace('/[\'\\\\]/', '\\\\\\0', htmlspecialchars($me->planetName()))?></option>
<?php
	}
	$me->setActivePlanet($active_planet);
?>
				</optgroup>
				<optgroup label="Lesezeichen" id="shortcut-optgroup-bookmarks">
<?php
	foreach($shortcuts_list as $shortcut)
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
		else $s .= "["._('unbesiedelt')."]";
?>
					<option value="<?=htmlspecialchars($shortcut)?>"><?=htmlspecialchars($s)?></option>
<?php
	}
?>
				</optgroup>
			</select>
			<ul class="actions"><li><a href="flotten_actions.php?action=shortcuts&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" class="lesezeichen-verwalten-link"<?=accesskey_attr(_("Lesezeichen verwalten&[login/karte.php|1]"))?>><?=h(_("Lesezeichen verwalten&[login/karte.php|1]"))?></a></li></ul>
		</div>
		<div id="karte-koordinaten"><input type="text" name="galaxy" value="<?=htmlspecialchars($galaxy_n)?>" tabindex="<?=($tabindex++)-1?>" id="current-galaxy-number" class="number number-koords" />:<input type="text" name="system" value="<?=htmlspecialchars($system_n)?>" tabindex="<?=($tabindex++)-1?>" id="current-system-number" class="number number-koords" /></div>
	</fieldset>
	<div class="karte-wahl-absenden button">
		<button type="submit" tabindex="<?=$tabindex++?>" accesskey="w"><kbd>W</kbd>echseln</button><?=global_setting("URL_FORMULAR")."\n"?>
	</div>
</form>
<table class="karte-system" id="karte">
	<thead>
		<tr>
			<th class="c-planet">Planet</th>
			<th class="c-name">Name (Eigentümer)</th>
			<th class="c-aktionen">Aktionen</th>
		</tr>
	</thead>
	<tbody id="karte-system">
<?php
	$planets_count = $galaxy->getPlanetsCount($system_n);
	$shortcut = null;
	if(isset($_GET["shortcut"]) && count($expl = explode(":", $_GET["shortcut"])) >= 3)
		$shortcut = $expl[2];
	for($i=1; $i <= $planets_count; $i++)
	{
		$planet = array(false, $galaxy->getPlanetOwner($system_n, $i), $galaxy->getPlanetName($system_n, $i), $galaxy->getPlanetOwnerAlliance($system_n, $i), $galaxy->getPlanetOwnerFlag($system_n, $i));
		$class = $galaxy->getPlanetClass($system_n, $i);

		$that_uname = $planet[1];

		if($planet[1])
		{
			if($that_uname == $me->getName())
				$class2 = 'eigen';
			elseif($me->isVerbuendet($that_uname))
				$class2 = 'verbuendet';
			else
				$class2 = 'fremd';
			if($planet[4] == 'U')
				$class2 .= ' urlaub';
			elseif($planet[4] == 'g')
				$class2 .= ' gesperrt';
		}
		else
			$class2 = 'leer';
		
		if(isset($shortcut) && $shortcut == $i)
			$class2 .= " active";
?>
		<tr class="<?=$class2?> planet_<?=$class?>">
<?php
		$truemmerfeld = truemmerfeld::get($galaxy_n, $system_n, $i);
		if($truemmerfeld !== false && array_sum($truemmerfeld) > 0)
			$tf_string = ' <abbr title="Trümmerfeld: '.ths($truemmerfeld[0]).'&nbsp;Carbon, '.ths($truemmerfeld[1]).'&nbsp;Aluminium, '.ths($truemmerfeld[2]).'&nbsp;Wolfram, '.ths($truemmerfeld[3]).'&nbsp;Radium">T</abbr>';
		else
			$tf_string = '';
?>
			<td class="c-planet"><?=htmlspecialchars($i)?><?=$tf_string?></td>
<?php
		if($planet[1])
		{
?>
			<td class="c-name"><?php if($planet[3]){?><span class="allianz<?=($planet[3] == $me->allianceTag()) ? '-eigen' : '-fremd'?>">[<a href="help/allianceinfo.php?alliance=<?=htmlspecialchars(urlencode($planet[3]))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Informationen zu dieser Allianz anzeigen" class="alliancename"><?=htmlspecialchars($planet[3])?></a>]</span> <?php }?><?=htmlspecialchars($planet[2])?> <span class="playername">(<a href="help/playerinfo.php?player=<?=htmlspecialchars(urlencode($that_uname))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Informationen zu diesem Spieler anzeigen"><?=htmlspecialchars($that_uname)?></a><?=$planet[4] ? ' ('.htmlspecialchars($planet[4]).')' : ''?>)</span></td>
<?php
		}
		else
		{
?>
			<td class="c-name"></td>
<?php
		}

		$show_sammeln = ($me->permissionToAct() && $me->getItemLevel('S3', 'schiffe') > 0 && array_sum($truemmerfeld) > 0);
?>
			<td class="c-aktionen">
				<ul>
					<li class="c-koordinaten-verwenden"><a href="flotten.php?action_galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;action_system=<?=htmlspecialchars(urlencode($system_n))?>&amp;action_planet=<?=htmlspecialchars(urlencode($i))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Die Koordinaten dieses Planeten ins Flottenmenü einsetzen">Koordinaten verwenden</a></li>
<?php
		if(!in_array($galaxy_n.':'.$system_n.':'.$i, $me->getPosShortcutsList()))
		{
?>
					<li class="c-lesezeichen"><a href="flotten.php?action=shortcut&amp;action_galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;action_system=<?=htmlspecialchars(urlencode($system_n))?>&amp;action_planet=<?=htmlspecialchars(urlencode($i))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Die Koordinaten dieses Planeten zu den Lesezeichen hinzufügen"<?php if($me->checkSetting('performance') > 1){?> onclick="return (fast_action(this, 'shortcut', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>) || !add_bookmark(this, <?=$i?>));"<?php }?>>Lesezeichen</a></li>
<?php
		}

		if($that_uname != $me->getName())
		{
			if($planet[4] != 'U' && $me->permissionToAct() && $me->getItemLevel('S5', 'schiffe') > 0 && (!fleets_locked() || $me->isVerbuendet($that_uname)))
			{
?>
					<li class="c-spionieren"><a href="flotten.php?action=spionage&amp;action_galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;action_system=<?=htmlspecialchars(urlencode($system_n))?>&amp;action_planet=<?=htmlspecialchars(urlencode($i))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Spionieren Sie diesen Planeten aus"<?php if($me->checkSetting('performance') > 1){?> onclick="return fast_action(this, 'spionage', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Spionieren</a></li>
<?php
			}

			if($planet[1])
			{
?>
					<li class="c-nachricht"><a href="nachrichten.php?to=<?=htmlspecialchars(urlencode($that_uname))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Schreiben Sie diesem Spieler eine Nachricht">Nachricht</a></li>
<?php
			}

			if(!$planet[1] && $me->permissionToAct() && $me->checkPlanetCount() && $me->getItemLevel('S6', 'schiffe') > 0)
			{
?>
					<li class="c-besiedeln"><a href="flotten.php?action=besiedeln&amp;action_galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;action_system=<?=htmlspecialchars(urlencode($system_n))?>&amp;action_planet=<?=htmlspecialchars(urlencode($i))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Schicken Sie ein Besiedelungsschiff zu diesem Planeten"<?php if($me->checkSetting('performance') > 1){?>  onclick="return fast_action(this, 'besiedeln', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Besiedeln</a></li>
<?php
			}
		}

		if($show_sammeln)
		{
?>
					<li class="c-truemmerfeld"><a href="flotten.php?action=sammeln&amp;action_galaxy=<?=htmlspecialchars(urlencode($galaxy_n))?>&amp;action_system=<?=htmlspecialchars(urlencode($system_n))?>&amp;action_planet=<?=htmlspecialchars(urlencode($i))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="Schicken Sie ausreichend Sammler zu diesem Trümmerfeld"<?php if($me->checkSetting('performance') > 1){?>  onclick="return fast_action(this, 'sammeln', <?=$galaxy_n?>, <?=$system_n?>, <?=$i?>);"<?php }?>>Trümmerfeld</a></li>
<?php
		}
?>
				</ul>
			</td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
<script type="text/javascript">
	if(document.clientWidth) // http://www.lipfert-malik.de/webdesign/tutorial/bsp/browser_js_test.html?alph#Detail
	{
		// Prevent Konqueror crash (http://bugs.kde.org/show_bug.cgi?id=129253)
		document.getElementById('karte').style.borderCollapse = "separate";
	}
	get_systems_around();
</script>
<?php
	login_gui::html_foot();
?>
