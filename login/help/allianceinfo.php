<?php
	require('../scripts/include.php');

	login_gui::html_head();

	if(!isset($_GET['alliance']) || !is_file(DB_ALLIANCES.'/'.urlencode($_GET['alliance'])) || !is_readable(DB_ALLIANCES.'/'.urlencode($_GET['alliance'])))
	{
?>
<p class="error">
	Diese Allianz gibt es nicht.
</p>
<?php
	}
	else
	{
		$alliance_array = get_alliance_array($_GET['alliance']);

		$punktzahl = 0;
		foreach($alliance_array['members'] as $member)
			$punktzahl += $member['punkte'];
		$punktzahl = floor($punktzahl/count($alliance_array['members']));
?>
<h2>Allianzinfo <em class="alliancename"><?=utf8_htmlentities($_GET['alliance'])?></em></h2>
<dl class="allianceinfo">
	<dt class="c-allianztag">Allianz<span xml:lang="en">tag</span></dt>
	<dd class="c-allianztag"><?=utf8_htmlentities($_GET['alliance'])?></dd>

	<dt class="c-name">Name</dt>
	<dd class="c-name"><?=utf8_htmlentities($alliance_array['name'])?></dd>

	<dt class="c-mitglieder">Mitglieder</dt>
	<dd class="c-mitglieder"><?=htmlentities(count($alliance_array['members']))?></dd>

	<dt class="c-punktzahl">Punktzahl</dt>
	<dd class="c-punktzahl"><?=ths($punktzahl)?></dd>
</dl>
<h3 id="allianzbeschreibung">Allianzbeschreibung</h3>
<div class="allianz-externes">
<?php
		if(!isset($alliance_array['description_parsed']))
		{
			$alliance_array['description_parsed'] = parse_html($alliance_array['description']);
			write_alliance_array($_GET['alliance'], $alliance_array);
		}

		print($alliance_array['description_parsed']);
?>
</div>
<?php
		if(!$user_array['alliance'])
		{
?>
<ul class="allianz-bewerben">
	<li><a href="../allianz.php?action=apply&amp;for=<?=htmlentities(urlencode($_GET['alliance']))?>&amp;<?=htmlentities(urlencode(SESSION_COOKIE).'='.urlencode(session_id()))?>">Bei dieser Allianz bewerben</a></li>
</ul>
<?php
		}
	}

	login_gui::html_foot();
?>