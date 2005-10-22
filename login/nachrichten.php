<?php
	require('scripts/include.php');

	login_gui::html_head();

	if(isset($_GET['to']))
	{
		# Neue Nachricht verfassen
?>
<h2><a href="nachrichten.php" title="Zurück zur Nachrichtenkategorienübersicht [W]" accesskey="w" tabindex="5">Nachrichten</a></h2>
<?php
		$error = '';
		$show_form = true;

		if(isset($_POST['empfaenger']) && isset($_POST['betreff']) && isset($_POST['inhalt']))
		{
			# Nachricht versenden, versuchen

			$_POST['empfaenger'] = trim($_POST['empfaenger']);

			if(!is_file(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])) || !is_readable(DB_PLAYERS.'/'.urlencode($_POST['empfaenger'])))
				$error = 'Der Empfänger, den du eingegeben hast, existiert nicht.';
			elseif($_POST['empfaenger'] == $_SESSION['username'])
				$error = 'Du kannst dir nicht selbst eine Nachricht schicken.';
			elseif(strlen($_POST['betreff']) > 30)
				$error = 'Der Betreff darf maximal 30 Bytes lang sein.';
			elseif(strlen($_POST['inhalt']) <= 0)
				$error = 'Du musst eine Nachricht eingeben.';
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

				if(!messages::new_message($to, $from, $subject, $text))
					$error = 'Datenbankfehler.';
				else
				{
?>
<p class="successful">
	Die Nachricht wurde erfolgreich versandt.
</p>
<?php
					$show_form = false;
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
<form action="nachrichten.php?to=" method="post" class="nachrichten-neu">
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
<h2><a href="nachrichten.php" title="Zurück zur Nachrichtenkategorienübersicht [W]" tabindex="4" accesskey="w">Nachrichten</a>: <a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>" title="Zurück zur Nachrichtenübersicht: <?=htmlentities($message_type_names[$_GET['type']])?> [O]" tabindex="3" accesskey="o"><?=htmlentities($message_type_names[$_GET['type']])?></a></h2>
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
							return "\n\t\t</p>\n".str_repeat('<br />', $len-2)."\n<p>\n\t";
					}

					if($message['html'])
						echo "\t\t".preg_replace("/\r\n|\r|\n/", "\n\t\t", utf8_htmlentities($message['text'], true))."\n";
					else
						echo "\t\t<p>\n\t\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', utf8_htmlentities($message['text']))."\n\t\t</p>\n";
?>
	</dd>
</dl>
<?php
					if($_GET['type'] == 6 || $_GET['type'] == 7)
					{
						# Bei Nachrichten im Postausgang ist die Antwort nicht moeglich
?>
<form action="nachrichten.php" method="get" class="nachricht-antworten-formular">
	<div>
		<input type="hidden" name="to" value="<?=utf8_htmlentities($message['from'])?>" />
		<input type="hidden" name="subject" value="<?=utf8_htmlentities('Re: '.((trim($message['subject']) != '') ? $message['subject'] : 'Kein Betreff'))?>" />
		<button type="submit" accesskey="w" tabindex="1">Ant<kbd>w</kbd>orten</button>
	</div>
</form>
<?php
					}
?>
<form action="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>" method="post" class="nachricht-loeschen-formular">
	<div><input type="hidden" name="message[<?=htmlentities($_GET['message'])?>]" value="on" /><button type="submit" name="delete" accesskey="n" tabindex="2">Lösche<kbd>n</kbd></button></div>
</form>
<?php
				}
			}
		}
		else
		{
			# Nachrichtenuebersicht einer Kategorie anzeigen
?>
<h2><a href="nachrichten.php" title="Zurück zur Nachrichtenkategorienübersicht [W]" accesskey="w" tabindex="4">Nachrichten</a>: <?=htmlentities($message_type_names[$_GET['type']])?></h2>
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
	function toggle_selection()
	{
		var formular = document.getElementById('nachrichten-liste').elements;
		for(var i=0; i<formular.length; i++)
		{
			if(formular[i].checked != undefined)
				formular[i].checked = !formular[i].checked;
		}
	}
</script>
<form action="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>" method="post" class="nachrichten-liste type-<?=utf8_htmlentities($_GET['type'])?>" id="nachrichten-liste">
	<table>
		<thead>
			<tr>
				<th class="c-auswaehlen"></th>
				<th class="c-betreff">Betreff</th>
<?php
	if($_GET['type'] == 6 || $_GET['type'] == 7)
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
						continue;
					$message = unserialize(gzuncompress(file_get_contents(DB_MESSAGES.'/'.$message_id)));
					if(!$message)
						continue;
?>
			<tr class="<?=($unread && $_GET['type'] != 8) ? 'neu' : 'alt'?>">
				<td class="c-auswaehlen"><input type="checkbox" name="message[<?=htmlentities($message_id)?>]" tabindex="<?=$tabindex++?>" /></td>
				<td class="c-betreff"><a href="nachrichten.php?type=<?=htmlentities(urlencode($_GET['type']))?>&amp;message=<?=htmlentities(urlencode($message_id))?>" tabindex="<?=$tabindex++?>"><?=(trim($message['subject']) != '') ? utf8_htmlentities($message['subject']) : 'Kein Betreff'?></a></td>
<?php
	if($_GET['type'] == 6 || $_GET['type'] == 7)
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
				<td colspan="3"><button type="submit" name="delete" class="loeschen-button" accesskey="n" tabindex="2">Lösche<kbd>n</kbd></button> <button type="submit" name="read" class="als-gelesen-markieren-button" tabindex="3" accesskey="u" title="[U]">Als gelesen markieren</a></td>
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
	<li><a href="nachrichten.php?to=" accesskey="n" tabindex="1"><kbd>N</kbd>eue Nachricht</a></li>
</ul>
<dl class="nachrichten-kategorien">
	<dt class="c-kaempfe <?=$ncount[1][2]?>"><a href="nachrichten.php?type=1" accesskey="ä" tabindex="2">K<kbd>ä</kbd>mpfe</a></dt>
	<dd class="c-kaempfe <?=$ncount[1][2]?>"><?=utf8_htmlentities($ncount[1][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[1][1])?>)</span></dd>

	<dt class="c-spionage <?=$ncount[2][2]?>"><a href="nachrichten.php?type=2" accesskey="o" tabindex="3">Spi<kbd>o</kbd>nage</a></dt>
	<dd class="c-spionage <?=$ncount[2][2]?>"><?=utf8_htmlentities($ncount[2][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[2][1])?>)</span></dd>

	<dt class="c-transport <?=$ncount[3][2]?>"><a href="nachrichten.php?type=3" accesskey="j" title="[J]" tabindex="4">Transport</a></dt>
	<dd class="c-transport <?=$ncount[3][2]?>"><?=utf8_htmlentities($ncount[3][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[3][1])?>)</span></dd>

	<dt class="c-sammeln <?=$ncount[4][2]?>"><a href="nachrichten.php?type=4" accesskey="q" title="[Q]" tabindex="5">Sammeln</a></dt>
	<dd class="c-sammeln <?=$ncount[4][2]?>"><?=utf8_htmlentities($ncount[4][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[4][1])?>)</span></dd>

	<dt class="c-besiedelung <?=$ncount[5][2]?>"><a href="nachrichten.php?type=5" accesskey="u" tabindex="6">Besiedel<kbd>u</kbd>ng</a></dt>
	<dd class="c-besiedelung <?=$ncount[5][2]?>"><?=utf8_htmlentities($ncount[5][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[5][1])?>)</span></dd>

	<dt class="c-benutzernachrichten <?=$ncount[6][2]?>"><a href="nachrichten.php?type=6" accesskey="z" tabindex="7">Benut<kbd>z</kbd>ernachrichten</a></dt>
	<dd class="c-benutzernachrichten <?=$ncount[6][2]?>"><?=utf8_htmlentities($ncount[6][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[6][1])?>)</span></dd>

	<dt class="c-verbeundete <?=$ncount[7][2]?>"><a href="nachrichten.php?type=7" accesskey="ü" tabindex="8">Verb<kbd>ü</kbd>ndete</a></dt>
	<dd class="c-verbuendete <?=$ncount[7][2]?>"><?=utf8_htmlentities($ncount[7][0])?> <span class="gesamt">(<?=utf8_htmlentities($ncount[7][1])?>)</span></dd>

	<dt class="c-postausgang <?=$ncount[8][2]?>"><a href="nachrichten.php?type=8" accesskey="w" title="[W]" tabindex="9">Postausgang</a></dt>
	<dd class="c-postausgang <?=$ncount[8][2]?>"><?=utf8_htmlentities($ncount[8][1])?></dd>

	<dt class="c-gesamt <?=$ges_ncount[2]?>">Gesamt</dt>
	<dd class="c-gesamt <?=$ges_ncount[2]?>"><?=utf8_htmlentities($ges_ncount[0])?> <span class="gesamt">(<?=utf8_htmlentities($ges_ncount[1])?>)</span></dd>
</dl>
<?php
	}

	login_gui::html_foot();
?>