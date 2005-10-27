<?php
	require('scripts/include.php');

	login_gui::html_head();

	if(count($_POST) > 0)
		$_SESSION['handelsrechner'] = $_POST;
	elseif(isset($_SESSION['handelsrechner']))
		$_POST = $_SESSION['handelsrechner'];

	$handelskurs = preg_split("/\r\n|\r|\n/", file_get_contents(DB_HANDELSKURS));
?>
<h2>Handelsrechner</h2>
<form action="handelsrechner.php" method="post" class="handelsrechner">
	<fieldset class="handelsrechner-handelskurs">
		<legend>Handelskurs</legend>
		<dl>
			<dt class="c-carbon"><label for="handelskurs-carbon">Carbon</label></dt>
			<dd class="c-carbon"><input type="text" name="handelskurs-carbon" id="handelskurs-carbon" value="<?=isset($_POST['handelskurs-carbon']) ? htmlentities($_POST['handelskurs-carbon']) : round($handelskurs[0], 2)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="24" /></dd>

			<dt class="c-aluminium"><label for="handelskurs-aluminium">Aluminium</label></dt>
			<dd class="c-aluminium"><input type="text" name="handelskurs-aluminium" id="handelskurs-aluminium" value="<?=isset($_POST['handelskurs-aluminium']) ? htmlentities($_POST['handelskurs-aluminium']) : round($handelskurs[1], 2)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="25" /></dd>

			<dt class="c-wolfram"><label for="handelskurs-wolfram">Wolfram</label></dt>
			<dd class="c-wolfram"><input type="text" name="handelskurs-wolfram" id="handelskurs-wolfram" value="<?=isset($_POST['handelskurs-wolfram']) ? htmlentities($_POST['handelskurs-wolfram']) : round($handelskurs[2], 2)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="26" /></dd>

			<dt class="c-radium"><label for="handelskurs-radium">Radium</label></dt>
			<dd class="c-radium"><input type="text" name="handelskurs-radium" id="handelskurs-radium" value="<?=isset($_POST['handelskurs-radium']) ? htmlentities($_POST['handelskurs-radium']) : round($handelskurs[3], 2)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="27" /></dd>

			<dt class="c-tritium"><label for="handelskurs-tritium">Tritium</label></dt>
			<dd class="c-tritium"><input type="text" name="handelskurs-tritium" id="handelskurs-tritium" value="<?=isset($_POST['handelskurs-tritium']) ? htmlentities($_POST['handelskurs-tritium']) : round($handelskurs[4], 2)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="28" /></dd>
		</dl>
		<script type="text/javascript">
			// <![CDATA[
			document.write('<button onclick="reset_handelskurs();" title="Setzt den Handelskurs auf seinen urspr�nglichen Wert zur�ck." accesskey="z" tabindex="23"><kbd>Z</kbd>ur�cksetzen</button>');
			// ]]>
		</script>
		<br class="clear" />
	</fieldset>
<?php
	function check_number($float)
	{
		$args = func_get_args();
		array_shift($args); /* $float entfernen */
		$regexp = ($float ? '/^([0-9]*[.,][0-9]+)|([0-9]+)$/' : '/^[0-9]+$/');
		foreach($args as $arg)
		{
			if(!preg_match($regexp, $arg))
				return false;
		}
		return true;
	}
	$angebot_carbon = $angebot_aluminium = $angebot_wolfram = $angebot_radium = $angebot_tritium
	= $zurueck_carbon = $zurueck_aluminium = $zurueck_wolfram = $zurueck_radium = $zurueck_tritium = '0';

	if((!isset($_POST['eingabe']) || $_POST['eingabe'] != 'zurueck'))
	{
		if(isset($_POST['angebot-carbon']))
			$angebot_carbon = $_POST['angebot-carbon'];
		if(isset($_POST['angebot-aluminium']))
			$angebot_aluminium = $_POST['angebot-aluminium'];
		if(isset($_POST['angebot-wolfram']))
			$angebot_wolfram = $_POST['angebot-wolfram'];
		if(isset($_POST['angebot-radium']))
			$angebot_radium = $_POST['angebot-radium'];
		if(isset($_POST['angebot-tritium']))
			$angebot_tritium = $_POST['angebot-tritium'];
	}
	else
	{
		if(isset($_POST['zurueck-carbon']))
			$zurueck_carbon = $_POST['zurueck-carbon'];
		if(isset($_POST['zurueck-aluminium']))
			$zurueck_aluminium = $_POST['zurueck-aluminium'];
		if(isset($_POST['zurueck-wolfram']))
			$zurueck_wolfram = $_POST['zurueck-wolfram'];
		if(isset($_POST['zurueck-radium']))
			$zurueck_radium = $_POST['zurueck-radium'];
		if(isset($_POST['zurueck-tritium']))
			$zurueck_tritium = $_POST['zurueck-tritium'];
	}

	if((((!isset($_POST['eingabe']) || $_POST['eingabe'] != 'zurueck') && isset($_POST['angebot-carbon']) && isset($_POST['angebot-aluminium']) && isset($_POST['angebot-wolfram']) && isset($_POST['angebot-radium']) && isset($_POST['angebot-tritium']))
	|| (isset($_POST['eingabe']) && $_POST['eingabe'] == 'zurueck' && isset($_POST['zurueck-carbon']) && isset($_POST['zurueck-aluminium']) && isset($_POST['zurueck-wolfram']) && isset($_POST['zurueck-radium']) && isset($_POST['zurueck-tritium'])))
	&& isset($_POST['handelskurs-carbon']) && isset($_POST['handelskurs-aluminium']) && isset($_POST['handelskurs-wolfram']) && isset($_POST['handelskurs-radium']) && isset($_POST['handelskurs-tritium']))
	{
		if(!isset($_POST['eingabe']) || $_POST['eingabe'] != 'zurueck')
		{
			$from = 'angebot';
			$to = 'zurueck';
		}
		else
		{
			$from = 'zurueck';
			$to = 'angebot';
		}

		if(!isset($_POST['fortgeschrittener-modus']) || (isset($_POST[$to.'-anteil-carbon']) && isset($_POST[$to.'-anteil-aluminium']) && isset($_POST[$to.'-anteil-wolfram']) && isset($_POST[$to.'-anteil-radium']) && isset($_POST[$to.'-anteil-tritium'])))
		{
			if(!check_number(false, $_POST[$from.'-carbon'], $_POST[$from.'-aluminium'], $_POST[$from.'-wolfram'], $_POST[$from.'-radium'], $_POST[$from.'-tritium']) || !check_number(true, $_POST['handelskurs-carbon'], $_POST['handelskurs-aluminium'], $_POST['handelskurs-wolfram'], $_POST['handelskurs-radium'], $_POST['handelskurs-tritium']) || (isset($_POST['fortgeschrittener-modus']) && !check_number(true, $_POST[$to.'-anteil-carbon'], $_POST[$to.'-anteil-aluminium'], $_POST[$to.'-anteil-wolfram'], $_POST[$to.'-anteil-radium'], $_POST[$to.'-anteil-tritium'])))
			{
?>
	<p id="error-message" class="error">
		Bitte gib g�ltige Werte ein.
	</p>
<?php
			}
			else
			{
				$h_carbon = (float) str_replace(',', '.', $_POST['handelskurs-carbon']);
				$h_aluminium = (float) str_replace(',', '.', $_POST['handelskurs-aluminium']);
				$h_wolfram = (float) str_replace(',', '.', $_POST['handelskurs-wolfram']);
				$h_radium = (float) str_replace(',', '.', $_POST['handelskurs-radium']);
				$h_tritium = (float) str_replace(',', '.', $_POST['handelskurs-tritium']);

				$gesamt = ($_POST[$from.'-carbon']/$h_carbon)+($_POST[$from.'-aluminium']/$h_aluminium)+($_POST[$from.'-wolfram']/$h_wolfram)+($_POST[$from.'-radium']/$h_radium)+($_POST[$from.'-tritium']/$h_tritium);

				if(isset($_POST['fortgeschrittener-modus']))
				{
					$anteil_carbon = str_replace(',', '.', $_POST[$to.'-anteil-carbon']);
					$anteil_aluminium = str_replace(',', '.', $_POST[$to.'-anteil-aluminium']);
					$anteil_wolfram = str_replace(',', '.', $_POST[$to.'-anteil-wolfram']);
					$anteil_radium = str_replace(',', '.', $_POST[$to.'-anteil-radium']);
					$anteil_tritium = str_replace(',', '.', $_POST[$to.'-anteil-tritium']);
					$anteil_gesamt = $anteil_carbon+$anteil_aluminium+$anteil_wolfram+$anteil_radium+$anteil_tritium;

					if($anteil_gesamt == 0)
						$h_carbon = $h_aluminium = $h_wolfram = $h_radium = $h_tritium = 0;
					else
					{
						$h_carbon *= $anteil_carbon/$anteil_gesamt;
						$h_aluminium *= $anteil_aluminium/$anteil_gesamt;
						$h_wolfram *= $anteil_wolfram/$anteil_gesamt;
						$h_radium *= $anteil_radium/$anteil_gesamt;
						$h_tritium *= $anteil_tritium/$anteil_gesamt;
					}
				}

				${$to.'_carbon'} = round($gesamt*$h_carbon);
				${$to.'_aluminium'} = round($gesamt*$h_aluminium);
				${$to.'_wolfram'} = round($gesamt*$h_wolfram);
				${$to.'_radium'} = round($gesamt*$h_radium);
				${$to.'_tritium'} = round($gesamt*$h_tritium);
			}
		}
	}
?>
	<fieldset id="angebot" class="handelsrechner-angebot">
		<legend>Ihr Angebot</legend>
		<div><input type="radio" id="eingabe-angebot" name="eingabe" value="angebot" onchange="refresh_eingabe();" onclick="refresh_eingabe();" onkeyup="refresh_eingabe();"<?=(!isset($_POST['eingabe']) || $_POST['eingabe'] != 'zurueck') ? ' checked="checked"' : ''?> tabindex="1" accesskey="o" /> <label for="eingabe-angebot">Angeb<kbd>o</kbd>t eingeben</label></div>
		<dl>
			<dt><label for="angebot-carbon">Carb<kbd>o</kbd>n</label></dt>
			<dd><input type="text" name="angebot-anteil-carbon" id="angebot-anteil-carbon" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['angebot-anteil-carbon']) ? htmlentities($_POST['angebot-anteil-carbon']) : '0'?>" tabindex="3" /> <input type="text" name="angebot-carbon" id="angebot-carbon" value="<?=htmlentities($angebot_carbon)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="4" /></dd>

			<dt><label for="angebot-aluminium">Aluminium</label></dt>
			<dd><input type="text" name="angebot-anteil-aluminium" id="angebot-anteil-aluminium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['angebot-anteil-aluminium']) ? htmlentities($_POST['angebot-anteil-aluminium']) : '0'?>" tabindex="5" /> <input type="text" name="angebot-aluminium" id="angebot-aluminium" value="<?=htmlentities($angebot_aluminium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="6" /></dd>

			<dt><label for="angebot-wolfram">Wolfram</label></dt>
			<dd><input type="text" name="angebot-anteil-wolfram" id="angebot-anteil-wolfram" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['angebot-anteil-wolfram']) ? htmlentities($_POST['angebot-anteil-wolfram']) : '0'?>" tabindex="7" /> <input type="text" name="angebot-wolfram" id="angebot-wolfram" value="<?=htmlentities($angebot_wolfram)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="8" /></dd>

			<dt><label for="angebot-radium">Radium</label></dt>
			<dd><input type="text" name="angebot-anteil-radium" id="angebot-anteil-radium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['angebot-anteil-radium']) ? htmlentities($_POST['angebot-anteil-radium']) : '0'?>" tabindex="9" /> <input type="text" name="angebot-radium" id="angebot-radium" value="<?=htmlentities($angebot_radium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="10" /></dd>

			<dt><label for="angebot-tritium">Tritium</label></dt>
			<dd><input type="text" name="angebot-anteil-tritium" id="angebot-anteil-tritium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['angebot-anteil-tritium']) ? htmlentities($_POST['angebot-anteil-tritium']) : '0'?>" tabindex="11" /> <input type="text" name="angebot-tritium" id="angebot-tritium" value="<?=htmlentities($angebot_tritium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="12" /></dd>
		</dl>
	</fieldset>
	<fieldset id="zurueck" class="handelsrechner-erhalten">
		<legend>Sie erhalten</legend>
		<div><input type="radio" id="eingabe-zurueck" name="eingabe" value="zurueck" onchange="refresh_eingabe();" onclick="refresh_eingabe();" onkeyup="refresh_eingabe();"<?=(isset($_POST['eingabe']) && $_POST['eingabe'] == 'zurueck') ? ' checked="checked"' : ''?> tabindex="2" accesskey="n" /> <label for="eingabe-zurueck">Erh�lt<kbd>n</kbd>is eingeben</label></div>
		<dl>
			<dt><label for="zurueck-carbon">Carbo<kbd>n</kbd></label></dt>
			<dd><input type="text" name="zurueck-anteil-carbon" id="zurueck-anteil-carbon" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['zurueck-anteil-carbon']) ? htmlentities($_POST['zurueck-anteil-carbon']) : '0'?>" tabindex="13" /> <input type="text" name="zurueck-carbon" id="zurueck-carbon" value="<?=htmlentities($zurueck_carbon)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="14" /></dd>

			<dt><label for="zurueck-aluminium">Aluminium</label></dt>
			<dd><input type="text" name="zurueck-anteil-aluminium" id="zurueck-anteil-aluminium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['zurueck-anteil-aluminium']) ? htmlentities($_POST['zurueck-anteil-aluminium']) : '0'?>" tabindex="15" /> <input type="text" name="zurueck-aluminium" id="zurueck-aluminium" value="<?=htmlentities($zurueck_aluminium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="16" /></dd>

			<dt><label for="zurueck-wolfram">Wolfram</label></dt>
			<dd><input type="text" name="zurueck-anteil-wolfram" id="zurueck-anteil-wolfram" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['zurueck-anteil-wolfram']) ? htmlentities($_POST['zurueck-anteil-wolfram']) : '0'?>" tabindex="17" /> <input type="text" name="zurueck-wolfram" id="zurueck-wolfram" value="<?=htmlentities($zurueck_wolfram)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="18" /></dd>

			<dt><label for="zurueck-radium">Radium</label></dt>
			<dd><input type="text" name="zurueck-anteil-radium" id="zurueck-anteil-radium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['zurueck-anteil-radium']) ? htmlentities($_POST['zurueck-anteil-radium']) : '0'?>" tabindex="19" /> <input type="text" name="zurueck-radium" id="zurueck-radium" value="<?=htmlentities($zurueck_radium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="20" /></dd>

			<dt><label for="zurueck-tritium">Tritium</label></dt>
			<dd><input type="text" name="zurueck-anteil-tritium" id="zurueck-anteil-tritium" size="3" title="Anteil (fortgeschrittener Modus)" class="handelsrechner-anteil" onkeyup="calc();" onmouseup="calc();" onchange="calc();" value="<?=isset($_POST['zurueck-anteil-tritium']) ? htmlentities($_POST['zurueck-anteil-tritium']) : '0'?>" tabindex="21" /> <input type="text" name="zurueck-tritium" id="zurueck-tritium" value="<?=htmlentities($zurueck_tritium)?>" onkeyup="calc();" onmouseup="calc();" onchange="calc();" tabindex="22" /></dd>
		</dl>
	</fieldset>
	<noscript><button type="submit" tabindex="30">Berechnen</button></noscript>
	<p id="fortgeschrittener-modus" class="handelsrechner-fortgeschrittener-modus">
		<input type="checkbox" name="fortgeschrittener-modus" id="fortgeschrittener-modus-input"<?=isset($_POST['fortgeschrittener-modus']) ? ' checked="checked"' : ''?> onchange="refresh_modus();" onclick="refresh_modus();" onkeyup="refresh_modus();" accesskey="u" tabindex="29" /> <label for="fortgeschrittener-modus-input">Fortgeschrittener Mod<kbd>u</kbd>s</label> (<a href="handelsrechner.php?hilfe=0" onclick="show_hilfe(); return false;">Hilfe</a>)
	</p>
<?php
	if(!isset($_GET['hilfe']))
	{
?>
	<script type="text/javascript">
		// <![CDATA[
<?php
		echo "\t\tdocument.write('";
		ob_start();
	}
?>
	<hr id="hilfe-trenn" />
	<div id="hilfe" class="handelsrechner-hilfe">
		<ul id="hilfe-schliessen-klein">
			<li><a href="<?=htmlentities($_SERVER['PHP_SELF'])?>" onclick="hide_hilfe(); return false;"><abbr title="Hilfe schlie�en">X</abbr></a></li>
		</ul>
		<h3>Hilfe zum fortgeschrittenen Modus</h3>
		<p>Der fortgeschrittene Modus bietet die M�glichkeit, Angebot oder Erh�ltnis zu verschiedenen Anteilen in die Rohstoffe aufzuteilen.</p>
		<p>Wenn Sie in den fortgeschrittenen Modus wechseln, erscheint vor jedem Rohstofffeld auf der Seite, auf der Sie gerade nichts eintippen wollen, ein zus�tzliches kleines Textfeld. Dort k�nnen Sie die Verh�ltnisse eintragen, wie die Rohstoffe aufgeteilt werden sollen. Alternativ k�nnen Sie auch einfach Prozentzahlen eintippen. Achten Sie aber darauf, dass diese wirklich zusammen 100 ergeben.</p>

		<ul id="beispiele-ausklappen">
			<li><a href="handelsrechner.php?hilfe=<?=(isset($_GET['hilfe']) && $_GET['hilfe'] == '1') ? '0' : '1'?>" onclick="show_beispiel_1(); return false;">Beispiel 1</a></li>
			<li><a href="handelsrechner.php?hilfe=<?=(isset($_GET['hilfe']) && $_GET['hilfe'] == '2') ? '0' : '2'?>" onclick="show_beispiel_2(); return false;">Beispiel 2</a></li>
		</ul>
		<hr id="beispiel-trenn" />
<?php
	if(isset($_GET['hilfe']) && $_GET['hilfe'] != '1')
	{
?>
		<script type="text/javascript">
			// <![CDATA[
<?php
		echo "\t\t\tdocument.write('";
		ob_start();
	}
?>
		<div id="beispiel-1" class="handelsrechner-beispiel">
			<h4>Beispiel 1</h4>
			<p>Sie m�chten gerne 10<?=THS_HTML?>000&nbsp;Tritium verscherbeln. Daf�r h�tten Sie gerne Carbon und Radium. Sie w�rden diese Carbon und dieses Radium gerne im Verh�ltnis 1:1 ausgezahlt bekommen, das hei�t, dass Sie f�r die eine H�lfte Ihres Tritiums Carbon haben wollen, f�r die andere aber Radium.</p>
			<p>Sie tippen dazu einfach ins Carbon-Verh�ltnisfeld (das kleinere Feld links vom Carbonfeld auf der rechten Seite) eine 1 ein, ebenso ins Radium-Verh�ltnisfeld. (Verh�ltnis: 1:1)</p>
			<hr />
		</div>
<?php
	if(isset($_GET['hilfe']) && $_GET['hilfe'] != '1')
	{
		$c = ob_get_contents();
		ob_end_clean();
		echo str_replace(array("\n", "\r", "\t"), array('\\n', '\\r', ''), preg_replace('/[\'\\\\]/', '\\\\$0', $c))."');\n";
?>
			// ]]>
		</script>
<?php
	}
	if(isset($_GET['hilfe']) && $_GET['hilfe'] != '2')
	{
?>
		<script type="text/javascript">
			// <![CDATA[
<?php
		echo "\t\t\tdocument.write('";
		ob_start();
	}
?>
		<div id="beispiel-2" class="handelsrechner-beispiel">
			<h4>Beispiel 2</h4>
			<p>Sie m�chten einen Handel betreiben, f�r den Sie 10<?=THS_HTML?>000&nbsp;Carbon und 7<?=THS_HTML?>000&nbsp;Aluminium erhalten wollen. Sie m�chten daf�r in Wolfram und Radium bezahlen. Die Bezahlung soll im Verh�ltnis 2:1 geschehen, das hei�t, Sie m�chten zwei Drittel des Preises in Wolfram und ein Drittel in Radium bezahlen. Dazu tippen Sie nun einfach in das kleine Feld vor dem Wolframfeld auf der linken Seite eine 2, in das vor dem Radiumfeld eine 1 ein.</p>
			<hr />
		</div>
<?php
	if(isset($_GET['hilfe']) && $_GET['hilfe'] != '2')
	{
		$c = ob_get_contents();
		ob_end_clean();
		echo str_replace(array("\n", "\r", "\t"), array('\\n', '\\r', ''), preg_replace('/[\'\\\\]/', '\\\\$0', $c))."');\n";
?>
			// ]]>
		</script>
<?php
	}
?>
		<ul id="hilfe-schliessen">
			<li><a href="<?=htmlentities($_SERVER['PHP_SELF'])?>" onclick="hide_hilfe(); return false;">Hilfe schlie�en</a></li>
		</ul>
<?php
	if(!isset($_GET['hilfe']))
	{
		$c = ob_get_contents();
		ob_end_clean();
		echo str_replace(array("\n", "\r", "\t"), array('\\n', '\\r', ''), preg_replace('/[\'\\\\]/', '\\\\$0', $c))."');\n";
?>
		// ]]>
	</script>
<?php
	}
?>
	</div>
	<script type="text/javascript">
<?php
	if(isset($_GET['hilfe']) && $_GET['hilfe'] == '1')
	{
?>
		window.beispiel1 = true;
<?php
	}
	else
	{
?>
		document.getElementById('beispiel-1').style.display = 'none';
		window.beispiel1 = false;
<?php
	}
	if(isset($_GET['hilfe']) && $_GET['hilfe'] == '2')
	{
?>
		window.beispiel2 = true;
<?php
	}
	else
	{
?>
		document.getElementById('beispiel-2').style.display = 'none';
		window.beispiel2 = false;
<?php
	}
?>
	</script>
</form>
<script type="text/javascript">
	// <![CDATA[
	function reset_handelskurs()
	{
		document.getElementById('handelskurs-carbon').value = '<?=round($handelskurs[0], 2)?>';
		document.getElementById('handelskurs-aluminium').value = '<?=round($handelskurs[1], 2)?>';
		document.getElementById('handelskurs-wolfram').value = '<?=round($handelskurs[2], 2)?>';
		document.getElementById('handelskurs-radium').value = '<?=round($handelskurs[3], 2)?>';
		document.getElementById('handelskurs-tritium').value = '<?=round($handelskurs[4], 2)?>';
	}

	function refresh_eingabe()
	{
		var zurueck = document.getElementById('eingabe-zurueck').checked;
		if((zurueck && window.angabe != 2) || (!zurueck && window.angabe != 1))
		{
			if(zurueck)
			{
				/* Erh�ltnis eingeben */
				document.getElementById('angebot-carbon').setAttribute('readonly', 'readonly');
				document.getElementById('angebot-aluminium').setAttribute('readonly', 'readonly');
				document.getElementById('angebot-wolfram').setAttribute('readonly', 'readonly');
				document.getElementById('angebot-radium').setAttribute('readonly', 'readonly');
				document.getElementById('angebot-tritium').setAttribute('readonly', 'readonly');

				document.getElementById('angebot-anteil-carbon').style.visibility='visible';
				document.getElementById('angebot-anteil-aluminium').style.visibility='visible';
				document.getElementById('angebot-anteil-wolfram').style.visibility='visible';
				document.getElementById('angebot-anteil-radium').style.visibility='visible';
				document.getElementById('angebot-anteil-tritium').style.visibility='visible';


				document.getElementById('zurueck-carbon').removeAttribute('readonly');
				document.getElementById('zurueck-aluminium').removeAttribute('readonly');
				document.getElementById('zurueck-wolfram').removeAttribute('readonly');
				document.getElementById('zurueck-radium').removeAttribute('readonly');
				document.getElementById('zurueck-tritium').removeAttribute('readonly');

				document.getElementById('zurueck-anteil-carbon').style.visibility='hidden';
				document.getElementById('zurueck-anteil-aluminium').style.visibility='hidden';
				document.getElementById('zurueck-anteil-wolfram').style.visibility='hidden';
				document.getElementById('zurueck-anteil-radium').style.visibility='hidden';
				document.getElementById('zurueck-anteil-tritium').style.visibility='hidden';
			}
			else
			{
				/* Angebot eingeben */
				document.getElementById('angebot-carbon').removeAttribute('readonly');
				document.getElementById('angebot-aluminium').removeAttribute('readonly');
				document.getElementById('angebot-wolfram').removeAttribute('readonly');
				document.getElementById('angebot-radium').removeAttribute('readonly');
				document.getElementById('angebot-tritium').removeAttribute('readonly');

				document.getElementById('angebot-anteil-carbon').style.visibility = 'hidden';
				document.getElementById('angebot-anteil-aluminium').style.visibility = 'hidden';
				document.getElementById('angebot-anteil-wolfram').style.visibility = 'hidden';
				document.getElementById('angebot-anteil-radium').style.visibility = 'hidden';
				document.getElementById('angebot-anteil-tritium').style.visibility = 'hidden';


				document.getElementById('zurueck-carbon').setAttribute('readonly', 'readonly');
				document.getElementById('zurueck-aluminium').setAttribute('readonly', 'readonly');
				document.getElementById('zurueck-wolfram').setAttribute('readonly', 'readonly');
				document.getElementById('zurueck-radium').setAttribute('readonly', 'readonly');
				document.getElementById('zurueck-tritium').setAttribute('readonly', 'readonly');

				document.getElementById('zurueck-anteil-carbon').style.visibility = 'visible';
				document.getElementById('zurueck-anteil-aluminium').style.visibility = 'visible';
				document.getElementById('zurueck-anteil-wolfram').style.visibility = 'visible';
				document.getElementById('zurueck-anteil-radium').style.visibility = 'visible';
				document.getElementById('zurueck-anteil-tritium').style.visibility = 'visible';
			}
			window.eingabe = (zurueck ? 2 : 1);

			if(!window.fortgeschritten || zurueck)
			{
				document.getElementById('angebot-carbon').value = '0';
				document.getElementById('angebot-aluminium').value = '0';
				document.getElementById('angebot-wolfram').value = '0';
				document.getElementById('angebot-radium').value = '0';
				document.getElementById('angebot-tritium').value = '0';
			}

			document.getElementById('angebot-anteil-carbon').value = '0';
			document.getElementById('angebot-anteil-aluminium').value = '0';
			document.getElementById('angebot-anteil-wolfram').value = '0';
			document.getElementById('angebot-anteil-radium').value = '0';
			document.getElementById('angebot-anteil-tritium').value = '0';


			if(!window.fortgeschritten || !zurueck)
			{
				document.getElementById('zurueck-carbon').value = '0';
				document.getElementById('zurueck-aluminium').value = '0';
				document.getElementById('zurueck-wolfram').value = '0';
				document.getElementById('zurueck-radium').value = '0';
				document.getElementById('zurueck-tritium').value = '0';
			}

			document.getElementById('zurueck-anteil-carbon').value = '0';
			document.getElementById('zurueck-anteil-aluminium').value = '0';
			document.getElementById('zurueck-anteil-wolfram').value = '0';
			document.getElementById('zurueck-anteil-radium').value = '0';
			document.getElementById('zurueck-anteil-tritium').value = '0';


			document.getElementById('error-message').style.display = 'none';

			if(zurueck)
				document.getElementById('zurueck-carbon').focus();
			else
				document.getElementById('angebot-carbon').focus();
		}
	}

	function refresh_modus()
	{
		var fortgeschrittener_modus = document.getElementById('fortgeschrittener-modus-input').checked;
		if(window.fortgeschritten != fortgeschrittener_modus)
		{
			if(fortgeschrittener_modus)
			{
				document.getElementById('angebot-anteil-carbon').style.display = 'inline';
				document.getElementById('angebot-anteil-aluminium').style.display = 'inline';
				document.getElementById('angebot-anteil-wolfram').style.display = 'inline';
				document.getElementById('angebot-anteil-radium').style.display = 'inline';
				document.getElementById('angebot-anteil-tritium').style.display = 'inline';

				document.getElementById('zurueck-anteil-carbon').style.display = 'inline';
				document.getElementById('zurueck-anteil-aluminium').style.display = 'inline';
				document.getElementById('zurueck-anteil-wolfram').style.display = 'inline';
				document.getElementById('zurueck-anteil-radium').style.display = 'inline';
				document.getElementById('zurueck-anteil-tritium').style.display = 'inline';
			}
			else
			{
				document.getElementById('angebot-anteil-carbon').style.display = 'none';
				document.getElementById('angebot-anteil-aluminium').style.display = 'none';
				document.getElementById('angebot-anteil-wolfram').style.display = 'none';
				document.getElementById('angebot-anteil-radium').style.display = 'none';
				document.getElementById('angebot-anteil-tritium').style.display = 'none';

				document.getElementById('zurueck-anteil-carbon').style.display = 'none';
				document.getElementById('zurueck-anteil-aluminium').style.display = 'none';
				document.getElementById('zurueck-anteil-wolfram').style.display = 'none';
				document.getElementById('zurueck-anteil-radium').style.display = 'none';
				document.getElementById('zurueck-anteil-tritium').style.display = 'none';
			}
			window.fortgeschritten = fortgeschrittener_modus;
		}

		calc();
	}

	function init()
	{
		var error_message = document.createElement('p');
		error_message.setAttribute('class', 'error');
		error_message.setAttribute('id', 'error-message');
		error_message.style.display = 'none';
		error_message.appendChild(document.createTextNode('Bitte gib g�ltige Werte ein.'));
		document.getElementById('angebot').parentNode.insertBefore(error_message, document.getElementById('angebot'));

		window.eingabe = 3;
		refresh_eingabe();
		window.fortgeschritten = 2;
		refresh_modus();
		calc();

	}

	function calc()
	{
		if(window.eingabe == 2)
		{
			from = 'zurueck';
			to = 'angebot';
		}
		else
		{
			from = 'angebot';
			to = 'zurueck';
		}

		var carbon_wert = parseInt(document.getElementById(from+'-carbon').value);
		var aluminium_wert = parseInt(document.getElementById(from+'-aluminium').value);
		var wolfram_wert = parseInt(document.getElementById(from+'-wolfram').value);
		var radium_wert = parseInt(document.getElementById(from+'-radium').value);
		var tritium_wert = parseInt(document.getElementById(from+'-tritium').value);

		var carbon_kurs = parseFloat(document.getElementById('handelskurs-carbon').value.replace(/,/, '.'));
		var aluminium_kurs = parseFloat(document.getElementById('handelskurs-aluminium').value.replace(/,/, '.'));
		var wolfram_kurs = parseFloat(document.getElementById('handelskurs-wolfram').value.replace(/,/, '.'));
		var radium_kurs = parseFloat(document.getElementById('handelskurs-radium').value.replace(/,/, '.'));
		var tritium_kurs = parseFloat(document.getElementById('handelskurs-tritium').value.replace(/,/, '.'));

		var fortgeschrittener_modus = document.getElementById('fortgeschrittener-modus-input').checked;
		if(fortgeschrittener_modus)
		{
			var carbon_anteil = parseFloat(document.getElementById(to+'-anteil-carbon').value.replace(/,/, '.'));
			var aluminium_anteil = parseFloat(document.getElementById(to+'-anteil-aluminium').value.replace(/,/, '.'));
			var wolfram_anteil = parseFloat(document.getElementById(to+'-anteil-wolfram').value.replace(/,/, '.'));
			var radium_anteil = parseFloat(document.getElementById(to+'-anteil-radium').value.replace(/,/, '.'));
			var tritium_anteil = parseFloat(document.getElementById(to+'-anteil-tritium').value.replace(/,/, '.'));
		}

		if(isNaN(carbon_wert) || isNaN(aluminium_wert) || isNaN(wolfram_wert) || isNaN(radium_wert) || isNaN(tritium_wert) || isNaN(carbon_kurs) || isNaN(aluminium_kurs) || isNaN(wolfram_kurs) || isNaN(radium_kurs) || isNaN(tritium_kurs) || (fortgeschrittener_modus && (isNaN(carbon_anteil) || isNaN(aluminium_anteil) || isNaN(wolfram_anteil) || isNaN(radium_anteil) || isNaN(tritium_anteil))))
		{
			document.getElementById('error-message').style.display = 'block';
			document.getElementById(to+'-carbon').value = '';
			document.getElementById(to+'-aluminium').value = '';
			document.getElementById(to+'-wolfram').value = '';
			document.getElementById(to+'-radium').value = '';
			document.getElementById(to+'-tritium').value = '';
		}
		else
		{
			document.getElementById('error-message').style.display = 'none';

			var wert=0;
			wert += carbon_wert/carbon_kurs;
			wert += aluminium_wert/aluminium_kurs;
			wert += wolfram_wert/wolfram_kurs;
			wert += radium_wert/radium_kurs;
			wert += tritium_wert/tritium_kurs;

			if(fortgeschrittener_modus)
			{
				var anteil_gesamt = carbon_anteil+aluminium_anteil+wolfram_anteil+radium_anteil+tritium_anteil;

				if(anteil_gesamt != 0)
				{
					carbon_kurs *= carbon_anteil/anteil_gesamt;
					aluminium_kurs *= aluminium_anteil/anteil_gesamt;
					wolfram_kurs *= wolfram_anteil/anteil_gesamt;
					radium_kurs *= radium_anteil/anteil_gesamt;
					tritium_kurs *= tritium_anteil/anteil_gesamt;
				}
				else
				{
					carbon_kurs = 0;
					aluminium_kurs = 0;
					wolfram_kurs = 0;
					radium_kurs = 0;
					tritium_kurs = 0;
				}
			}

			document.getElementById(to+'-carbon').value = Math.round(wert*carbon_kurs);
			document.getElementById(to+'-aluminium').value = Math.round(wert*aluminium_kurs);
			document.getElementById(to+'-wolfram').value = Math.round(wert*wolfram_kurs);
			document.getElementById(to+'-radium').value = Math.round(wert*radium_kurs);
			document.getElementById(to+'-tritium').value = Math.round(wert*tritium_kurs);

			var gesamt = carbon_wert+aluminium_wert+wolfram_wert+radium_wert+tritium_wert
			var grendarls = Math.ceil(gesamt/25000);
			var lunaren = Math.ceil(gesamt/125000);
		}
	}

	function show_hilfe()
	{
		document.getElementById('hilfe').style.display = 'block';
		document.getElementById('hilfe-trenn').style.display = 'block';
		document.getElementById('fortgeschrittener-modus').style.display = 'none';

		document.getElementById('fortgeschrittener-modus-input').checked = true;
		refresh_modus();
	}

	function hide_hilfe()
	{
		document.getElementById('hilfe').style.display = 'none';
		document.getElementById('hilfe-trenn').style.display = 'none';
		document.getElementById('fortgeschrittener-modus').style.display = 'block';
	}

	function show_beispiel_1()
	{
		if(window.beispiel1)
		{
			document.getElementById('beispiel-1').style.display = 'none';
			window.beispiel1 = false;
		}
		else
		{
			document.getElementById('beispiel-1').style.display = 'block';
			document.getElementById('beispiel-2').style.display = 'none';
			window.beispiel1 = true;
			window.beispiel2 = false;
		}
	}

	function show_beispiel_2()
	{
		if(window.beispiel2)
		{
			document.getElementById('beispiel-2').style.display = 'none';
			window.beispiel2 = false;
		}
		else
		{
			document.getElementById('beispiel-1').style.display = 'none';
			document.getElementById('beispiel-2').style.display = 'block';
			window.beispiel1 = false;
			window.beispiel2 = true;
		}
	}

	<?=isset($_GET['hilfe']) ? 'show' : 'hide'?>_hilfe();
	init();
	// ]]>
</script>
<?php
	login_gui::html_foot();
?>