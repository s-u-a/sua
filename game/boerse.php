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
	 * Handelsbörse, auf der Rohstoffe eingetauscht werden können.
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage login
	*/
	namespace sua\psua;

	require('include.php');

	$market = Classes::Market();

	if(isset($_GET['cancel']))
		$market->cancelOrder($_GET['cancel'], $me->getName());

	$gui->init();

	$taxes = array (
		"1" => .05,
		"12" => .1,
		"24" => .15,
		"48" => .2,
		"168" => .25,
		"336" => .3,
		"672" => .4
	);
?>
<h2><?=L::h(_("Handelsbörse"))?></h2>
<p><?=L::h(_("Hier können Sie einen Rohstoffbetrag bieten und einen Mindestbetrag an Rohstoffen dafür verlangen. Eine unabhängige Händlergesellschaft (besteht nicht aus reellen Spielern) wird sich um den Auftrag kümmern, sobald der Kurs Ihren Handelsauftrag zulässt. Jedoch wird der Handel nicht sofort durchgeführt, die Handelsgesellschaft entscheidet, wann die den Auftrag annimmt."))?></p>
<p><?=L::h(_("Je größer die Rohstoffmenge ist, die Sie handeln wollen, desto länger dauert es, bis die Handelsgesellschaft den Auftrag annimmt. Je länger Sie Ihr Angebot aufrecht erhalten wollen, desto mehr Gebühren müssen Sie für den Handel zahlen. Bei einer geringen Lebensdauer kann es passieren, dass die Handelsgesellschaft den Auftrag überhaupt nicht annimmt."))?></p>
<p><?=L::h(_("Der Handelskurs wird durch Angebot und Nachfrage bestimmt. Wenn viele Spieler nur wenig für eine Rohstoffmenge haben wollen, sinkt deren Preis, wenn sie viel dafür verlangen, steigt der Preis. Wenn aber viele den Rohstoff haben wollen, steigt der Preis, wenn sie viel dafür bieten; er sinkt, wenn sie wenig dafür bieten."))?></p>
<script type="text/javascript">
// <![CDATA[
	var prices = {
		1: { 2:<?=$market->getRate(1, 2)?>, 3:<?=$market->getRate(1, 3)?>, 4:<?=$market->getRate(1, 4)?>, 5:<?=$market->getRate(1, 5)?> },
		2: { 1:<?=$market->getRate(2, 1)?>, 3:<?=$market->getRate(2, 3)?>, 4:<?=$market->getRate(2, 4)?>, 5:<?=$market->getRate(2, 5)?> },
		3: { 1:<?=$market->getRate(3, 1)?>, 2:<?=$market->getRate(3, 2)?>, 4:<?=$market->getRate(3, 4)?>, 5:<?=$market->getRate(3, 5)?> },
		4: { 1:<?=$market->getRate(4, 1)?>, 2:<?=$market->getRate(4, 2)?>, 3:<?=$market->getRate(4, 3)?>, 5:<?=$market->getRate(4, 5)?> },
		5: { 1:<?=$market->getRate(5, 1)?>, 2:<?=$market->getRate(5, 2)?>, 3:<?=$market->getRate(5, 3)?>, 4:<?=$market->getRate(5, 4)?> }
	};

	var taxes = {
		1: <?=$taxes['1']?>,
		12: <?=$taxes['12']?>,
		24: <?=$taxes['24']?>,
		48: <?=$taxes['48']?>,
		168: <?=$taxes['168']?>,
		336: <?=$taxes['336']?>,
		672: <?=$taxes['672']?>
	};

	function refresh_offers()
	{
		var req = document.getElementById('i-angebot-rohstoff').value;

		var s_el = document.getElementById('i-gewuenschter-rohstoff');
		var old_v = s_el.selectedIndex;

		for(var i=s_el.options.length-1; i>=0; i--)
			s_el.options[i] = null;

		var i=0;
		if(req != "1") s_el.options[i++] = new Option('<?=JS::jsentities(_("[ress_0]"))?>', "1", old_v == "1", old_v == "1");
		if(req != "2") s_el.options[i++] = new Option('<?=JS::jsentities(_("[ress_1]"))?>', "2", old_v == "2", old_v == "2");
		if(req != "3") s_el.options[i++] = new Option('<?=JS::jsentities(_("[ress_2]"))?>', "3", old_v == "3", old_v == "3");
		if(req != "4") s_el.options[i++] = new Option('<?=JS::jsentities(_("[ress_3]"))?>', "4", old_v == "4", old_v == "4");
		if(req != "5") s_el.options[i++] = new Option('<?=JS::jsentities(_("[ress_4]"))?>', "5", old_v == "5", old_v == "5");
	}

	function refresh_costs()
	{
		document.getElementById('aktueller-ertrag').firstChild.data = Math.round(myParseInt(document.getElementById('i-menge').value) * prices[document.getElementById('i-angebot-rohstoff').value][document.getElementById('i-gewuenschter-rohstoff').value]);
		document.getElementById('zusaetzliche-gebuehren').firstChild.data = Math.round(myParseInt(document.getElementById('i-menge').value) * taxes[document.getElementById('i-angebotsdauer').value]);
	}
// ]]>
</script>
<h3 class="boerse-auftraege-heading strong" id="handelsauftraege"><?=L::h(_("Handelsaufträge"))?></h3>
<?php
	if(isset($_POST['res_offered']) && isset($_POST['amount']) && isset($_POST['res_requested']) && isset($_POST['min_price']) && isset($_POST['duration']) && in_array($_POST['res_offered'], array("1", "2", "3", "4", "5")) && in_array($_POST['res_requested'], array("1", "2", "3", "4", "5")) && $_POST['res_offered'] != $_POST['res_requested'] && $_POST['amount'] > 0 && in_array($_POST['duration'], array("1", "12", "24", "48", "168", "336", "672")))
	{
		$cur_ress = $me->getRess();
		$incl_tax = round($_POST['amount']*(1+$taxes[$_POST['duration']]));
		if($incl_tax > $cur_ress[$_POST['res_offered']-1])
		{
?>
<p class="error"><?=L::h(_("Sie können nicht mehr anbieten als Sie besitzen."))?></p>
<?php
		}
		else
		{
			$ress = array(0, 0, 0, 0, 0);
			$ress[$_POST['res_offered']-1] = -$incl_tax;
			$market->addOrder($me->getName(), $me->getActivePlanet(), $_POST['res_offered'], $_POST['amount'], $_POST['res_requested'], $_POST['min_price']/$_POST['amount'], time()+$_POST['duration']*3600);
			$me->addRess($ress);
?>
<p class="successful"><?=L::h(_("Der Auftrag wurde hinzugefügt."))?></p>
<?php
		}
	}

	$tabindex_save = $tabindex;
	$orders = $market->getOrders($me->getName());
	if(count($orders) > 0)
	{
?>
<table class="boerse-auftraege">
	<thead>
		<tr>
			<th class="c-planet"><?=L::h(_("Planet"))?></th>
			<th class="c-gebot"><?=L::h(_("Gebot"))?></th>
			<th class="c-mindestertrag"><?=L::h(_("Mindestertrag"))?></th>
			<th class="c-gueltigkeit"><?=L::h(_("Gültigkeit"))?></th>
			<th class="c-status"><?=L::h(_("Status"))?></th>
			<th class="c-zurueckziehen"><?=L::h(_("Zurückziehen"))?></th>
		</tr>
	</thead>
	<tbody>
<?php
		$tabindex += 6;
		$i = 0;
		$countdowns = array();
		$active_planet = $me->getActivePlanet();
		foreach($orders as $order)
		{
			$me->setActivePlanet($order['planet']);
			define_url_suffix();
?>
		<tr class="gebot-<?=htmlspecialchars($order['offered_resource'])?> ertrag-<?=htmlspecialchars($order['requested_resource'])?>">
			<td class="c-planet"><a href="boerse.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" tabindex="<?=$tabindex++?>"><?=sprintf(h(_("„%s“ (%s)")), htmlspecialchars($me->planetName()), "<span class=\"koords\">".htmlspecialchars($me->getPosString())."</span>")?></a></td>
			<td class="c-gebot"><span class="zahl"><?=F::ths($order['amount'])?></span> <?=L::h(_("[ress_".($order['offered_resource']-1)."]"))?></td>
			<td class="c-mindestertrag"><span class="zahl"><?=F::ths($order['amount']*$order['min_price'])?></span> <?=L::h(_("[ress_".($order['requested_resource']-1)."]"))?></td>
			<td class="c-gueltigkeit" id="restbauzeit-boerse-<?=htmlspecialchars($i)?>"><?=sprintf(h(_("%s (Serverzeit)")), htmlspecialchars(date(_('H:i:s, Y-m-d'), $order['expiration'])))?></td>
<?php
			$countdowns["boerse-".$i] = $order['expiration'];

			if($order['finish'] == -1)
			{
?>
			<td class="c-status waiting"><?=L::h(_("Warte auf Händler"))?></td>
			<td class="c-zurueckziehen waiting"><a href="boerse.php?cancel=<?=htmlspecialchars(urlencode($order['id']))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>"><?=L::h(_("Zurückziehen"))?></a></td>
<?php
			}
			else
			{
?>
			<td class="c-status" id="restbauzeit-boersew-<?=htmlspecialchars($i)?>"><?=sprintf(h(_("%s (Serverzeit)")), htmlspecialchars(date(_('H:i:s, Y-m-d'), $order['finish'])))?></td>
			<td class="c-zurueckziehen">&mdash;</td>
<?php
				$countdowns["boersew-".$i] = $order['finish'];
			}
?>
		</tr>
<?php
			$i++;
		}
		$me->setActivePlanet($active_planet);
		define_url_suffix();
		list($tabindex, $tabindex_save) = array($tabindex_save, $tabindex);
?>
	</tbody>
</table>
<script type="text/javascript">
<?php
		foreach($countdowns as $i=>$exp)
		{
?>
	init_countdown('<?=$i?>', <?=$exp?>, false);
<?php
		}
?>
</script>
<?php
	}
?>
<form action="boerse.php?<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>#handelsauftraege" method="post" class="boerse-auftrag">
	<fieldset>
		<legend><?=L::h(_("Neuen Handelsauftrag anlegen"))?></legend>
		<dl class="form">
			<dt class="c-angebot"><label for="i-angebot-rohstoff"><?=L::h(_("Angeb&ot[login/boerse.php|1]"))?></label></dt>
			<dd class="c-angebot">
				<select name="res_offered" id="i-angebot-rohstoff" onchange="refresh_offers(); refresh_costs();" onkeypress="onchange()" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Angebot&[login/boerse.php|1]"))?>>
					<option value="1"<?php if(isset($_POST['res_offered']) && $_POST['res_offered'] == 1){?> selected="selected"<?php }?>><?=L::h(_("[ress_0]"))?></option>
					<option value="2"<?php if(isset($_POST['res_offered']) && $_POST['res_offered'] == 2){?> selected="selected"<?php }?>><?=L::h(_("[ress_1]"))?></option>
					<option value="3"<?php if(isset($_POST['res_offered']) && $_POST['res_offered'] == 3){?> selected="selected"<?php }?>><?=L::h(_("[ress_2]"))?></option>
					<option value="4"<?php if(isset($_POST['res_offered']) && $_POST['res_offered'] == 4){?> selected="selected"<?php }?>><?=L::h(_("[ress_3]"))?></option>
					<option value="5"<?php if(isset($_POST['res_offered']) && $_POST['res_offered'] == 5){?> selected="selected"<?php }?>><?=L::h(_("[ress_4]"))?></option>
				</select>
				<input type="text" id="i-menge" name="amount" value="<?=isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : 0?>" onchange="refresh_costs();" onkeypress="onchange();" onclick="onchange();" tabindex="<?=$tabindex++?>" />
			</dd>

			<dt class="c-gewuenschter-rohstoff"><label for="i-gewuenschter-rohstoff"><?=L::h(_("Gewünschter Rohstoff&[login/boerse.php|1]"))?></label></dt>
			<dd class="c-gewuenschter-rohstoff">
				<select name="res_requested" id="i-gewuenschter-rohstoff" onchange="refresh_costs();" onkeypress="onchange();" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Gewünschter Rohstoff&[login/boerse.php|1]"))?>>
					<option value="1"<?php if(isset($_POST['res_requested']) && $_POST['res_requested'] == 1){?> selected="selected"<?php }?>><?=L::h(_("[ress_0]"))?></option>
					<option value="2"<?php if(isset($_POST['res_requested']) && $_POST['res_requested'] == 2){?> selected="selected"<?php }?>><?=L::h(_("[ress_1]"))?></option>
					<option value="3"<?php if(isset($_POST['res_requested']) && $_POST['res_requested'] == 3){?> selected="selected"<?php }?>><?=L::h(_("[ress_2]"))?></option>
					<option value="4"<?php if(isset($_POST['res_requested']) && $_POST['res_requested'] == 4){?> selected="selected"<?php }?>><?=L::h(_("[ress_3]"))?></option>
					<option value="5"<?php if(isset($_POST['res_requested']) && $_POST['res_requested'] == 5){?> selected="selected"<?php }?>><?=L::h(_("[ress_4]"))?></option>
				</select>
			</dd>

			<dt class="c-minimale-menge"><label for="i-minimale-menge"><?=L::h(_("Minimale Menge&[login/boerse.php|1]"))?></label></dt>
			<dd class="c-minimale-menge"><input type="text" id="i-minimale-menge" name="min_price" value="<?=isset($_POST['min_price']) ? htmlspecialchars($_POST['min_price']) : 0?>" onchange="refresh_costs();" onkeypress="onchange();" onclick="onchange();" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Minimale Menge&[login/boerse.php|1]"))?> /></dd>

			<dt class="c-angebotsdauer"><label for="i-angebotsdauer"><?=L::h(_("Angebotsdauer&[login/boerse.php|1]"))?></label></dt>
			<dd class="c-angebotsdauer">
				<select name="duration" id="i-angebotsdauer" onchange="refresh_costs();" onkeypress="onchange();" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Angebotsdauer&[login/boerse.php|1]"))?>>
					<option value="1"<?php if(isset($_POST['duration']) && $_POST['duration'] == 1){?> selected="selected"<?php }?>><?=sprintf(h(_("Eine Stunde (%s %%)")), 5)?></option>
					<option value="12"<?php if(isset($_POST['duration']) && $_POST['duration'] == 12){?> selected="selected"<?php }?>><?=sprintf(h(_("Zwölf Stunden (%s %%)")), 10)?></option>
					<option value="24"<?php if(isset($_POST['duration']) && $_POST['duration'] == 24){?> selected="selected"<?php }?>><?=sprintf(h(_("Einen Tag (%s %%)")), 15)?></option>
					<option value="48"<?php if(isset($_POST['duration']) && $_POST['duration'] == 48){?> selected="selected"<?php }?>><?=sprintf(h(_("Zwei Tage (%s %%)")), 20)?></option>
					<option value="168"<?php if(isset($_POST['duration']) && $_POST['duration'] == 168){?> selected="selected"<?php }?>><?=sprintf(h(_("Eine Woche (%s %%)")), 25)?></option>
					<option value="336"<?php if(isset($_POST['duration']) && $_POST['duration'] == 336){?> selected="selected"<?php }?>><?=sprintf(h(_("Zwei Wochen (%s %%)")), 30)?></option>
					<option value="672"<?php if(isset($_POST['duration']) && $_POST['duration'] == 672){?> selected="selected"<?php }?>><?=sprintf(h(_("Vier Wochen (%s %%)")), 40)?></option>
				</select>
			</dd>

			<dt class="c-aktueller-ertrag"><?=L::h(_("Aktueller Ertrag"))?></dt>
			<dd class="c-aktueller-ertrag" id="aktueller-ertrag">0</dd>

			<dt class="c-zusaetzliche-gebuehren"><?=L::h(_("Zusätzliche Gebühren"))?></dt>
			<dd class="c-zusaetzliche-gebuehren" id="zusaetzliche-gebuehren">0</dd>
		</dl>
		<div class="button"><button type="submit" tabindex="<?=$tabindex++?>"<?=L::accesskeyAttr(_("Auftrag aufgeben&[login/boerse.php|1]"))?>><?=L::h(_("Auftrag aufgeben&[login/boerse.php|1]"))?></button></div>
	</fieldset>
</form>
<?php
	$tabindex = $tabindex_save;
?>
<script type="text/javascript">
	refresh_offers();
	refresh_costs();
</script>
<h3 class="boerse-kurs-heading" class="strong"><?=L::h(_("Kurs"))?></h3>
<table class="boerse-kurs">
	<thead>
		<tr>
			<th class="c-von" rowspan="2"><?=L::h(_("von"))?></th>
			<th class="c-nach" colspan="5"><?=L::h(_("nach"))?></th>
		</tr>
		<tr>
			<th class="c-carbon"><?=L::h(_("[ress_0]"))?></th>
			<th class="c-aluminium"><?=L::h(_("[ress_1]"))?></th>
			<th class="c-wolfram"><?=L::h(_("[ress_2]"))?></th>
			<th class="c-radium"><?=L::h(_("[ress_3]"))?></th>
			<th class="c-tritium"><?=L::h(_("[ress_4]"))?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="r-carbon">
			<th class="c-von"><?=L::h(_("[ress_0]"))?></th>
			<td class="c-carbon disabled"><?=L::h(_("—"))?></td>
			<td class="c-aluminium"><?=F::ths($market->getRate(1, 2), 2)?></td>
			<td class="c-wolfram"><?=F::ths($market->getRate(1, 3), 2)?></td>
			<td class="c-radium"><?=F::ths($market->getRate(1, 4), 2)?></td>
			<td class="c-tritium"><?=F::ths($market->getRate(1, 5), 2)?></td>
		</tr>
		<tr class="r-aluminium">
			<th class="c-von"><?=L::h(_("[ress_1]"))?></th>
			<td class="c-carbon"><?=F::ths($market->getRate(2, 1), 2)?></td>
			<td class="c-aluminium disabled"><?=L::h(_("—"))?></td>
			<td class="c-wolfram"><?=F::ths($market->getRate(2, 3), 2)?></td>
			<td class="c-radium"><?=F::ths($market->getRate(2, 4), 2)?></td>
			<td class="c-tritium"><?=F::ths($market->getRate(2, 5), 2)?></td>
		</tr>
		<tr class="r-wolfram">
			<th class="c-von"><?=L::h(_("[ress_2]"))?>m</th>
			<td class="c-carbon"><?=F::ths($market->getRate(3, 1), 2)?></td>
			<td class="c-aluminium"><?=F::ths($market->getRate(3, 2), 2)?></td>
			<td class="c-wolfram disabled"><?=L::h(_("—"))?></td>
			<td class="c-radium"><?=F::ths($market->getRate(3, 4), 2)?></td>
			<td class="c-tritium"><?=F::ths($market->getRate(3, 5), 2)?></td>
		</tr>
		<tr class="r-radium">
			<th class="c-von"><?=L::h(_("[ress_3]"))?></th>
			<td class="c-carbon"><?=F::ths($market->getRate(4, 1), 2)?></td>
			<td class="c-aluminium"><?=F::ths($market->getRate(4, 2), 2)?></td>
			<td class="c-wolfram"><?=F::ths($market->getRate(4, 3), 2)?></td>
			<td class="c-radium disabled"><?=L::h(_("—"))?></td>
			<td class="c-tritium"><?=F::ths($market->getRate(4, 5), 2)?></td>
		</tr>
		<tr class="r-tritium">
			<th class="c-von"><?=L::h(_("[ress_4]"))?></th>
			<td class="c-carbon"><?=F::ths($market->getRate(5, 1), 2)?></td>
			<td class="c-aluminium"><?=F::ths($market->getRate(5, 2), 2)?></td>
			<td class="c-wolfram"><?=F::ths($market->getRate(5, 3), 2)?></td>
			<td class="c-radium"><?=F::ths($market->getRate(5, 4), 2)?></td>
			<td class="c-tritium disabled"><?=L::h(_("—"))?></td>
		</tr>
	</tbody>
</table>
<?php
	$gui->end();
?>
