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
	require('../include.php');

	$gui->init();

	if(!isset($_GET['id'])) $item = false;
	else $item = Classes::Item($_GET['id']);

	if(!$item || !$item->getInfo())
	{
?>
<p class="error"><?=h(_("Dieser Gegenstand existiert nicht."))?></p>
<?php
	}
	else
	{
		$type = $item->getType();
		if($type == 'gebaeude' || $type == 'forschung')
			$lvl = _("%s (Stufe %s)");
		else
			$lvl = "%s";
?>
<div class="desc desc-<?=htmlspecialchars($_GET['id'])?> desc-<?=htmlspecialchars($type)?>">
	<h2><?=h(sprintf($lvl, _("[item_".$_GET["id"]."]"), ths($me->getItemLevel($_GET["id"]))))?></h2>
	<div class="desc-desc">
<?php
		function repl_nl($nls)
		{
			$len = strlen($nls);
			if($len == 1)
				return "<br />\n\t\t\t";
			elseif($len == 2)
				return "\n\t\t</p>\n\t\t<p>\n\t\t\t";
			elseif($len > 2)
				return "\n\t\t</p>\n\t\t".str_repeat('<br />', $len-2)."\n\t\t<p>\n\t\t\t";
		}

		$desc = "\t\t<p>\n\t\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', _i(h(_("[itemdesc_".$_GET["id"]."]")), true))."\n\t\t</p>\n";

		print($desc);
?>
	</div>
<?php
		$item_info = $me->getItemInfo($_GET['id'], $type);
		if($item_info)
		{
?>
	<dl class="item-info lines">
		<dt class="item-kosten"><?=h(_("Kosten"))?></dt>
		<dd class="item-kosten">
<?php
			echo format_ress($item_info['ress'], 3, false, false, false, $me);
?>
		</dd>

<?php
			if($type == 'forschung')
			{
?>
		<dt class="item-bauzeit forschung-lokal"><?=h(_("Bauzeit lokal"))?></dt>
		<dd class="item-bauzeit forschung-lokal"><?=format_btime($item_info['time_local'])?></dd>

		<dt class="item-bauzeit forschung-global"><?=h(_("Bauzeit global"))?></dt>
		<dd class="item-bauzeit forschung-global"><?=format_btime($item_info['time_global'])?></dd>
<?php
			}
			else
			{
?>
		<dt class="item-bauzeit"><?=h(_("Bauzeit"))?></dt>
		<dd class="item-bauzeit"><?=format_btime($item_info['time'])?></dd>
	</dl>
<?php
			}
		}

		$deps = $me->getItemDeps($_GET["id"]);
		if(count($deps) > 0)
		{
			Items::isort($deps);
?>
	<div class="desc-deps">
		<h3 class="strong"><?=h(_("Abhängigkeiten"))?></h3>
		<ul class="deps paragraph">
<?php
			foreach($deps as $id=>$level)
			{
?>
			<li class="deps-<?=htmlspecialchars($id)?> deps-<?=($me->getItemLevel($id) >= $level) ? "ja" : "nein"?>"><?=sprintf(h(_("%s (Stufe %s)")), "<a href=\"description.php?id=".htmlspecialchars(urlencode($id)."&".global_setting("URL_SUFFIX"))."\" title=\"".h(_("Genauere Informationen anzeigen"))."\">".h(_("[item_".$id."]"))."</a>", ths($level))?></li>
<?php
			}
?>
		</ul>
	</div>
<?php
		}

		if($type == 'gebaeude')
		{
?>
	<div class="desc-values">
		<h3 class="strong"><?=h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=h(_("Eigenschaft"))?></th>
					<th><?=h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=h(_("Benötigte Felderzahl"))?></th>
					<td><?=ths($item->getInfo('fields'))?></td>
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
		<h3 class="strong"><?=h(_("Produktion pro Stunde"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th class="c-stufe"><?=h(_("Stufe"))?></th>
<?php
				if($prod[0] != 0)
				{
?>
					<th class="c-carbon"><?=h(_("[ress_0]"))?></th>
<?php
				}
				if($prod[1] != 0)
				{
?>
					<th class="c-aluminium"><?=h(_("[ress_1]"))?></th>
<?php
				}
				if($prod[2] != 0)
				{
?>
					<th class="c-wolfram"><?=h(_("[ress_2]"))?></th>
<?php
				}
				if($prod[3] != 0)
				{
?>
					<th class="c-radium"><?=h(_("[ress_3]"))?></th>
<?php
				}
				if($prod[4] != 0)
				{
?>
					<th class="c-tritium"><?=h(_("[ress_4]"))?></th>
<?php
				}
				if($prod[5] != 0)
				{
?>
					<th class="c-energie"><?=h(_("[ress_5]"))?></th>
<?php
				}
?>
			</thead>
			<tbody>
<?php
				$global_factors = get_global_factors();
				$prod[0] *= $global_factors['prod'];
				$prod[1] *= $global_factors['prod'];
				$prod[2] *= $global_factors['prod'];
				$prod[3] *= $global_factors['prod'];
				$prod[4] *= $global_factors['prod'];
				$prod[5] *= $global_factors['prod'];

				$start_lvl = $me->getItemLevel($_GET["id"])-3;
				if($start_lvl < 1)
					$start_lvl = 1;

				for($x=0; $x <= 10; $x++)
				{
					$act_lvl = $start_lvl+$x;
?>
				<tr<?=($act_lvl == $me->getItemLevel($_GET["id"])) ? ' class="active"' : ''?>>
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
		<h3 class="strong"><?=h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=h(_("Eigenschaft"))?></th>
					<th><?=h(_("Ursprungswert"))?></th>
					<th><?=h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=h(_("Transportkapazität"))?></th>
					<td><?=h(sprintf(_("%s Tonnen, %s Roboter"), ths($trans[0]), ths($trans[1])))?></td>
					<td><?=h(sprintf(_("%s Tonnen, %s Roboter"), ths($item_info["trans"][0]), ths($item_info["trans"][1])))?></td>
				</tr>
				<tr>
					<th><?=h(_("Angriffsstärke"))?></th>
					<td><?=ths($item->getInfo('att'))?></td>
					<td><?=ths($item_info["att"])?></td>
				</tr>
				<tr>
					<th><?=h(_("Schild"))?></th>
					<td><?=ths($item->getInfo('def'))?></td>
					<td><?=ths($item_info["def"])?></td>
				</tr>
				<tr>
					<th><?=h(_("Antriebsstärke"))?></th>
					<td><?=ths($item->getInfo('speed'))?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $item->getInfo("speed")))?>"><?=h(_("µOr⁄s²"))?></abbr></td>
					<td><?=ths($item_info["speed"])?><?=h(_("[unit_separator]"))?><abbr title="<?=h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $item->getInfo("speed")))?>"><?=h(_("µOr⁄s²"))?></abbr></td>
				</tr>
				<tr>
					<th><?=h(_("Unterstützte Auftragsarten"))?></th>
					<td colspan="2">
						<ul>
<?php
			foreach($item->getInfo('types') as $t)
			{
?>
							<li><?=h(_("[fleet_".$t."]"))?></li>
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
		<h3 class="strong"><?=h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=h(_("Eigenschaft"))?></th>
					<th><?=h(_("Ursprungswert"))?></th>
					<th><?=h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=h(_("Angriffsstärke"))?></th>
					<td><?=ths($item->getInfo('att'))?></td>
					<td><?=ths($item_info["att"])?></td>
				</tr>
				<tr>
					<th><?=h(_("Schild"))?></th>
					<td><?=ths($item->getInfo('def'))?></td>
					<td><?=ths($item_info["def"])?></td>
				</tr>
			</tbody>
		</table>
	</div>
<?php
		}
	}
?>
</div>
<?php
	$gui->end();
?>