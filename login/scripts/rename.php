<?php
	require('include.php');

	$planet_error = false;
	if(isset($_POST['planet_name']))
	{
		if(trim($_POST['planet_name']) == '')
			$_POST['planet_name'] = $this_planet['name'];
		elseif(strlen($_POST['planet_name']) <= 24)
		{
			$old_name = $this_planet['name'];
			$this_planet['name'] = $_POST['planet_name'];
			if(!write_user_array())
				$planet_error = true;
			else
			{
				$pos = explode(':', $this_planet['pos']);

				$old_info = universe::get_planet_info($pos[0], $pos[1], $pos[2]);
				if(!$old_info || !universe::set_planet_info($pos[0], $pos[1], $pos[2], $old_info[0], $_SESSION['username'], $_POST['planet_name']))
				{
					$this_planet['name'] = $old_name;
					write_user_array();
					$planet_error = true;
				}
			}
		}
	}

	login_gui::html_head();

	if($planet_error)
	{
?>
<p class="error">
	Datenbankfehler.
</p>
<?php
	}
	elseif(isset($_POST['planet_name']) && strlen($_POST['planet_name']) > 24)
	{
?>
<p class="error">
	Der Name darf maximal 24&nbsp;Bytes lang sein.
</p>
<?php
	}
?>
<form action="rename.php" method="post">
	<fieldset>
		<legend>Planeten umbenennen</legend>
		<dl>
			<dt><label for="name"><kbd>N</kbd>euer Name</label></dt>
			<dd><input type="text" id="name" name="planet_name" value="<?=utf8_htmlentities($this_planet['name'])?>" maxlength="24" accesskey="n" tabindex="1" /></dd>
		</dl>
		<ul>
			<li><button type="submit" accesskey="u" tabindex="2"><kbd>U</kbd>mbenennen</button></li>
		</ul>
	</fieldset>
</form>
<?php
	login_gui::html_foot();
?>