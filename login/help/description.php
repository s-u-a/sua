<?php
	require('../scripts/include.php');

	login_gui::html_head();

	if(!isset($_GET['id']) || !isset($items['ids'][$_GET['id']]))
	{
?>
<p class="error">Dieser Gegenstand existiert nicht.</p>
<?php
	}
	else
	{
		$i = & $items['ids'][$_GET['id']];

		if(isset($items['gebaeude'][$_GET['id']]) || isset($items['forschung'][$_GET['id']]))
		{
			$lvl = 0;
			if(isset($this_planet['ids'][$_GET['id']]))
				$lvl = $this_planet['ids'][$_GET['id']];
		}
		else
			$lvl = -1;
?>
<div class="desc">
	<h2><?=utf8_htmlentities($i['name'])?><?php if($lvl >= 0){?> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($lvl)?>)</span><?php }?></h2>
<?php
		$desc = $i['caption'];

		function repl_nl($nls)
		{
			$len = strlen($nls);
			if($len == 1)
				return "<br />\n\t\t";
			elseif($len == 2)
				return "\n\t</p>\n\t<p>\n\t\t";
			elseif($len > 2)
				return "\n\t</p>\n\t".str_repeat('<br />', $len-2)."\n\t<p>\n\t\t";
		}

		$desc = "\t<p>\n\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', utf8_htmlentities($desc))."\n\t</p>\n";

		echo $desc;
?>
</div>
<?php
		if(isset($items['gebaeude'][$_GET['id']]) && ($i['prod'][0] != 0 || $i['prod'][1] != 0 || $i['prod'][2] != 0 || $i['prod'][3] != 0 || $i['prod'][4] != 0 || $i['prod'][5] != 0))
		{
			# Es handelt sich um ein Gebaeude
			# Produktion ausgeben
?>
<div class="desc-values">
	<h3>Eigenschaften</h3>
	<table>
		<thead>
			<tr>
				<th>Eigenschaft</th>
				<th>Wert</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Benötigte Felderzahl</th>
				<td><?=utf8_htmlentities($i['fields'])?></td>
			</tr>
		</tbody>
	</table>
</div>
<div class="desc-prod">
	<h3>Produktion pro Stunde</h3>
	<table>
		<thead>
			<tr>
				<th class="c-stufe">Stufe</th>
<?php
			if($i['prod'][0] != 0)
			{
?>
				<th class="c-carbon">Kohle</th>
<?php
			}
			if($i['prod'][1] != 0)
			{
?>
				<th class="c-aluminium">Aluminium</th>
<?php
			}
			if($i['prod'][2] != 0)
			{
?>
				<th class="c-wolfram">Wolfram</th>
<?php
			}
			if($i['prod'][3] != 0)
			{
?>
				<th class="c-radium">Radium</th>
<?php
			}
			if($i['prod'][4] != 0)
			{
?>
				<th class="c-tritium">Tritium</th>
<?php
			}
			if($i['prod'][5] != 0)
			{
?>
				<th class="c-energie">Energie</th>
<?php
			}
?>
		</thead>
		<tbody>
<?php
			$start_lvl = $lvl-3;
			if($start_lvl < 1)
				$start_lvl = 1;

			for($x=0; $x <= 10; $x++)
			{
				$act_lvl = $start_lvl+$x;
?>
			<tr<?=($act_lvl == $lvl) ? ' class="active"' : ''?>>
				<th><?=ths($act_lvl)?></th>
<?php
				if($i['prod'][0] != 0)
				{
?>
				<td class="c-carbon"><?=ths($i['prod'][0]*pow($act_lvl, 2))?></td>
<?php
				}
				if($i['prod'][1] != 0)
				{
?>
				<td class="c-aluminium"><?=ths($i['prod'][1]*pow($act_lvl, 2))?></td>
<?php
				}
				if($i['prod'][2] != 0)
				{
?>
				<td class="c-wolfram"><?=ths($i['prod'][2]*pow($act_lvl, 2))?></td>
<?php
				}
				if($i['prod'][3] != 0)
				{
?>
				<td class="c-radium"><?=ths($i['prod'][3]*pow($act_lvl, 2))?></td>
<?php
				}
				if($i['prod'][4] != 0)
				{
?>
				<td class="c-tritium"><?=ths($i['prod'][4]*pow($act_lvl, 2))?></td>
<?php
				}
				if($i['prod'][5] != 0)
				{
?>
				<td class="c-energie"><?=ths($i['prod'][5]*pow($act_lvl, 2))?></td>
<?php
				}
?>
			</tr>
<?php
			}
?>
		</tbody>
	</table>
</div>
<?php
		}

		if(isset($items['schiffe'][$_GET['id']]))
		{
			# Es handelt sich um ein Schiff
			# Werte ausgeben
?>
<div class="desc-values">
	<h3>Eigenschaften</h3>
	<table>
		<thead>
			<tr>
				<th>Eigenschaft</th>
				<th>Wert</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Transportkapazität</th>
				<td><?=ths($i['trans'][0])?>&nbsp;Tonnen, <?=ths($i['trans'][1])?>&nbsp;Roboter</td>
			</tr>
			<tr>
				<th>Angriffsstärke</th>
				<td><?=ths($i['att'])?></td>
			</tr>
			<tr>
				<th>Schild</th>
				<td><?=ths($i['def'])?></td>
			</tr>
			<tr>
				<th>Antriebsstärke</th>
				<td><?=ths($i['speed'])?>&nbsp;Megawatt</td>
			</tr>
			<tr>
				<th>Unterstützte Auftragsarten</th>
				<td>
					<ul>
<?php
			foreach($i['types'] as $t)
			{
				if(isset($type_names[$t]))
					$t = $type_names[$t];
?>
						<li><?=utf8_htmlentities($t)?></li>
<?php
			}
?>
					</ul>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<?php
		}

		if(isset($items['verteidigung'][$_GET['id']]))
		{
			# Es handelt sich um eine Verteidigungsanlange
			# Werte ausgeben
?>
<div class="desc-values">
	<h3>Eigenschaften</h3>
	<table>
		<thead>
			<tr>
				<th>Eigenschaft</th>
				<th>Wert</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Angriffsstärke</th>
				<td><?=ths($i['att'])?></td>
			</tr>
			<tr>
				<th>Schild</th>
				<td><?=ths($i['def'])?></td>
			</tr>
		</tbody>
	</table>
</div>
<?php
		}
	}
?>
<?php
	login_gui::html_foot();
?>