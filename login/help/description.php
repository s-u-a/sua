<?php
	require('../scripts/include.php');

	login_gui::html_head();

	if(!isset($_GET['id'])) $item = false;
	else $item = Classes::Item($_GET['id']);
	
	if(!$item || !$item->getInfo())
	{
?>
<p class="error">Dieser Gegenstand existiert nicht.</p>
<?php
	}
	else
	{
		$type = $item->getType();
		if($type == 'gebaeude' || $type == 'forschung')
			$lvl = $me->getItemLevel($_GET['id']);
		else
			$lvl = -1;
?>
<div class="desc">
	<h2><?=utf8_htmlentities($item->getInfo('name'))?><?php if($lvl >= 0){?> <span class="stufe">(Stufe&nbsp;<?=utf8_htmlentities($lvl)?>)</span><?php }?></h2>
<?php
		$desc = $item->getInfo('caption');

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

		print($desc);
?>
</div>
<?php
		if($type == 'gebaeude')
		{
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
				<td><?=utf8_htmlentities($item->getInfo('fields'))?></td>
			</tr>
		</tbody>
	</table>
</div>
<?php
			$prod = $item->getInfo('prod');
			if(array_sum($prod) != 0)
			{
?>
<div class="desc-prod">
	<h3>Produktion pro Stunde</h3>
	<table>
		<thead>
			<tr>
				<th class="c-stufe">Stufe</th>
<?php
				if($prod[0] != 0)
				{
?>
				<th class="c-carbon">Carbon</th>
<?php
				}
				if($prod[1] != 0)
				{
?>
				<th class="c-aluminium">Aluminium</th>
<?php
				}
				if($prod[2] != 0)
				{
?>
				<th class="c-wolfram">Wolfram</th>
<?php
				}
				if($prod[3] != 0)
				{
?>
				<th class="c-radium">Radium</th>
<?php
				}
				if($prod[4] != 0)
				{
?>
				<th class="c-tritium">Tritium</th>
<?php
				}
				if($prod[5] != 0)
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
					if($prod[0] != 0)
					{
?>
				<td class="c-carbon"><?=ths($prod[0]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[1] != 0)
					{
?>
				<td class="c-aluminium"><?=ths($prod[1]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[2] != 0)
					{
?>
				<td class="c-wolfram"><?=ths($prod[2]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[3] != 0)
					{
?>
				<td class="c-radium"><?=ths($prod[3]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[4] != 0)
					{
?>
				<td class="c-tritium"><?=ths($prod[4]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[5] != 0)
					{
?>
				<td class="c-energie"><?=ths($prod[5]*pow($act_lvl, 2))?></td>
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
		}

		if($type == 'schiffe')
		{
			$trans = $item->getInfo('trans');
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
				<td><?=ths($trans[0])?>&nbsp;Tonnen, <?=ths($trans[1])?>&nbsp;Roboter</td>
			</tr>
			<tr>
				<th>Angriffsstärke</th>
				<td><?=ths($item->getInfo('att'))?></td>
			</tr>
			<tr>
				<th>Schild</th>
				<td><?=ths($item->getInfo('def'))?></td>
			</tr>
			<tr>
				<th>Antriebsstärke</th>
				<td><?=ths($item->getInfo('speed'))?>&thinsp;<abbr title="Milliorbits pro Quadratsekunde">mOr&frasl;s²</abbr></td>
			</tr>
			<tr>
				<th>Unterstützte Auftragsarten</th>
				<td>
					<ul>
<?php
			foreach($item->getInfo('types') as $t)
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

		if($type == 'verteidigung')
		{
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
				<td><?=ths($item->getInfo('att'))?></td>
			</tr>
			<tr>
				<th>Schild</th>
				<td><?=ths($item->getInfo('def'))?></td>
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