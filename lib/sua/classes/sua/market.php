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
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Kontrolliert die Rohstoff-Handelsbörse.
	 * @todo Arbeitet noch mit altem setActivePlanet()-Zeugs
	*/

	class Market extends SQL
	{
		/**
		 * Berechnet den Handelskurs vom Rohstoff $offer zum Rohstoff $request neu.
		 * @param int $offer Der Rohstoff des Angebots (0: Carbon, 1: Aluminium, ...)
		 * @param int $request Der Rohstoff der Nachfrage
		 * @param bool $transaction Wenn true, wird das Annehmen von Aufträgen, das aus der Kursänderung entsteht, mit transactionQuery() statt query() durchgeführt.
		 * @return void
		*/

		public function recalcRate($offer, $request, $transaction=false)
		{
			$check_prices = array();
			$this->query("SELECT DISTINCT ROUND(c_min_price, 2) FROM t_market WHERE c_offered_resource = ".$this->escape($offer)." AND c_requested_resource = ".$this->escape($request).";");
			while($r = $this->nextResult())
				$check_prices[] = 0+array_shift($r);
			$this->query("SELECT DISTINCT ROUND(1/c_min_price, 2) FROM t_market WHERE c_offered_resource = ".$this->escape($request)." AND c_requested_resource = ".$this->escape($offer).";");
			while($r = $this->nextResult())
				$check_prices[] = 0+array_shift($r);
			$check_prices = array_unique($check_prices);
			rsort($check_prices, SORT_NUMERIC);

			$diff = null;
			$best_price = null;
			foreach($check_prices as $price)
			{
				$sum1 = 0+$this->singleField("SELECT SUM(c_amount) FROM t_market WHERE c_offered_resource = ".$this->escape($offer)." AND c_requested_resource = ".$this->escape($request)." AND c_min_price >= ".$this->escape($price).";");
				$sum2 = 0;
				if($price > 0)
					$sum2 += $this->singleField("SELECT SUM(c_amount) FROM t_market WHERE c_offered_resource = ".$this->escape($request)." AND c_requested_resource = ".$this->escape($offer)." AND c_min_price >= ".$this->escape(round(1/$price, 2)).";");
				$this_diff = abs($sum1-$sum2);
				if($diff === null || $this_diff < $diff)
				{
					$diff = $this_diff;
					$best_price = $price;
				}
				elseif($sum2 < $sum1)
					break;
			}

			$this->query("INSERT INTO t_market_rate ( c_offer, c_request, c_price, c_date ) VALUES ( ".$this->escape($offer).", ".$this->escape($request).", ".$this->escape($best_price).", ".$this->escape(microtime(true))." );");

			$this->checkAccepted($offer, $request, $transaction);
		}

		/**
		 * Gibt den Handelskurs vom Rohstoff $offer zum Rohstoff $request zurück.
		 * @param int $offer 0: Carbon; 1: Aluminium; ...
		 * @param int $request
		 * @return float
		*/

		public function getRate($offer, $request)
		{
			$q = $this->arrayQuery("SELECT c_price FROM t_market_rate WHERE c_offer = ".$this->escape($offer)." AND c_request = ".$this->escape($request)." ORDER BY c_date desc LIMIT 1;");
			if(!isset($q[0])) return 0;
			return 0+$q[0]['c_price'];
		}

		/**
		 * Gibt eine neue ID für einen Handelsauftrag zurück.
		 * @return int
		*/

		protected function getNewId()
		{
			return 1+$this->singleField("SELECT c_id FROM t_market ORDER BY c_id DESC LIMIT 1;");
		}

		/**
		 * Gibt einen neuen Handelsauftrag auf.
		 * @param string $user
		 * @param int $planet Der Index des Planeten, vom dem die Rohstoffe genommen wurden
		 * @param int $offered_resource Der angebotene Rohstoff (0: Carbon; 1: Aluminium; ...)
		 * @param int $amount Die angebotene Menge.
		 * @param int $requested_resource Der gewünschte Rohstoff
		 * @param float $min_price Der Mindest-Kurs, zu dem der Handel durchgeführt wird.
		 * @param int $expiration Wie viele Sekunden ist das Angebot gültig?
		 * @return int Die ID des Auftrags.
		*/

		public function addOrder($user, $planet, $offered_resource, $amount, $requested_resource, $min_price, $expiration)
		{
			$id = $this->getNewId();
			$this->query("INSERT INTO t_market ( c_id, c_user, c_planet, c_offered_resource, c_amount, c_requested_resource, c_min_price, c_expiration, c_date, c_finish ) VALUES ( ".$this->escape($id).", ".$this->escape($user).", ".$this->escape($planet).", ".$this->escape(0+$offered_resource).", ".$this->escape(0+$amount).", ".$this->escape(0+$requested_resource).", ".$this->escape(0+$min_price).", ".$this->escape(0+$expiration).", ".$this->escape(time()).", -1);");
			$this->recalcRate($offered_resource, $requested_resource, true);

			for($i=1; $i<=5; $i++)
			{
				if($i == $requested_resource) continue;
				$this->checkAccepted($offered_resource, $i, true);
			}

			$this->endTransaction();

			return $id;
		}

		/**
		 * Schaut, welche Handelsaufträge vom Rohstoff $offered_resource zum Rohstoff
		 * $requested_resource durchgeführt werden können und führt diese durch.
		 * @param int $offered_resource Der angebotene Rohstoff (0: Carbon; 1: Aluminium; ...)
		 * @param int $requested_resource Der nachgefragte Rohstoff
		 * @param bool $transaction Wenn true, wird zur Durchführung der Aufträge transactionQuery() statt query() verwendet.
		 * @return void
		*/

		public function checkAccepted($offered_resource, $requested_resource, $transaction=false)
		{
			# Bestehende Angebote einzuloesen versuchen
			$this->query("SELECT * FROM t_market WHERE c_offered_resource = ".$this->escape($offered_resource)." AND c_finish = -1 AND c_expiration < ".$this->escape(time())." AND c_min_price <= ".$this->escape($this->getRate($offered_resource, $requested_resource)).";");
			while($r = $this->nextResult())
			{
				$sum = $this->singleField("SELECT sum(c_amount) FROM t_market WHERE c_offered_resource = ".$this->escape($r['c_offered_resource'])." AND c_date >= ".$this->escape($r['c_date'])." AND c_user != ".$this->escape($r['c_user']).";");
				$count = $this->singleField("SELECT count(DISTINCT c_user) FROM t_market WHERE c_offered_resource = ".$this->escape($r['c_offered_resource'])." AND c_user != ".$this->escape($r['c_user']).";");
				if($sum >= Config::getLibConfig()->getConfigValueE("market", "min_amount")*$r['c_amount'] && $count > Config::getLibConfig()->getConfigValueE("market", "min_amount"))
				{
					# Auftrag wird ausgefuehrt
					$this->acceptOrder($r['c_id'], $transaction);
				}
			}
		}

		/**
		 * Führt den Auftrag mit der Nummer $id durch.
		 * @param int $id
		 * @param bool $transaction Wenn true, wird transactionQuery() statt query() verwendet.
		 * @return void
		*/

		public function acceptOrder($id, $transaction=false)
		{
			# TODO: Benachrichtigung
			$q = "UPDATE t_market SET c_finish = ".$this->escape(time()+Config::getLibConfig()->getConfigValueE("market", "delay"))." WHERE c_id = ".$this->escape($id).";";
			if($transaction)
				$this->transactionQuery($q);
			else
				$this->query($q);
		}

		/**
		 * Gibt alle Handelsaufträge in einem Array von assoziativen Arrays zurück,
		 * die der Benutzer am Laufen hat.
		 * Die assoziativen Arrays haben folgendes Format:
		 * • c_id => Die Auftrags-ID
		 * • c_user => Der Benutzer
		 * • c_planet => Die Planetennummer
		 * • c_offered_resource => Der angebotene Rohstoff (0: Carbon; 1: Aluminium; ...)
		 * • c_amount => Die angebotene Menge
		 * • c_requested_resource => Der nachgefragte Rohstoff
		 * • c_min_price => Der Mindestkurs zur Durchführung
		 * • c_expiration => Der Zeitpunkt, zu dem das Angebot abläuft
		 * • c_finish => Der Zeitpunkt, zu dem der Handel durchgeführt wird, sofern dieser feststeht
		 * @param string $user
		 * @return array
		*/

		public function getOrders($user)
		{
			$this->cleanUp($user);
			return $this->arrayQuery("SELECT c_id, c_user, c_planet, c_offered_resource, c_amount, c_requested_resource, c_min_price, c_expiration, c_finish FROM t_market WHERE c_user = ".$this->escape($user).";");
		}

		/**
		 * Gibt ein Array von assoziativen Arrays mit ID (id) und Durchführungs-
		 * zeitpunkt (finish) der Aufträge ein, die bis zum Zeitpunkt $max_time
		 * durchgeführt werden/wurden.
		 * @param int $max_time
		 * @return array
		*/

		public function getFinishingOrdersList($max_time)
		{
			return $this->arrayQuery("SELECT c_id, c_finish FROM t_market WHERE c_finish <= ".$this->escape($max_time)." AND c_finish != -1 ORDER BY c_finish ASC;");
		}

		/**
		 * Führt den Auftrag mit der ID $id durch.
		 * @param int $id
		 * @return void
		 * @todo Benachrichtung des Benutzers
		*/

		public function finishOrder($id)
		{
			$r = $this->arrayQuery("SELECT * FROM t_market WHERE c_id = ".$this->escape($id).";");
			if(!isset($r[0])) return false;
			$u = Classes::User($r[0]['c_user']);
			$this->query("DELETE FROM t_market WHERE c_id = ".$this->escape($id).";");
			$u->setActivePlanet($r[0]['c_planet']);
			$u->addRess(array($r[0]['c_requested_resource']-1 => max($r[0]['c_amount']*$r[0]['c_min_price'], $r[0]['c_amount']*$this->getRate($r[0]['c_offered_resource'], $r[0]['c_requested_resource']))));
		}

		/**
		 * Bricht den Handelsauftrag mit der ID $id des Benutzers $user ab.
		 * @param int $id
		 * @param string $user
		 * @return void
		*/

		public function cancelOrder($id, $user)
		{
			$u = Classes::User($user);
			$r = $this->arrayQuery("SELECT c_id, c_planet, c_amount, c_offered_resource, c_requested_resource FROM t_market WHERE c_id = ".$this->escape($id)." AND c_user = ".$this->escape($u->getName())." AND c_finish = -1;");
			if(isset($r[0]))
			{
				$this->query("DELETE FROM t_market WHERE c_id = ".$this->escape($r[0]['c_id']).";");
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r[0]['c_offered_resource']-1] = $r[0]['c_amount'];
				$planet = $u->getActivePlanet();
				$u->setActivePlanet($r[0]['c_planet']);
				$u->addRess($ress);
				$u->setActivePlanet($planet);

				$this->recalcRate($r[0]['c_offered_resource'], $r[0]['c_requested_resource']);
			}
		}

		/**
		 * Entfernt die abgelaufenen Angebote des Benutzers $user aus der Datenbank.
		 * @param string $user
		 * @return void
		*/

		public function cleanUp($user)
		{
			$u = Classes::User($user);
			$planet = $u->getActivePlanet();
			$recalc = array();
			$this->query("SELECT c_id, c_planet, c_amount, c_offered_resource, c_requested_resource FROM t_market WHERE c_expiration < ".$this->escape(time())." AND c_finish = -1 AND c_user = ".$this->escape($u->getName()).";");
			while($r = $this->nextResult())
			{
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r['c_offered_resource']-1] = $r['c_amount'];
				$u->setActivePlanet($r['c_planet']);
				$u->addRess($ress);
				$this->transactionQuery("DELETE FROM t_market WHERE c_id = ".$this->escape($r['c_id']).";");
				$recalc[] = array($r['c_offered_resource'], $r['c_requested_resource']);
			}
			$this->endTransaction();
			$u->setActivePlanet($planet);

			foreach(array_unique($recalc) as $r)
				$this->recalcRate($r[0], $r[1], true);
			$this->endTransaction();
		}
	}
