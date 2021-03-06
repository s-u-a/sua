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
	/**
	 * Zeigt die Beschreibung eines Gegenstandes an.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('../include.php');

	$gui->init();

	if(!isset($_GET['id'])) $item = false;
	else $item = Classes::Item($_GET['id']);

	if(!$item || !$item->getInfo())
	{
?>
<p class="error"><?=L::h(_("Dieser Gegenstand existiert nicht."))?></p>
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
	<h2><?=L::h(sprintf($lvl, _("[item_".$_GET["id"]."]"), F::ths($me->getItemLevel($_GET["id"]))))?></h2>
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

		$desc = "\t\t<p>\n\t\t\t".preg_replace('/[\n]+/e', 'repl_nl(\'$0\');', L::_i(h(_("[itemdesc_".$_GET["id"]."]")), true))."\n\t\t</p>\n";

		print($desc);
?>
	</div>
<?php
		$item_info = $me->getItemInfo($_GET['id'], $type);
		if($item_info)
		{
?>
	<dl class="item-info lines">
		<dt class="item-kosten"><?=L::h(_("Kosten"))?></dt>
		<dd class="item-kosten">
<?php
			echo F::formatRess($item_info['ress'], 3, false, false, false, $me);
?>
		</dd>

<?php
			if($type == 'forschung')
			{
?>
		<dt class="item-bauzeit forschung-lokal"><?=L::h(_("Bauzeit lokal"))?></dt>
		<dd class="item-bauzeit forschung-lokal"><?=F::formatBTime($item_info['time_local'])?></dd>

		<dt class="item-bauzeit forschung-global"><?=L::h(_("Bauzeit global"))?></dt>
		<dd class="item-bauzeit forschung-global"><?=F::formatBTime($item_info['time_global'])?></dd>
<?php
			}
			else
			{
?>
		<dt class="item-bauzeit"><?=L::h(_("Bauzeit"))?></dt>
		<dd class="item-bauzeit"><?=F::formatBTime($item_info['time'])?></dd>
	</dl>
<?php
			}
		}

		$deps = $me->getItemDeps($_GET["id"]);
		if(count($deps) > 0)
		{
			Item::isort($deps);
?>
	<div class="desc-deps">
		<h3 class="strong"><?=L::h(_("Abhängigkeiten"))?></h3>
		<ul class="deps paragraph">
<?php
			foreach($deps as $id=>$level)
			{
?>
			<li class="deps-<?=htmlspecialchars($id)?> deps-<?=($me->getItemLevel($id) >= $level) ? "ja" : "nein"?>"><?=sprintf(h(_("%s (Stufe %s)")), "<a href=\"description.php?id=".htmlspecialchars(urlencode($id)."&".global_setting("URL_SUFFIX"))."\" title=\"".h(_("Genauere Informationen anzeigen"))."\">".h(_("[item_".$id."]"))."</a>", F::ths($level))?></li>
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
		<h3 class="strong"><?=L::h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=L::h(_("Eigenschaft"))?></th>
					<th><?=L::h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=L::h(_("Benötigte Felderzahl"))?></th>
					<td><?=F::ths($item->getInfo('fields'))?></td>
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
		<h3 class="strong"><?=L::h(_("Produktion pro Stunde"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th class="c-stufe"><?=L::h(_("Stufe"))?></th>
<?php
				if($prod[0] != 0)
				{
?>
					<th class="c-carbon"><?=L::h(_("[ress_0]"))?></th>
<?php
				}
				if($prod[1] != 0)
				{
?>
					<th class="c-aluminium"><?=L::h(_("[ress_1]"))?></th>
<?php
				}
				if($prod[2] != 0)
				{
?>
					<th class="c-wolfram"><?=L::h(_("[ress_2]"))?></th>
<?php
				}
				if($prod[3] != 0)
				{
?>
					<th class="c-radium"><?=L::h(_("[ress_3]"))?></th>
<?php
				}
				if($prod[4] != 0)
				{
?>
					<th class="c-tritium"><?=L::h(_("[ress_4]"))?></th>
<?php
				}
				if($prod[5] != 0)
				{
?>
					<th class="c-energie"><?=L::h(_("[ress_5]"))?></th>
<?php
				}
?>
			</thead>
			<tbody>
<?php
				$database_config = Classes::Database(global_setting("DB"))->getConfig();
				if(isset($database_config["global_factors"]) && isset($database_config["global_factors"]["production"]))
				{
					$prod[0] *= $database_config["global_factors"]["production"];
					$prod[1] *= $database_config["global_factors"]["production"];
					$prod[2] *= $database_config["global_factors"]["production"];
					$prod[3] *= $database_config["global_factors"]["production"];
					$prod[4] *= $database_config["global_factors"]["production"];
					$prod[5] *= $database_config["global_factors"]["production"];
				}

				$start_lvl = $me->getItemLevel($_GET["id"])-3;
				if($start_lvl < 1)
					$start_lvl = 1;

				for($x=0; $x <= 10; $x++)
				{
					$act_lvl = $start_lvl+$x;
?>
				<tr<?=($act_lvl == $me->getItemLevel($_GET["id"])) ? ' class="active"' : ''?>>
					<th><?=F::ths($act_lvl)?></th>
<?php
					if($prod[0] != 0)
					{
?>
					<td class="c-carbon"><?=F::ths($prod[0]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[1] != 0)
					{
?>
					<td class="c-aluminium"><?=F::ths($prod[1]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[2] != 0)
					{
?>
					<td class="c-wolfram"><?=F::ths($prod[2]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[3] != 0)
					{
?>
					<td class="c-radium"><?=F::ths($prod[3]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[4] != 0)
					{
?>
					<td class="c-tritium"><?=F::ths($prod[4]*pow($act_lvl, 2))?></td>
<?php
					}
					if($prod[5] != 0)
					{
?>
					<td class="c-energie"><?=F::ths($prod[5]*pow($act_lvl, 2))?></td>
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
		<h3 class="strong"><?=L::h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=L::h(_("Eigenschaft"))?></th>
					<th><?=L::h(_("Ursprungswert"))?></th>
					<th><?=L::h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=L::h(_("Transportkapazität"))?></th>
					<td><?=L::h(sprintf(_("%s Tonnen, %s Roboter"), F::ths($trans[0]), F::ths($trans[1])))?></td>
					<td><?=L::h(sprintf(_("%s Tonnen, %s Roboter"), F::ths($item_info["trans"][0]), F::ths($item_info["trans"][1])))?></td>
				</tr>
				<tr>
					<th><?=L::h(_("Angriffsstärke"))?></th>
					<td><?=F::ths($item->getInfo('att'))?></td>
					<td><?=F::ths($item_info["att"])?></td>
				</tr>
				<tr>
					<th><?=L::h(_("Schild"))?></th>
					<td><?=F::ths($item->getInfo('def'))?></td>
					<td><?=F::ths($item_info["def"])?></td>
				</tr>
				<tr>
					<th><?=L::h(_("Antriebsstärke"))?></th>
					<td><?=F::ths($item->getInfo('speed'))?><?=L::h(_("[unit_separator]"))?><abbr title="<?=L::h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $item->getInfo("speed")))?>"><?=L::h(_("µOr⁄s²"))?></abbr></td>
					<td><?=F::ths($item_info["speed"])?><?=L::h(_("[unit_separator]"))?><abbr title="<?=L::h(ngettext("Mikroorbit pro Quadratsekunde", "Mikroorbits pro Quadratsekunde", $item->getInfo("speed")))?>"><?=L::h(_("µOr⁄s²"))?></abbr></td>
				</tr>
				<tr>
					<th><?=L::h(_("Unterstützte Auftragsarten"))?></th>
					<td colspan="2">
						<ul>
<?php
			foreach($item->getInfo('types') as $t)
			{
?>
							<li><?=L::h(_("[fleet_".$t."]"))?></li>
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
		<h3 class="strong"><?=L::h(_("Eigenschaften"))?></h3>
		<table class="table-small">
			<thead>
				<tr>
					<th><?=L::h(_("Eigenschaft"))?></th>
					<th><?=L::h(_("Ursprungswert"))?></th>
					<th><?=L::h(_("Wert"))?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th><?=L::h(_("Angriffsstärke"))?></th>
					<td><?=F::ths($item->getInfo('att'))?></td>
					<td><?=F::ths($item_info["att"])?></td>
				</tr>
				<tr>
					<th><?=L::h(_("Schild"))?></th>
					<td><?=F::ths($item->getInfo('def'))?></td>
					<td><?=F::ths($item_info["def"])?></td>
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