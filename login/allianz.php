<?php
	require('scripts/include.php');

	function sort_members_list($a, $b)
	{
		if(isset($_GET['invert']) && $_GET['invert']) $invert = -1;
		else $invert = 1;
		if($_GET['sortby'] == 'punkte' || $_GET['sortby'] == 'time')
		{
			if($a[$_GET['sortby']] > $b[$_GET['sortby']]) return $invert;
			elseif($a[$_GET['sortby']] < $b[$_GET['sortby']]) return -$invert;
			else return 0;
		}
		else
		{
			$cmp = strnatcasecmp($a[$_GET['sortby']], $b[$_GET['sortby']]);
			if($cmp < 0) return -$invert;
			elseif($cmp > 0) return $invert;
			else return 0;
		}
	}

	login_gui::html_head();

	$action = false;
	if(isset($_GET['action']))
		$action = $_GET['action'];

	if(!$user_array['alliance'] || !is_file(DB_ALLIANCES.'/'.urlencode($user_array['alliance'])) || !is_readable(DB_ALLIANCES.'/'.urlencode($user_array['alliance'])))
	{
		if(isset($user_array['alliance_bewerbung']) && $user_array['alliance_bewerbung'])
		{
			if($action == 'cancel')
			{
				$alliance_array = get_alliance_array($user_array['alliance_bewerbung']);

				$key = (isset($alliance_array['bewerbungen']) ? array_search($_SESSION['username'], $alliance_array['bewerbungen']) : false);
				if($key !== false)
				{
					unset($alliance_array['bewerbungen'][$key]);
					write_alliance_array($user_array['alliance_bewerbung'], $alliance_array);

					unset($user_array['alliance_bewerbung']);
					write_user_array();

					$recipients = array();
					foreach($alliance_array['members'] as $name=>$member)
					{
						if($member['permissions'][4])
							$recipients[$name] = 7;
					}

					messages::new_message($recipients, $_SESSION['username'], "Allianzbewerbung zur\xc3\xbcckgezogen", 'Der Benutzer '.$_SESSION['username']." hat seine Bewerbung bei Ihrer Allianz zur\xc3\xbcckgezogen.");
				}
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<p class="successful">Ihre Bewerbung wurde zurückgezogen.</p>
<?php
			}
			else
			{
?>
<h2>Allianz</h2>
<p class="allianz-laufende-bewerbung">Sie haben derzeit eine laufende Bewerbung bei der Allianz <a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($user_array['alliance_bewerbung']))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"><?=utf8_htmlentities($user_array['alliance_bewerbung'])?></a>.</p>
<ul class="allianz-laufende-bewerbung-aktionen">
	<li><a href="allianz.php?action=cancel&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Bewerbung zurückziehen</a></li>
</ul>
<?php
			}
		}
		elseif($action == 'gruenden')
		{
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<?php
			if(isset($_POST['tag']) && isset($_POST['name']))
			{
				$_POST['tag'] = trim($_POST['tag']);
				if(strlen($_POST['tag']) < 2)
				{
?>
<p class="error">Das Allianz<span xml:lang="en">tag</span> muss mindestens zwei <span xml:lang="en">Bytes</span> lang sein.</p>
<?php
				}
				elseif(strlen($_POST['tag']) > 6)
				{
?>
<p class="error">Das Allianz<span xml:lang="en">tag</span> darf höchstens sechs <span xml:lang="en">Bytes</span> lang sein.</p>
<?php
				}
				elseif(file_exists(DB_ALLIANCES.'/'.urlencode($_POST['tag'])))
				{
?>
<p class="error">Es gibt schon eine Allianz mit diesem <span xml:lang="en">Tag</span>.</p>
<?php
				}
				else
				{
					$alliance_array = array(
						'tag' => $_POST['tag'],
						'name' => $_POST['name'],
						'members' => array(
							$_SESSION['username'] => array(
								'punkte' => floor($user_array['punkte'][0]+$user_array['punkte'][1]+$user_array['punkte'][2]+$user_array['punkte'][3]+$user_array['punkte'][4]+$user_array['punkte'][5]+$user_array['punkte'][6]),
								'rang' => "Gr\xc3\xbcnder",
								'time' => time(),
								'permissions' => array(true, true, true, true, true, true, true, true, true)
							)
						),
						'description' => '',
						'description_parsed' => '',
						'inner_description' => '',
						'inner_description_parsed' => ''
					);
					
					$highscores_info = highscores_alliances::make_info($_POST['tag'], $alliance_array['members'][$_SESSION['username']]['punkte']);
					$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'a');
					if(!$fh)
					{
?>
<p class="error">Datenbankfehler.</p>
<?php
					}
					else
					{
						flock($fh, LOCK_EX);
						
						fwrite($fh, $highscores_info);
						
						flock($fh, LOCK_UN);
						fclose($fh);
						
						$alliance_array['platz'] = highscores_alliances::get_alliances_count();
	
						if(!write_alliance_array($_POST['tag'], $alliance_array))
						{
?>
<p class="error">Datenbankfehler.</p>
<?php
						}
						else
						{
							$user_array['alliance'] = $_POST['tag'];
							write_user_array();
							highscores::recalc();
	
							$planets = array_keys($user_array['planets']);
							$pos = array();
							foreach($planets as $planet)
								$pos[] = $user_array['planets'][$planet]['pos'];
							$infos = universe::get_planet_info($pos);
							foreach($planets as $planet)
							{
								$this_pos = explode(':', $user_array['planets'][$planet]['pos']);
								$this_info = $infos[$user_array['planets'][$planet]['pos']];
								universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], $user_array['alliance']);
							}
?>
<p class="successful">Die Allianz <?=utf8_htmlentities($_POST['tag'])?> wurde erfolgreich gegründet.</p>
<?php
							login_gui::html_foot();
							exit();
						}
					}
				}
			}
?>
<form action="allianz.php?action=gruenden&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-grunden-form">
	<dl>
		<dt><label for="allianztag-input">Allianz<span xml:lang="en">tag</span></label></dt>
		<dd><input type="text" name="tag" id="allianztag-input" value="<?=isset($_POST['tag']) ? utf8_htmlentities($_POST['tag']) : ''?>" title="Das Allianztag wird in der Karte und in den Highscores vor dem Benutzernamen angezeigt." maxlength="6" /></dd>

		<dt><label for="allianzname-input">Allianzname</label></dt>
		<dd><input type="text" name="name" id="allianzname-input" value="<?=isset($_POST['name']) ? utf8_htmlentities($_POST['name']) : ''?>" /></dd>
	</dl>
	<div><button type="submit">Allianz gründen</button></div>
</form>
<?php
		}
		elseif($action == 'apply')
		{
			if(!isset($_GET['for']))
				$_GET['for'] = '';
			if(!is_file(DB_ALLIANCES.'/'.urlencode($_GET['for'])))
			{
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<p class="error">Diese Allianz gibt es nicht.</p>
<?php
			}
			elseif(!isset($_POST['text']))
			{
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<form action="allianz.php?action=apply&amp;for=<?=htmlentities(urlencode($_GET['for']))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-bewerben-form">
	<dl>
		<dt><label for="bewerbungstext-textarea">Bewerbungstext</label></dt>
		<dd><textarea name="text" id="bewerbungstext-textarea" cols="50" rows="17"></textarea></dd>
	</dl>
	<div><button type="submit">Bewerbung absenden</button></div>
</form>
<?php
			}
			else
			{
				$alliance_array = get_alliance_array($_GET['for']);
				$recipients = array();
				foreach($alliance_array['members'] as $name=>$member)
				{
					if($member['permissions'][4])
						$recipients[$name] = 7;
				}
				$message_text = "Der Benutzer ".$_SESSION['username']." hat sich bei Ihrer Allianz beworben. Gehen Sie auf Ihre Allianzseite, um die Bewerbung anzunehmen oder abzulehnen.";
				if(trim($_POST['text']) == '')
					$message_text .= "\n\nDer Bewerber hat keinen Bewerbungstext hinterlassen.";
				else
					$message_text .= "\n\nDer Bewerber hat folgenden Bewerbungstext hinterlassen:\n\n".$_POST['text'];
				messages::new_message($recipients, $_SESSION['username'], 'Neue Allianzbewerbung', $message_text);

				if(!isset($alliance_array['bewerbungen']))
					$alliance_array['bewerbungen'] = array();
				$alliance_array['bewerbungen'][] = $_SESSION['username'];

				write_alliance_array($_GET['for'], $alliance_array);

				$user_array['alliance_bewerbung'] = $_GET['for'];
				write_user_array();
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<p class="successful">Ihre Bewerbung wurde erfolgreich abgesandt.</p>
<?php
			}
		}
		else
		{
?>
<h2>Allianz</h2>
<p class="allianz-keine">Sie gehören derzeit keiner Allianz an. Es bieten sich Ihnen zwei Möglichkeiten.</p>
<form action="allianz.php" method="get" class="allianz-moeglichkeiten">
	<ul>
		<li><a href="allianz.php?action=gruenden&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Eigene Allianz gründen</a></li>
		<li><input type="text" name="search" value="<?=(isset($_GET['search'])) ? utf8_htmlentities($_GET['search']) : ''?>" /> <button type="submit">Allianz suchen</button><input type="hidden" name="<?=htmlentities(SESSION_COOKIE)?>" value="<?=htmlentities(session_id())?>" /></li>
	</ul>
</form>
<?php
			if(isset($_GET['search']) && $_GET['search'])
			{
?>
<h3 id="allianz-suchergebnisse">Suchergebnisse</h3>
<?php
				$i = 0;
				$preg = '/^'.str_replace(array('\\*', '\\?'), array('.*', '.?'), preg_quote($_GET['search'], '/')).'$/i';
				$alliances = array();
				$dh = opendir(DB_ALLIANCES);
				while(($fname = readdir($dh)) !== false)
				{
					if(!is_file(DB_ALLIANCES.'/'.$fname) || !is_readable(DB_ALLIANCES.'/'.$fname))
						continue;
					$alliance = urldecode($fname);
					if(preg_match($preg, $alliance))
						$alliances[] = $alliance;
				}
				closedir($dh);

				if(count($alliances) <= 0)
				{
?>
<p class="error">Es wurden keine Allianzen gefunden.</p>
<?php
				}
				else
				{
					natcasesort($alliances);
?>
<ul class="allianz-suchergebnisse">
<?php
					foreach($alliances as $alliance)
					{
?>
	<li><a href="help/allianceinfo.php?alliance=<?=htmlentities(urlencode($alliance))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Genauere Informationen anzeigen"><?=utf8_htmlentities($alliance)?></a></li>
<?php
					}
?>
</ul>
<?php
				}
?>
</ul>
<?php
			}
		}
	}
	else
	{
		if($action == 'liste')
		{
			$sort = (isset($_GET['sortby']) && in_array($_GET['sortby'], array('punkte', 'rang', 'time')));
			if(!isset($_GET['invert']) || !$_GET['invert'])
				$invert = '&amp;invert=1';
			else
				$invert = '';
			$alliance_array = get_alliance_array($user_array['alliance']);
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<?php
			if($alliance_array['members'][$_SESSION['username']]['permissions'][6])
			{
?>
<form action="allianz.php?action=liste<?=isset($_GET['sortby']) ? '&amp;sortby='.htmlentities(urlencode($_GET['sortby'])) : ''?><?=isset($_GET['invert']) ? '&amp;invert='.htmlentities(urlencode($_GET['invert'])) : ''?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-liste-form">
<?php
			}
			if($alliance_array['members'][$_SESSION['username']]['permissions'][5])
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
			<th class="c-name"><a href="allianz.php?action=liste<?=$sort ? '' : $invert?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Nach Namen sortieren">Name</a></th>
			<th class="c-rang"><a href="allianz.php?action=liste&amp;sortby=rang<?=($sort && $_GET['sortby'] == 'rang') ? $invert : ''?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Nach Rang sortieren">Rang</a></th>
			<th class="c-punkte"><a href="allianz.php?action=liste&amp;sortby=punkte<?=($sort && $_GET['sortby'] == 'punkte') ? $invert : ''?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Nach Punkten sortieren">Punkte</a></th>
			<th class="c-aufnahmezeit"><a href="allianz.php?action=liste&amp;sortby=time<?=($sort && $_GET['sortby'] == 'time') ? $invert : ''?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Nach Aufnahmezeit sortieren">Aufnahmezeit</a></th>
<?php
			if($alliance_array['members'][$_SESSION['username']]['permissions'][5])
			{
?>
			<th class="c-kick">Kick</th>
<?php
			}
?>
		</tr>
	</thead>
	<tbody>
<?php
			$liste = $alliance_array['members'];
			if($sort)
			{
				uasort($liste, 'sort_members_list');
				$member_names = array_keys($liste);
			}
			else
			{
				$member_names = array_keys($liste);
				natcasesort($member_names);
				if(isset($_GET['invert']) && $_GET['invert'])
					$member_names = array_reverse($member_names);
			}

			$changed = false;

			foreach($member_names as $i=>$member_name)
			{
				if($alliance_array['members'][$_SESSION['username']]['permissions'][5] && isset($_POST['kick']) && isset($_POST['kick'][$i]) && $member_name != $_SESSION['username'])
				{
					unset($alliance_array['members'][$member_name]);
					$changed = true;

					$that_user_array = get_user_array($member_name);
					$that_user_array['alliance'] = false;
					write_user_array($member_name, $that_user_array);

					messages::new_message(array($member_name=>7), '', "Allianzmitgliedschaft gek\xc3\xbcndigt", "Sie wurden aus der Allianz ".$user_array['alliance']." geworfen.");

					$planets = array_keys($that_user_array['planets']);
					$pos = array();
					foreach($planets as $planet)
						$pos[] = $that_user_array['planets'][$planet]['pos'];
					$infos = universe::get_planet_info($pos);
					foreach($planets as $planet)
					{
						$this_pos = explode(':', $that_user_array['planets'][$planet]['pos']);
						$this_info = $infos[$that_user_array['planets'][$planet]['pos']];
						universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], '');
					}

					unset($that_user_array);

					highscores::recalc($member_name);

					continue;
				}

				$member_info = $liste[$member_name];
?>
		<tr>
			<th class="c-name"><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($member_name))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($member_name)?></a></th>
<?php
				if($alliance_array['members'][$_SESSION['username']]['permissions'][6])
				{
					if(isset($_POST['rang']) && isset($_POST['rang'][$i]))
					{
						$member_info['rang'] = $alliance_array['members'][$member_name]['rang'] = $_POST['rang'][$i];
						$changed = true;
					}
?>
			<td class="c-rang"><input type="text" name="rang[<?=utf8_htmlentities($i)?>]" value="<?=utf8_htmlentities($member_info['rang'])?>" /></td>
<?php
				}
				else
				{
?>
			<td class="c-rang"><?=utf8_htmlentities($member_info['rang'])?></td>
<?php
				}
?>
			<td class="c-punkte"><?=ths($member_info['punkte'])?></td>
			<td class="c-aufnahmezeit"><?=date('Y-m-d, H:i:s', $member_info['time'])?></td>
<?php
				if($alliance_array['members'][$_SESSION['username']]['permissions'][5])
				{
?>
			<td class="c-kick"><input type="checkbox" name="kick[<?=utf8_htmlentities($i)?>]" onchange="if(!kick_warned){ kick_warned=true; alert('Die ausgewählten Benutzer werden beim Speichern der Änderungen aus der Allianz geworfen.'); }"<?=($member_name == $_SESSION['username']) ? ' disabled="disabled"' : ''?> /></td>
<?php
				}
?>
		</tr>
<?php
			}

			if($changed)
				write_alliance_array($user_array['alliance'], $alliance_array);
?>
	</tbody>
</table>
<?php
			if($alliance_array['members'][$_SESSION['username']]['permissions'][6])
			{
?>
	<div><button type="submit">Änderungen speichern</button></div>
</form>
<?php
			}
		}
		else
		{
			$alliance_array = get_alliance_array($user_array['alliance']);

			$austreten = !$alliance_array['members'][$_SESSION['username']]['permissions'][7];
			if(!$austreten)
			{
				foreach($alliance_array['members'] as $name=>$member)
				{
					if($name == $_SESSION['username'])
						continue;
					if($member['permissions'][7])
					{
						$austreten = true;
						break;
					}
				}
			}

			if($alliance_array['members'][$_SESSION['username']]['permissions'][8] && $action == 'aufloesen')
			{
				$members = array_keys($alliance_array['members']);

				$recipients = array();
				foreach($members as $member)
					$recipients[$member] = 7;
				messages::new_message($recipients, $_SESSION['username'], "Allianz aufgel\xc3\xb6st", 'Die Allianz '.$user_array['alliance']." wurde aufgel\xc3\xb6st.");

				foreach($members as $member)
				{
					if($member == $_SESSION['username'])
						continue;
					$that_user_array = get_user_array($member);
					$that_user_array['alliance'] = false;
					write_user_array($member, $that_user_array);

					$planets = array_keys($that_user_array['planets']);
					$pos = array();
					foreach($planets as $planet)
						$pos[] = $that_user_array['planets'][$planet]['pos'];
					$infos = universe::get_planet_info($pos);
					foreach($planets as $planet)
					{
						$this_pos = explode(':', $that_user_array['planets'][$planet]['pos']);
						$this_info = $infos[$that_user_array['planets'][$planet]['pos']];
						universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], '');
					}

					highscores::recalc($member);
				}

				unlink(DB_ALLIANCES.'/'.urlencode($user_array['alliance']));

				$user_array['alliance'] = false;
				write_user_array();

				$planets = array_keys($user_array['planets']);
				$pos = array();
				foreach($planets as $planet)
					$pos[] = $user_array['planets'][$planet]['pos'];
				$infos = universe::get_planet_info($pos);
				foreach($planets as $planet)
				{
					$this_pos = explode(':', $user_array['planets'][$planet]['pos']);
					$this_info = $infos[$user_array['planets'][$planet]['pos']];
					universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], '');
				}

				highscores::recalc();
				
				# Aus den Allianz-Highscores entfernen
				$fh = fopen(DB_HIGHSCORES_ALLIANCES, 'r+');
				flock($fh, LOCK_EX);
				fseek($fh, $alliance_array['platz']*14, SEEK_SET);
				$filesize = filesize(DB_HIGHSCORES_ALLIANCES);
				
				while(true)
				{
					if($filesize-ftell($fh) < 14)
						break;
					$line = fread($fh, 14);
					$info = highscores_alliances::get_info($line);
					$that_alliance_array = get_alliance_array($info[0]);
					$that_alliance_array['platz']--;
					write_alliance_array($info[0], $that_alliance_array);
					
					fseek($fh, -28, SEEK_CUR);
					fwrite($fh, $line);
					fseek($fh, 14, SEEK_CUR);
				}
				ftruncate($fh, $filesize-14);
				
				flock($fh, LOCK_UN);
				fclose($fh);
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<p class="successful">Die Allianz wurde aufgelöst.</p>
<?php
			}
			elseif($austreten && $action == 'austreten')
			{
				$recipients = array();
				foreach($alliance_array['members'] as $name=>$member)
					$recipients[$name] = 7;
				unset($recipients[$_SESSION['username']]);
				messages::new_message($recipients, $_SESSION['username'], 'Benutzer aus Allianz ausgetreten', 'Der Benutzer '.$_SESSION['username'].' hat Ihre Allianz verlassen.');

				unset($alliance_array['members'][$_SESSION['username']]);
				write_alliance_array($user_array['alliance'], $alliance_array);
				
				highscores_alliances::recalc($user_array['alliance']);

				$user_array['alliance'] = false;
				write_user_array();
				
				$planets = array_keys($user_array['planets']);
				$pos = array();
				foreach($planets as $planet)
					$pos[] = $user_array['planets'][$planet]['pos'];
				$infos = universe::get_planet_info($pos);
				foreach($planets as $planet)
				{
					$this_pos = explode(':', $user_array['planets'][$planet]['pos']);
					$this_info = $infos[$user_array['planets'][$planet]['pos']];
					universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], '');
				}
				
				highscores::recalc();
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<p class="successful">Sie haben die Allianz verlassen.</p>
<?php
			}
			elseif($alliance_array['members'][$_SESSION['username']]['permissions'][2] && $action == 'intern')
			{
				if(isset($_POST['intern-text']))
				{
					$alliance_array['inner_description'] = $_POST['intern-text'];
					$alliance_array['inner_description_parsed'] = parse_html($alliance_array['inner_description']);
					write_alliance_array($user_array['alliance'], $alliance_array);
				}
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<form action="allianz.php?action=intern&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-intern-form">
	<dl>
		<dt><label for="allianz-intern-textarea">Interner Allianztext</label></dt>
		<dd><textarea name="intern-text" id="allianz-intern-textarea" cols="50" rows="17"><?=preg_replace("/[\t\r\n]/e", "'&#'.ord('\$0').';'", utf8_htmlentities($alliance_array['inner_description']))?></textarea></dd>
	</dl>
	<div><button type="submit">Speichern</button></div>
</form>
<?php
			}
			elseif($alliance_array['members'][$_SESSION['username']]['permissions'][3] && $action == 'extern')
			{
				if(isset($_POST['extern-text']))
				{
					$alliance_array['description'] = $_POST['extern-text'];
					$alliance_array['description_parsed'] = parse_html($alliance_array['description']);
					if(isset($_POST['extern-name']))
						$alliance_array['name'] = $_POST['extern-name'];
					write_alliance_array($user_array['alliance'], $alliance_array);
				}
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<form action="allianz.php?action=extern&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-extern-form">
	<dl>
		<dt class="c-name"><label for="allianz-name-input">Allianzname</label></dt>
		<dd class="c-name"><input type="text" name="extern-name" id="allianz-name-input" value="<?=utf8_htmlentities($alliance_array['name'])?>" /></dd>

		<dt class="c-text"><label for="allianz-extern-textarea">Externer Allianztext</label></dt>
		<dd class="c-text"><textarea name="extern-text" id="allianz-extern-textarea" cols="50" rows="17"><?=preg_replace("/[\t\r\n]/e", "'&#'.ord('\$0').';'", utf8_htmlentities($alliance_array['description']))?></textarea></dd>
	</dl>
	<div><button type="submit">Speichern</button></div>
</form>
<?php
			}
			elseif($alliance_array['members'][$_SESSION['username']]['permissions'][7] && $action == 'permissions')
			{
?>
<h2><a href="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Zurück zur Allianzübersicht">Allianz</a></h2>
<form action="allianz.php?action=permissions&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" method="post" class="allianz-rechte-form">
	<table>
		<thead>
			<tr>
				<th>Mitglied</th>
				<th><abbr title="Rundschreiben">Rundschr.</abbr></th>
				<th title="Koordinaten der anderen Allianzmitglieder einsehen">Koordinaten</th>
				<th title="Internen Bereich bearbeiten">Intern</th>
				<th title="Externen Bereich bearbeiten">Extern</th>
				<th title="Bewerbungen annehmen oder ablehnen">Bewerbungen</th>
				<th title="Mitglieder aus der Allianz werfen"><span xml:lang="en">Kick</span></th>
				<th title="Ränge verteilen">Ränge</th>
				<th title="Benutzerrechte verteilen">Rechte</th>
				<th title="Bündnis auflösen">Auflösen</th>
			</tr>
		</thead>
		<tbody>
<?php
				$member_names = array_keys($alliance_array['members']);
				natcasesort($member_names);
				$changed = false;
				foreach($member_names as $i=>$member_name)
				{
					if(isset($_POST['permissions']) && isset($_POST['permissions'][$i]))
					{
						$alliance_array['members'][$member_name]['permissions'] = array(isset($_POST['permissions'][$i][0]), isset($_POST['permissions'][$i][1]), isset($_POST['permissions'][$i][2]), isset($_POST['permissions'][$i][3]), isset($_POST['permissions'][$i][4]), isset($_POST['permissions'][$i][5]), isset($_POST['permissions'][$i][6]), isset($_POST['permissions'][$i][7]), isset($_POST['permissions'][$i][8]));
						if($member_name == $_SESSION['username'])
							$alliance_array['members'][$member_name]['permissions'][7] = true;
						$changed = true;
					}
					$member_info = $alliance_array['members'][$member_name];
?>
			<tr>
				<th><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($member_name))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Informationen zu diesem Spieler anzeigen"><?=utf8_htmlentities($member_name)?></a><input type="hidden" name="permissions[<?=utf8_htmlentities($i)?>][9]" value="on" /></th>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][0]"<?=$member_info['permissions'][0] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][1]"<?=$member_info['permissions'][1] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][2]"<?=$member_info['permissions'][2] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][3]"<?=$member_info['permissions'][3] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][4]"<?=$member_info['permissions'][4] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][5]"<?=$member_info['permissions'][5] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][6]"<?=$member_info['permissions'][6] ? ' checked="checked"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][7]"<?=$member_info['permissions'][7] ? ' checked="checked"' : ''?><?=($member_name == $_SESSION['username']) ? ' disabled="disabled"' : ''?> /></td>
				<td><input type="checkbox" name="permissions[<?=utf8_htmlentities($i)?>][8]"<?=$member_info['permissions'][8] ? ' checked="checked"' : ''?> /></td>
			</tr>
<?php
				}

				if($changed)
					write_alliance_array($user_array['alliance'], $alliance_array);
?>
		</tbody>
	</table>
	<div><button type="submit">Speichern</button></div>
</form>
<?php
			}
			else
			{
?>
<h2>Allianz</h2>
<?php
				if($alliance_array['members'][$_SESSION['username']]['permissions'][4])
				{
					if($action == 'annehmen' && isset($_GET['which']) && isset($alliance_array['bewerbungen']) && in_array($_GET['which'], $alliance_array['bewerbungen']))
					{
						$recipients = array();
						foreach($alliance_array['members'] as $name=>$member)
							$recipients[$name] = 7;
						unset($recipients[$_SESSION['username']]);
						messages::new_message($recipients, $_SESSION['username'], 'Neues Allianzmitglied', 'Ein neues Mitglied wurde in Ihre Allianz aufgenommen: '.$_GET['which']);
						messages::new_message(array($_GET['which']=>7), '', 'Allianzbewerbung angenommen', 'Ihre Bewerbung bei der Allianz '.$user_array['alliance'].' wurde angenommen.');

						$that_user_array = get_user_array($_GET['which']);
						unset($that_user_array['alliance_bewerbung']);
						$that_user_array['alliance'] = $user_array['alliance'];

						if(!isset($alliance_array['members'][$_GET['which']]))
						{
							$alliance_array['members'][$_GET['which']] = array(
								'punkte' => floor($that_user_array['punkte'][0]+$that_user_array['punkte'][1]+$that_user_array['punkte'][2]+$that_user_array['punkte'][3]+$that_user_array['punkte'][4]+$that_user_array['punkte'][5]+$that_user_array['punkte'][6]),
								'permissions' => array(false, false, false, false, false, false, false, false, false),
								'rang' => 'Neuling',
								'time' => time()
							);
						}
						unset($alliance_array['bewerbungen'][array_search($_GET['which'], $alliance_array['bewerbungen'])]);
						write_alliance_array($user_array['alliance'], $alliance_array);

						$that_user_array = get_user_array($_GET['which']);
						unset($that_user_array['alliance_bewerbung']);
						$that_user_array['alliance'] = $user_array['alliance'];
						write_user_array($_GET['which'], $that_user_array);

						$planets = array_keys($that_user_array['planets']);
						$pos = array();
						foreach($planets as $planet)
							$pos[] = $that_user_array['planets'][$planet]['pos'];
						$infos = universe::get_planet_info($pos);
						foreach($planets as $planet)
						{
							$this_pos = explode(':', $that_user_array['planets'][$planet]['pos']);
							$this_info = $infos[$that_user_array['planets'][$planet]['pos']];
							universe::set_planet_info($this_pos[0], $this_pos[1], $this_pos[2], $this_info[0], $this_info[1], $this_info[2], $user_array['alliance']);
						}

						unset($that_user_array);

						highscores::recalc($_GET['which']);
					}
					elseif($action == 'ablehnen' && isset($_GET['which']) && isset($alliance_array['bewerbungen']) && in_array($_GET['which'], $alliance_array['bewerbungen']))
					{
						$recipients = array();
						foreach($alliance_array['members'] as $name=>$member)
						{
							if($member['permissions'][4])
								$recipients[$name] = 7;
						}
						unset($recipients[$_SESSION['username']]);
						messages::new_message($recipients, $_SESSION['username'], 'Allianzbewerbung abgelehnt', 'Die Bewerbung von '.$_GET['which'].' an Ihre Allianz wurde abgelehnt.');
						messages::new_message(array($_GET['which']=>7), '', 'Allianzbewerbung abgelehnt', 'Ihre Bewerbung bei der Allianz '.$user_array['alliance'].' wurde abgelehnt.');

						unset($alliance_array['bewerbungen'][array_search($_GET['which'], $alliance_array['bewerbungen'])]);
						write_alliance_array($user_array['alliance'], $alliance_array);

						$that_user_array = get_user_array($_GET['which']);
						unset($that_user_array['alliance_bewerbung']);
						write_user_array($_GET['which'], $that_user_array);
						unset($that_user_array);
					}

					if(isset($alliance_array['bewerbungen']) && count($alliance_array['bewerbungen']) > 0)
					{
?>
<h3 id="laufende-bewerbungen">Laufende Bewerbungen</h3>
<dl class="allianz-laufende-bewerbungen">
<?php
						foreach($alliance_array['bewerbungen'] as $bewerbung)
						{
?>
	<dt><a href="help/playerinfo.php?player=<?=htmlentities(urlencode($bewerbung))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>"><?=utf8_htmlentities($bewerbung)?></a></dt>
	<dd><ul>
		<li><a href="allianz.php?action=annehmen&amp;which=<?=htmlentities(urlencode($bewerbung))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Annehmen</a></li>
		<li><a href="allianz.php?action=ablehnen&amp;which=<?=htmlentities(urlencode($bewerbung))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" onclick="return confirm('Sind Sie sicher, dass Sie die Bewerbung des Benutzers <?=utf8_htmlentities($bewerbung)?> ablehnen wollen?');">Ablehnen</a></li>
	</ul></dd>
<?php
						}
?>
</dl>
<h3 id="allianz-informationen">Allianz-Informationen</h3>
<?php
					}
				}
				$punktzahl = 0;
				foreach($alliance_array['members'] as $member)
					$punktzahl += $member['punkte'];
				$punktzahl = floor($punktzahl/count($alliance_array['members']));
?>
<dl class="allianceinfo">
	<dt class="c-allianztag">Allianz<span xml:lang="en">tag</span></dt>
	<dd class="c-allianztag"><?=utf8_htmlentities($user_array['alliance'])?></dd>

	<dt class="c-name">Name</dt>
	<dd class="c-name"><?=utf8_htmlentities($alliance_array['name'])?></dd>

	<dt class="c-ihr-rang">Ihr Rang</dt>
	<dd class="c-ihr-rang"><?=utf8_htmlentities($alliance_array['members'][$_SESSION['username']]['rang'])?></dd>

	<dt class="c-mitglieder">Mitglieder</dt>
	<dd class="c-mitglieder"><?=htmlentities(count($alliance_array['members']))?> <span class="liste">(<a href="allianz.php?action=liste&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" title="Mitgliederliste der Allianz einsehen">Liste</a>)</span></dd>

	<dt class="c-punktzahl">Punktzahl</dt>
	<dd class="c-punktzahl"><?=ths($punktzahl)?> <span class="platz">(Platz <?=ths($alliance_array['platz'])?> von <?=ths(highscores_alliances::get_alliances_count())?>)</span></dd>
</dl>
<?php
				if($alliance_array['members'][$_SESSION['username']]['permissions'][8] || $austreten || $alliance_array['members'][$_SESSION['username']]['permissions'][2] || $alliance_array['members'][$_SESSION['username']]['permissions'][3] || $alliance_array['members'][$_SESSION['username']]['permissions'][7])
				{
?>
<ul class="allianz-aktionen">
<?php
					if($austreten)
					{
?>
	<li class="c-austreten"><a href="allianz.php?action=austreten&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" onclick="return confirm('Wollen Sie wirklich aus der Allianz austreten?');">Aus der Allianz austreten</a></li>
<?php
					}
					if($alliance_array['members'][$_SESSION['username']]['permissions'][8])
					{
?>
	<li class="c-aufloesen"><a href="allianz.php?action=aufloesen&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>" onclick="if(confirm('Sind Sie sicher, dass Sie diese Allianz komplett auflösen und allen Mitgliedern die Mitgliedschaft kündigen wollen?')) return !confirm('Haben Sie es sich doch noch anders überlegt?'); else return false;">Allianz auflösen</a></li>
<?php
					}
					if($alliance_array['members'][$_SESSION['username']]['permissions'][2])
					{
?>
	<li class="c-interner-bereich"><a href="allianz.php?action=intern&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Internen Bereich bearbeiten</a></li>
<?php
					}
					if($alliance_array['members'][$_SESSION['username']]['permissions'][3])
					{
?>
	<li class="c-externer-bereich"><a href="allianz.php?action=extern&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Externen Bereich bearbeiten</a></li>
<?php
					}
					if($alliance_array['members'][$_SESSION['username']]['permissions'][7])
					{
?>
	<li class="c-benutzerrechte"><a href="allianz.php?action=permissions&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Benutzerrechte verwalten</a></li>
<?php
					}
?>
</ul>
<?php
				}
?>
<h3 id="internes">Internes</h3>
<div class="allianz-internes">
<?php
				if(!isset($alliance_array['inner_description_parsed']))
				{
					$alliance_array['inner_description_parsed'] = parse_html($alliance_array['inner_description']);
					write_alliance_array($user_array['alliance'], $alliance_array);
				}
				print($alliance_array['inner_description_parsed']);
?>
</div>
<h3 id="externes">Externes</h3>
<div class="allianz-externes">
<?php
				if(!isset($alliance_array['description_parsed']))
				{
					$alliance_array['description_parsed'] = parse_html($alliance_array['description']);
					write_alliance_array($user_array['alliance'], $alliance_array);
				}
				print($alliance_array['description_parsed']);
?>
</div>
<?php
				if($alliance_array['members'][$_SESSION['username']]['permissions'][0] && count($alliance_array['members']) > 1)
				{
?>
<h3 id="allianzrundschreiben">Allianzrundschreiben</h3>
<?php
					if(isset($_POST['rundschreiben-text']) && strlen(trim($_POST['rundschreiben-text'])) > 0)
					{
						$betreff = '';
						if(isset($_POST['rundschreiben-betreff']))
							$betreff = $_POST['rundschreiben-betreff'];
						if(strlen(trim($betreff)) <= 0)
							$betreff = 'Allianzrundschreiben';
						$recipients = array();
						foreach($alliance_array['members'] as $name=>$member)
							$recipients[$name] = 7;
						$recipients[$_SESSION['username']] = 8;
						messages::new_message($recipients, $_SESSION['username'], $betreff, $_POST['rundschreiben-text']);
?>
<p class="successful">Das Allianzrundschreiben wurde erfolgreich versandt.</p>
<?php
					}
?>
<form action="allianz.php?<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>#allianzrundschreiben" method="post" class="allianz-rundschreiben-form">
	<dl>
		<dt class="c-betreff"><label for="allianz-rundschreiben-betreff-input">Betreff</label></dt>
		<dd class="c-betreff"><input type="text" name="rundschreiben-betreff" id="allianz-rundschreiben-betreff-input" /></dd>

		<dt class="c-text"><label for="allianz-rundschreiben-text-textarea">Text</label></dt>
		<dd class="c-text"><textarea name="rundschreiben-text" id="allianz-rundschreiben-text-textarea" cols="50" rows="17"></textarea></dd>
	</dl>
	<div><button type="submit">Allianzrundschreiben verschicken</button></div>
</form>
<?php
				}
			}
		}
	}
?>
<?php
	login_gui::html_foot();
?>