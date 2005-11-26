<?php
	require('scripts/include.php');

	login_gui::html_head();

	if(isset($_GET['to']))
	{
		# Neue Nachricht verfassen
?>
<h2><a href="nachrichten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Zurück zur Nachrichtenkategorienübersicht [W]" accesskey="w" tabindex="5">Nachrichten</a></h2>
<?php
		$error = '';
		$show_form = true;

		if(isset($_POST['empfaenger']) && isset($_POST['betreff']) && isset($_POST['inhalt']))
		{
			# Nachricht versenden, versuchen

			$_POST['empfaenger'] = trim($_POST['empfaenger']);

			if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])))
				$error = 'Der Empfänger, den Sie eingegeben haben, existiert nicht.';
			elseif($_POST['empfaenger'] == $_SESSION['username'])
				$error = 'Sie können sich nicht selbst eine Nachricht schicken.';
			elseif(strlen($_POST['betreff']) > 30)
				$error = 'Der Betreff darf maximal 30 Bytes lang sein.';
			elseif(strlen($_POST['inhalt']) <= 0)
				$error = 'Sie müssen eine Nachricht eingeben.';
			else
			{
				# Nachricht versenden
				$to = array(
					$_POST['empfaenger'] => 6,
					$_SESSION['username'] => 8
				);
				$from = $_SESSION['username'];
				$subject = $_POST['betreff'];
				$text = $_POST['inhalt'];

				if(!($id = messages::new_message($to, $from, $subject, $text)))
					$error = 'Datenbankfehler.';
				else
				{
?>
<p class="successful">
	Die Nachricht wurde erfolgreich versandt.
</p>
<?php
					$show_form = false;

					logfile::action('21', $id, $_POST['empfaenger'], $subject);
				}
			}
		}

		if(trim($error) != '')
		{
?>
<p class="error">
	<?=htmlentities($error)."\n"?>
</p>
<?php
		}

		if($show_form)
		{
?>
<form action="nachrichten.php?to=&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="nachrichten-neu" onsubmit="this.setAttribute('onsubmit', 'return confirm(\'Doppelklickschutz: Sie haben ein zweites Mal auf \u201eAbsenden\u201c geklickt. Dadurch wird die Nachricht auch ein zweites Mal abgeschickt. Sind Sie sicher, dass Sie diese Aktion durchführen wollen?\');');">
	<fieldset>
		<legend>Nachricht verfassen</legend>
		<dl>
			<dt class="c-empfaenger"><label for="empfaenger-input">Empfänger</label></dt>
<?php
		$empfaenger = $_GET['to'];
		if(isset($_POST['empfaenger']))
			$empfaenger = $_POST['empfaenger'];
		$betreff = '';
		if(isset($_GET['subject']))
			$betreff = $_GET['subject'];
		if(isset($_POST['betreff']))
			$betreff = $_POST['betreff'];
?>
			<dd class="c-empfaenger"><input type="text" id="empfaenger-input" name="empfaenger" value="<?=utf8_htmlentities($empfaenger)?>" tabindex="1" accesskey="z" title="[Z]" /></dd>

			<dt class="c-betreff"><label for="betreff-input">Betreff</label></dt>
			<dd class="c-betreff"><input type="text" id="betreff-input" name="betreff" value="<?=utf8_htmlentities($betreff)?>" maxlength="30" tabindex="2" accesskey="j" title="[J]" /></dd>

			<dt class="c-inhalt"><label for="inhalt-input">Inhalt</label></dt>
			<dd class="c-inhalt"><textarea id="inhalt-input" name="inhalt" cols="50" rows="10" tabindex="3" accesskey="x" title="[X]"><?=isset($_POST['inhalt']) ? preg_replace("/[\n\r\t]/e", '\'&#\'.ord(\'$1\').\';\'', utf8_htmlentities($_POST['inhalt'])) : ''?></textarea></dd>
		</dl>
	</fieldset>
	<div><button type="submit" accesskey="n" tabindex="4">Abse<kbd>n</kbd>den</button></div>
</form>
<?php
		}
	}
	elseif(isset($_GET['type']) && isset($message_type_names[$_GET['type']]))
	{
		if(isset($_GET['message']))
		{
			# Nachricht anzeigen
?>
<h2><a href="nachrichten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Zurück zur Nachrichtenkategorienübersicht [W]" tabindex="6" accesskey="w">Nachrichten</a>: <a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Zurück zur Nachrichtenübersicht: <?=htmlentities($message_type_names[$_GET['type']])?> [O]" tabindex="5" accesskey="o"><?=htmlentities($message_type_names[$_GET['type']])?></a></h2>
<?php
			if(!isset($user_array['messages'][$_GET['type']][$_GET['message']]))
			{
?>
<p class="error">
	Diese Nachricht existiert nicht.
</p>
<?php
			}
			else
			{
				$message = false;
				is_file(DB_MESSAGES.'/'.$_GET['message']) && is_readable(DB_MESSAGES.'/'.$_GET['message']) && $message = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$_GET['message'])));
				if(!$message)
				{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
				}
				else
				{
					# Als gelesen markieren
					if($user_array['messages'][$_GET['type']][$_GET['message']])
					{
						$user_array['messages'][$_GET['type']][$_GET['message']] = false;
						write_user_array();

						logfile::action('22', $_GET['message']);
					}

					# Vorige und naechste ungelesene Nachricht
					$unread_prev = false;
					$unread_next = false;
					$messages = array_keys($user_array['messages'][$_GET['type']]);
					$this_key = array_search($_GET['message'], $messages);
					for($i=$this_key+1; $i<count($messages); $i++)
					{
						if($user_array['messages'][$_GET['type']][$messages[$i]])
						{
							$unread_prev = $messages[$i];
							break;
						}
					}
					for($i=$this_key-1; $i>=0; $i--)
					{
						if($user_array['messages'][$_GET['type']][$messages[$i]])
						{
							$unread_next = $messages[$i];
							break;
						}
					}

					if($unread_next !== false || $unread_prev !== false)
					{
?>
<ul class="ungelesene-nachrichten">
<?php
						if($unread_prev !== false)
						{
?>
	<li class="c-vorige"><a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($unread_prev))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Vorige ungelesene Nachricht [U]" accesskey="u" tabindex="4">&larr;</a></li>
<?php
						}
						if($unread_next !== false)
						{
?>
	<li class="c-naechste"><a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($unread_next))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Nächste ungelesene Nachricht [Q]" accesskey="q" tabindex="3">&rarr;</a></li>
<?php
						}
?>
</ul>
<?php
					}
?>
<dl class="nachricht-informationen type-<?=utf8_htmlentities($_GET['type'])?><?=$message['html'] ? ' html' : ''?>">
<?php
					if(trim($message['from']) != '')
					{
?>
	<dt class="c-absender">Absender</dt>
	<dd class="c-absender"><?=utf8_htmlentities($message['from'])?></dd>

<?php
					}
?>
	<dt class="c-betreff">Betreff</dt>
	<dd class="c-betreff"><?=(trim($message['subject']) != '') ? utf8_htmlentities($message['subject']) : 'Kein Betreff'?></dd>

	<dt class="c-zeit">Zeit</dt>
	<dd class="c-zeit"><?=date('H:i:s, Y-m-d', $message['time'])?></dd>

	<dt class="c-nachricht">Nachricht</dt>
	<dd class="c-nachricht">
<?php
					function repl_nl($nls)
					{
						$len = strlen($nls);
						if($len == 1)
							return "<br />\n\t\t\t";
						elseif($len == 2)
							return "\n\t\t</p>\n\t\t<p>\n\t";
						elseif($len > 2)
							return "\n\t\t</p>\n\t\t".str_repeat('<br />', $len-2)."\n<p>\n\t";
					}

					function repl_links($a, $b, $c)
					{
						$url2 = html_entity_decode($b);
						if(substr($url2, 0, 7) != 'http://')
						{
							$url3 = explode('#', $url2);
							$url3[0] = explode('?', $url3[0]);
							$url = array($url3[0][0]);
							if(isset($url3[0][1]))
								$url[1] = $url3[0][1];
							else
								$url[1] = '';
							if(isset($url3[1]))
								$url[2] = $url[1];
							else
								$url[2] = '';

							if($url[1] != '')

					$url[1] .= '&';
							$url[1] .= SESSION_COOKIE.'='.urlencode(session_id());

							$url2 = $url[0].'?'.$url[1];
							if($url[2] != '')
								$url2 .= '#'.$url[2];
						}

						return $a.htmlentities($url2).$c;
					}

					if($message['html'])
					{
						$message['text'] = preg_replace('/(<a [^>]*href=")([^"]*)(")/ei', 'repl_links("\\1", "\\2", "\\3")', $message['text']);
						echo "\t\t".preg_replace("/\r\n|\r|\n/", "\n\t\t", utf8_htmlentities($message['text'], true))."\n";
					}
					else
						echo "\t\t<p>\n\t\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', utf8_htmlentities($message['text']))."\n\t\t</p>\n";
?>
	</dd>
</dl>
<?php
					#if($_GET['type'] == 6 || $_GET['type'] == 7)
					if($message['from'] != '' && $message['from'] != $_SESSION['username'])
					{
						# Bei Nachrichten im Postausgang ist die Antwort nicht moeglich

						if(trim($message['subject']) == '')
							$re_betreff = 'Kein Betreff';
						else
							$re_betreff = $message['subject'];
						if(substr($re_betreff, 0, 4) != 'Re: ')
							$re_betreff = 'Re: '.$message['subject'];
?>
<form action="nachrichten.php" method="get" class="nachricht-antworten-formular">
	<div>
		<input type="hidden" name="<?=htmlentities(SESSION_COOKIE)?>" value="<?=htmlentities(session_id())?>" />
		<input type="hidden" name="to" value="<?=utf8_htmlentities($message['from'])?>" />
		<input type="hidden" name="subject" value="<?=utf8_htmlentities($re_betreff)?>" />
		<button type="submit" accesskey="w" tabindex="1">Ant<kbd>w</kbd>orten</button>
	</div>
</form>
<?php
					}
?>
<form action="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="nachricht-loeschen-formular">
	<div><input type="hidden" name="message[<?=htmlentities($_GET['message'])?>]" value="on" /><button type="submit" name="delete" accesskey="n" tabindex="2">Lösche<kbd>n</kbd></button></div>
</form>
<?php
					if(isset($_POST['weiterleitung-to']))
					{
						$weiterleitung_text = '';
						if($message['html'])
							$weiterleitung_text .= "<p class=\"weitergeleitete-nachricht\">\n\t";
						$weiterleitung_text .= "--- Weitergeleitete Nachricht";
						if(isset($message['from']) && trim($message['from']) != '')
						{
							$weiterleitung_text .= ", Absender: ";
							if($message['html'])
								$weiterleitung_text .= htmlspecialchars($message['from']);
							else
								$weiterleitung_text .= $message['from'];
						}
						if(isset($message['time']))
							$weiterleitung_text .= ", Sendezeit: ".date('H:i:s, Y-m-d', $message['time']);
						$weiterleitung_text .= " ---\n";
						if($message['html'])
							$weiterleitung_text .= "</p>";
						$weiterleitung_text .= "\n";

						$_POST['weiterleitung-to'] = trim($_POST['weiterleitung-to']);

						if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['weiterleitung-to'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_POST['weiterleitung-to'])))
						{
?>
<p class="error">Der Empfänger, den Sie eingegeben haben, existiert nicht.</p>
<?php
						}
						elseif($_POST['weiterleitung-to'] == $_SESSION['username'])
						{
?>
<p class="error">Sie können sich nicht selbst eine Nachricht schicken.</p>
<?php
						}
						elseif(!($id = messages::new_message(array($_POST['weiterleitung-to']=>$_GET['type']), $_SESSION['username'], 'Fwd: '.$message['subject'], $weiterleitung_text.$message['text'], $message['html'])))
						{
?>
<p class="error">Datenbankfehler.</p>
<?php
						}
						else
						{
?>
<p class="successful">Die Nachricht wurde erfolgreich weitergeleitet.</p>
<?php
							logfile::action('21', $id, $_POST['weiterleitung-to'], 'Fwd: '.$message['subject']);

							unset($_POST['weiterleitung-to']);
						}
					}

					if(isset($_GET['publish']) && $_GET['publish'] && !file_exists(DB_MESSAGES_PUBLIC.'/'.$_GET['message']))
					{
						$public_message_array = array (
							'from' => $message['from'],
							'to' => $_SESSION['username'],
							'subject' => $message['subject'],
							'time' => $message['time'],
							'last_view' => time(),
							'type' => $_GET['type'],
							'text' => $message['text'],
							'html' => $message['html']
						);

						$fh = fopen(DB_MESSAGES_PUBLIC.'/'.$_GET['message'], 'w');
						if($fh)
						{
							flock($fh, LOCK_EX);
							fwrite($fh, gzcompress(serialize($public_message_array)));
							flock($fh, LOCK_UN);
							fclose($fh);
						}

						unset($public_message_array);
					}

					if(file_exists(DB_MESSAGES_PUBLIC.'/'.$_GET['message']))
					{
?>
<p id="nachricht-veroeffentlichen">
	Sie können diese Nachricht öffentlich verlinken: <a href="http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/public_message.php?id=<?=htmlentities($_GET['message'])?>">http://<?=htmlentities($_SERVER['HTTP_HOST'].h_root)?>/public_message.php?id=<?=htmlentities($_GET['message'])?></a>.
</p>
<?php
					}
					else
					{
?>
<ul id="nachricht-veroeffentlichen">
	<li><a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($_GET['message']))?>&amp;publish=1&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>#nachricht-veroeffentlichen">Nachricht veröffentlichen</a></li>
</ul>
<?php
					}
?>
<form action="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($_GET['message']))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>#nachricht-weiterleiten-formular" method="post" id="nachricht-weiterleiten-formular" class="nachricht-weiterleiten-formular">
	<fieldset>
		<legend>Nachricht weiterleiten</legend>
		<dl>
			<dt><label for="empfaenger-input">Empfänger</label></dt>
			<dd><input type="text" name="weiterleitung-to" value="<?=isset($_POST['weiterleitung-to']) ? utf8_htmlentities($_POST['weiterleitung-to']) : ''?>" title="[X]" accesskey="x" tabindex="5" /></dd>
		</dl>
		<div><button type="submit" tabindex="6">Weiterleiten</button></div>
	</fieldset>
</form>
<?php
				}
			}
		}
		else
		{
			# Nachrichtenuebersicht einer Kategorie anzeigen
?>
<h2><a href="nachrichten.php?<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" title="Zurück zur Nachrichtenkategorienübersicht [W]" accesskey="w" tabindex="4">Nachrichten</a>: <?=htmlentities($message_type_names[$_GET['type']])?></h2>
<?php
			if(isset($user_array['messages'][$_GET['type']]) && count($user_array['messages'][$_GET['type']]))
			{
				if(isset($_POST['read']) && isset($_POST['message']) && is_array($_POST['message']))
				{
					# Als gelesen markieren
					$changed = false;
					foreach($_POST['message'] as $message_id=>$v)
					{
						if(!isset($user_array['messages'][$_GET['type']][$message_id]))
							continue;
						$user_array['messages'][$_GET['type']][$message_id] = false;
						$changed = true;

						logfile::action('22', $message_id);
					}
					if($changed)
						write_user_array();
				}
				elseif(isset($_POST['delete']) && isset($_POST['message']) && is_array($_POST['message']))
				{
					# Loeschen
					$changed = false;
					foreach($_POST['message'] as $message_id=>$v)
					{
						if(!isset($user_array['messages'][$_GET['type']][$message_id]))
							continue;
						if(!is_executable(DB_MESSAGES) || !is_file(DB_MESSAGES.'/'.$message_id) || !is_readable(DB_MESSAGES.'/'.$message_id))
							continue;

						$message_array = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$message_id)));
						if(isset($message_array['users'][$_SESSION['username']]))
						{
							unset($message_array['users'][$_SESSION['username']]);
							if(count($message_array['users']) <= 0)
								unlink(DB_MESSAGES.'/'.$message_id);
							else
							{
								$fh = fopen(DB_MESSAGES.'/'.$message_id, 'w');
								if($fh)
								{
									flock($fh, LOCK_EX);
									fwrite($fh, gzcompress(serialize($message_array)));
									flock($fh, LOCK_UN);
									fclose($fh);

									logfile::action('23', $message_id);
								}
							}
						}
						unset($message_array);

						unset($user_array['messages'][$_GET['type']][$message_id]);
						$changed = true;
					}
					if($changed)
						write_user_array();
				}
?>
<script type="text/javascript">
// <![CDATA[
	function toggle_selection()
	{
		var formular = document.getElementById('nachrichten-liste').elements;
		for(var i=0; i<formular.length; i++)
		{
			if(formular[i].checked != undefined)
				formular[i].checked = !formular[i].checked;
		}
	}
// ]]>
</script>
<form action="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" method="post" class="nachrichten-liste type-<?=utf8_htmlentities($_GET['type'])?>" id="nachrichten-liste">
	<table>
		<thead>
			<tr>
				<th class="c-auswaehlen"></th>
				<th class="c-betreff">Betreff</th>
<?php
	#if($_GET['type'] == 6 || $_GET['type'] == 7)
	{
?>
				<th class="c-absender">Absender</th>
<?php
	}
?>
				<th class="c-datum">Datum</th>
			</tr>
		</thead>
		<tbody>
<?php
				$messages = array_reverse($user_array['messages'][$_GET['type']]);
				$tabindex = 5;
				foreach($messages as $message_id=>$unread)
				{
					if(!is_file(DB_MESSAGES.'/'.$message_id) || !is_readable(DB_MESSAGES.'/'.$message_id))
					{
						unset($user_array['messages'][$_GET['type']][$message_id]);
						continue;
					}
					$message = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$message_id)));
					if(!$message)
						continue;
?>
			<tr class="<?=($unread && $_GET['type'] != 8) ? 'neu' : 'alt'?>">
				<td class="c-auswaehlen"><input type="checkbox" name="message[<?=htmlentities($message_id)?>]" tabindex="<?=$tabindex++?>" /></td>
				<td class="c-betreff"><a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($message_id))?>&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" tabindex="<?=$tabindex++?>"><?=(trim($message['subject']) != '') ? utf8_htmlentities($message['subject']) : 'Kein Betreff'?></a></td>
<?php
	#if($_GET['type'] == 6 || $_GET['type'] == 7)
	{
?>
				<td class="c-absender"><?=utf8_htmlentities($message['from'])?></td>
<?php
	}
?>
				<td class="c-datum"><?=date('H:i:s, Y-m-d', $message['time'])?></td>
			</tr>
<?php
				}
?>
		</tbody>
		<tfoot>
			<tr>
				<td class="c-auswaehlen">
					<script type="text/javascript">
						// <![CDATA[
						document.write('<button onclick="toggle_selection(); return false;" class="auswahl-button" title="[O]" accesskey="o" tabindex="1"><abbr title="Auswahl umkehren">A</abbr></button>');
						// ]]>
					</script>
				</td>
				<td colspan="3"><button type="submit" name="delete" class="loeschen-button" accesskey="n" tabindex="2">Lösche<kbd>n</kbd></button> <button type="submit" name="read" class="als-gelesen-markieren-button" tabindex="3" accesskey="u" title="[U]">Als gelesen markieren</button></td>
			</tr>
		</tfoot>
	</table>
</form>
<?php
			}
		}
	}
	else
	{
		$ncount = array(
			1 => array(0, 0, 'leer'),
			2 => array(0, 0, 'leer'),
			3 => array(0, 0, 'leer'),
			4 => array(0, 0, 'leer'),
			5 => array(0, 0, 'leer'),
			6 => array(0, 0, 'leer'),
			7 => array(0, 0, 'leer'),
			8 => array(0, 0, 'leer')
		);
		$ges_ncount = array(0, 0, 'leer');

		if(isset($user_array['messages']))
		{
			foreach($user_array['messages'] as $cat=>$messages)
			{
				foreach($messages as $message_id=>$unread)
				{
					if(!is_file(DB_MESSAGES.'/'.$message_id) || !is_readable(DB_MESSAGES.'/'.$message_id))
						continue;

					$ncount[$cat][1]++;
					$ges_ncount[1]++;

					if($unread && $cat != 8)
					{
						$ncount[$cat][0]++;
						$ges_ncount[0]++;
					}
				}
			}

			foreach($ncount as $type=>$cat)
			{
				if($cat[0] > 0 && $type != 8)
					$cat[2] = 'neu';
				elseif($cat[1] > 0)
					$cat[2] = 'alt';
				else
					$cat[2] = 'leer';
				$ncount[$type] = $cat;
			}

			if($ges_ncount[0] > 0)
				$ges_ncount[2] = 'neu';
			elseif($ges_ncount[1] > 0)
				$ges_ncount[2] = 'alt';
			else
				$ges_ncount[2] = 'leer';
		}
?>
<h2>Nachrichten</h2>
<ul class="nachrichten-neu-link">
	<li><a href="nachrichten.php?to=&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="n" tabindex="1"><kbd>N</kbd>eue Nachricht</a></li>
</ul>
<dl class="nachrichten-kategorien">
	<dt class="c-kaempfe <?=$ncount[1][2]?>"><a href="nachrichten.php?type=1&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="ä" tabindex="2">K<kbd>ä</kbd>mpfe</a></dt>
	<dd class="c-kaempfe <?=$ncount[1][2]?>"><?=utf8_htmlentities($ncount[1][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[1][1])?>)</span></dd>

	<dt class="c-spionage <?=$ncount[2][2]?>"><a href="nachrichten.php?type=2&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="o" tabindex="3">Spi<kbd>o</kbd>nage</a></dt>
	<dd class="c-spionage <?=$ncount[2][2]?>"><?=utf8_htmlentities($ncount[2][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[2][1])?>)</span></dd>

	<dt class="c-transport <?=$ncount[3][2]?>"><a href="nachrichten.php?type=3&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="j" title="[J]" tabindex="4">Transport</a></dt>
	<dd class="c-transport <?=$ncount[3][2]?>"><?=utf8_htmlentities($ncount[3][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[3][1])?>)</span></dd>

	<dt class="c-sammeln <?=$ncount[4][2]?>"><a href="nachrichten.php?type=4&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="q" title="[Q]" tabindex="5">Sammeln</a></dt>
	<dd class="c-sammeln <?=$ncount[4][2]?>"><?=utf8_htmlentities($ncount[4][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[4][1])?>)</span></dd>

	<dt class="c-besiedelung <?=$ncount[5][2]?>"><a href="nachrichten.php?type=5&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="u" tabindex="6">Besiedel<kbd>u</kbd>ng</a></dt>
	<dd class="c-besiedelung <?=$ncount[5][2]?>"><?=utf8_htmlentities($ncount[5][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[5][1])?>)</span></dd>

	<dt class="c-benutzernachrichten <?=$ncount[6][2]?>"><a href="nachrichten.php?type=6&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="z" tabindex="7">Benut<kbd>z</kbd>ernachrichten</a></dt>
	<dd class="c-benutzernachrichten <?=$ncount[6][2]?>"><?=utf8_htmlentities($ncount[6][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[6][1])?>)</span></dd>

	<dt class="c-verbeundete <?=$ncount[7][2]?>"><a href="nachrichten.php?type=7&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="ü" tabindex="8">Verb<kbd>ü</kbd>ndete</a></dt>
	<dd class="c-verbuendete <?=$ncount[7][2]?>"><?=utf8_htmlentities($ncount[7][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[7][1])?>)</span></dd>

	<dt class="c-postausgang <?=$ncount[8][2]?>"><a href="nachrichten.php?type=8&amp;<?=htmlentities(SESSION_COOKIE.'='.urlencode(session_id()))?>" accesskey="w" title="[W]" tabindex="9">Postausgang</a></dt>
	<dd class="c-postausgang <?=$ncount[8][2]?>"><?=utf8_htmlentities($ncount[8][1])?></dd>

	<dt class="c-gesamt <?=$ges_ncount[2]?>">Gesamt</dt>
	<dd class="c-gesamt <?=$ges_ncount[2]?>"><?=utf8_htmlentities($ges_ncount[0])?> <span class="gesamt">(<?=utf8_htmlentities($ges_ncount[1])?>)</span></dd>
</dl>
<?php
	}

	login_gui::html_foot();
?>