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
	 * @subpackage storage
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Kontrolliert die Rohstoff-Handelsbörse.
	*/

	class Market extends SQLite
	{
		protected $tables = array (
			"market" => array (
				"id PRIMARY KEY",
				"user",
				"planet",
				"offered_resource",
				"amount",
				"requested_resource",
				"min_price",
				"expiration",
				"date",
				"finish"
			),
			"market_rate" => array (
				"offer",
				"request",
				"price",
				"date"
			)
		);

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
			$this->query("SELECT DISTINCT ROUND(min_price, 2) FROM market WHERE offered_resource = ".$this->escape($offer)." AND requested_resource = ".$this->escape($request).";");
			while($r = $this->nextResult())
				$check_prices[] = 0+array_shift($r);
			$this->query("SELECT DISTINCT ROUND(1/min_price, 2) FROM market WHERE offered_resource = ".$this->escape($request)." AND requested_resource = ".$this->escape($offer).";");
			while($r = $this->nextResult())
				$check_prices[] = 0+array_shift($r);
			$check_prices = array_unique($check_prices);
			rsort($check_prices, SORT_NUMERIC);

			$diff = null;
			$best_price = null;
			foreach($check_prices as $price)
			{
				$sum1 = 0+$this->singleField("SELECT SUM(amount) FROM market WHERE offered_resource = ".$this->escape($offer)." AND requested_resource = ".$this->escape($request)." AND min_price >= ".$this->escape($price).";");
				$sum2 = 0;
				if($price > 0)
					$sum2 += $this->singleField("SELECT SUM(amount) FROM market WHERE offered_resource = ".$this->escape($request)." AND requested_resource = ".$this->escape($offer)." AND min_price >= ".$this->escape(round(1/$price, 2)).";");
				$this_diff = abs($sum1-$sum2);
				if($diff === null || $this_diff < $diff)
				{
					$diff = $this_diff;
					$best_price = $price;
				}
				elseif($sum2 < $sum1)
					break;
			}

			$this->query("INSERT INTO market_rate ( offer, request, price, date ) VALUES ( ".$this->escape($offer).", ".$this->escape($request).", ".$this->escape($best_price).", ".$this->escape(microtime(true))." );");

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
			$q = $this->arrayQuery("SELECT price FROM market_rate WHERE offer = ".$this->escape($offer)." AND request = ".$this->escape($request)." ORDER BY date desc LIMIT 1;");
			if(!isset($q[0])) return 0;
			return 0+$q[0]['price'];
		}

		/**
		 * Gibt eine neue ID für einen Handelsauftrag zurück.
		 * @return int
		*/

		protected function getNewId()
		{
			return 1+$this->singleField("SELECT id FROM market ORDER BY id DESC LIMIT 1;");
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
			$this->query("INSERT INTO market ( id, user, planet, offered_resource, amount, requested_resource, min_price, expiration, date, finish ) VALUES ( ".$this->escape($id).", ".$this->escape($user).", ".$this->escape($planet).", ".$this->escape(0+$offered_resource).", ".$this->escape(0+$amount).", ".$this->escape(0+$requested_resource).", ".$this->escape(0+$min_price).", ".$this->escape(0+$expiration).", ".$this->escape(time()).", -1);");
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
		 * Verändert alle Planetennummern $old des Benutzers $username zu $new.
		 * Sollte ausgeführt werden, wenn sich die Planetennummern des Benutzers
		 * ändern.
		 * @param string $username
		 * @param int $old_number
		 * @param int $new_number
		 * @return void
		*/

		function renamePlanet($username, $old_number, $new_number)
		{
			$this->query("UPDATE market SET planet = ".$this->quote($new_number)." WHERE user = ".$this->quote($username)." AND planet = ".$this->quote($old_number).";");
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
			$this->query("SELECT * FROM market WHERE offered_resource = ".$this->escape($offered_resource)." AND finish = -1 AND expiration < ".$this->escape(time())." AND min_price <= ".$this->escape($this->getRate($offered_resource, $requested_resource)).";");
			while($r = $this->nextResult())
			{
				$sum = $this->singleField("SELECT sum(amount) FROM market WHERE offered_resource = ".$this->escape($r['offered_resource'])." AND date >= ".$this->escape($r['date'])." AND user != ".$this->escape($r['user']).";");
				$count = $this->singleField("SELECT count(DISTINCT user) FROM market WHERE offered_resource = ".$this->escape($r['offered_resource'])." AND user != ".$this->escape($r['user']).";");
				if($sum >= global_setting('MARKET_MIN_AMOUNT')*$r['amount'] && $count > global_setting('MARKET_MIN_USERS'))
				{
					# Auftrag wird ausgefuehrt
					$this->acceptOrder($r['id'], $transaction);
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
			$q = "UPDATE market SET finish = ".$this->escape(time()+global_setting('MARKET_DELAY'))." WHERE id = ".$this->escape($id).";";
			if($transaction)
				$this->transactionQuery($q);
			else
				$this->query($q);
		}

		/**
		 * Gibt alle Handelsaufträge in einem Array von assoziativen Arrays zurück,
		 * die der Benutzer am Laufen hat.
		 * Die assoziativen Arrays haben folgendes Format:
		 * • id => Die Auftrags-ID
		 * • user => Der Benutzer
		 * • planet => Die Planetennummer
		 * • offered_resource => Der angebotene Rohstoff (0: Carbon; 1: Aluminium; ...)
		 * • amount => Die angebotene Menge
		 * • requested_resource => Der nachgefragte Rohstoff
		 * • min_price => Der Mindestkurs zur Durchführung
		 * • expiration => Der Zeitpunkt, zu dem das Angebot abläuft
		 * • finish => Der Zeitpunkt, zu dem der Handel durchgeführt wird, sofern dieser feststeht
		 * @param string $user
		 * @return array
		*/

		public function getOrders($user)
		{
			$this->cleanUp($user);
			return $this->arrayQuery("SELECT id, user, planet, offered_resource, amount, requested_resource, min_price, expiration, finish FROM market WHERE user = ".$this->escape($user).";");
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
			return $this->arrayQuery("SELECT id, finish FROM market WHERE finish <= ".$this->escape($max_time)." AND finish != -1 ORDER BY finish ASC;");
		}

		/**
		 * Führt den Auftrag mit der ID $id durch.
		 * @param int $id
		 * @return void
		 * @todo Benachrichtung des Benutzers
		*/

		public function finishOrder($id)
		{
			$r = $this->arrayQuery("SELECT * FROM market WHERE id = ".$this->escape($id).";");
			if(!isset($r[0])) return false;
			$u = Classes::User($r[0]['user']);
			$this->query("DELETE FROM market WHERE id = ".$this->escape($id).";");
			$u->setActivePlanet($r[0]['planet']);
			$u->addRess(array($r[0]['requested_resource']-1 => max($r[0]['amount']*$r[0]['min_price'], $r[0]['amount']*$this->getRate($r[0]['offered_resource'], $r[0]['requested_resource']))));
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
			$r = $this->arrayQuery("SELECT id, planet, amount, offered_resource, requested_resource FROM market WHERE id = ".$this->escape($id)." AND user = ".$this->escape($u->getName())." AND finish = -1;");
			if(isset($r[0]))
			{
				$this->query("DELETE FROM market WHERE id = ".$this->escape($r[0]['id']).";");
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r[0]['offered_resource']-1] = $r[0]['amount'];
				$planet = $u->getActivePlanet();
				$u->setActivePlanet($r[0]['planet']);
				$u->addRess($ress);
				$u->setActivePlanet($planet);

				$this->recalcRate($r[0]['offered_resource'], $r[0]['requested_resource']);
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
			$this->query("SELECT id, planet, amount, offered_resource, requested_resource FROM market WHERE expiration < ".$this->escape(time())." AND finish = -1 AND user = ".$this->escape($u->getName()).";");
			while($r = $this->nextResult())
			{
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r['offered_resource']-1] = $r['amount'];
				$u->setActivePlanet($r['planet']);
				$u->addRess($ress);
				$this->transactionQuery("DELETE FROM market WHERE id = ".$this->escape($r['id']).";");
				$recalc[] = array($r['offered_resource'], $r['requested_resource']);
			}
			$this->endTransaction();
			$u->setActivePlanet($planet);

			foreach(array_unique($recalc) as $r)
				$this->recalcRate($r[0], $r[1], true);
			$this->endTransaction();
		}
	}
