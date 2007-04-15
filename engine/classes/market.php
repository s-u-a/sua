<?php
	class Market extends SQLite
	{
		protected $tables = array (
			"market" => array("id PRIMARY KEY", "user", "planet", "offered_resource", "amount", "requested_resource", "min_price", "expiration", "date", "finish"),
			"market_rate" => array("offer", "request", "price", "date")
		);

		public function recalcRate($offer, $request)
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
		}

		public function getRate($offer, $request)
		{
			$q = $this->arrayQuery("SELECT price FROM market_rate WHERE offer = ".$this->escape($offer)." AND request = ".$this->escape($request)." ORDER BY date desc LIMIT 1;");
			if(!isset($q[0])) return 0;
			return $q[0]['price'];
		}

		protected function getNewId()
		{
			return 1+$this->singleField("SELECT id FROM market ORDER BY id desc LIMIT 1;");
		}

		public function addOrder($user, $planet, $offered_resource, $amount, $requested_resource, $min_price, $expiration)
		{
			$id = $this->getNewId();
			$this->query("INSERT INTO market ( id, user, planet, offered_resource, amount, requested_resource, min_price, expiration, date, finish ) VALUES ( ".$this->escape($id).", ".$this->escape($user).", ".$this->escape($planet).", ".$this->escape(0+$offered_resource).", ".$this->escape(0+$amount).", ".$this->escape(0+$requested_resource).", ".$this->escape(0+$min_price).", ".$this->escape(0+$expiration).", ".$this->escape(time()).", -1);");
			$this->recalcRate($offered_resource, $requested_resource);

			# Bestehende Angebote einzuloesen versuchen
			$this->query("SELECT * FROM market WHERE offered_resource = ".$this->escape($offered_resource)." AND finish = -1;");
			while($r = $this->nextResult())
			{
				$sum = $this->singleField("SELECT sum(amount) FROM market WHERE offered_resource = ".$this->escape($r['offered_resource'])." AND date >= ".$this->escape($r['date'])." AND user != ".$this->escape($r['user']).";");
				if($sum >= 10*$r['amount'])
				{
					# Auftrag wird ausgefuehrt
					$this->transactionQuery("UPDATE market SET finish = ".$this->escape(time()+7200)." WHERE id = ".$this->escape($r['id']).";");
				}
			}
			$this->endTransaction();

			return $id;
		}

		public function getOrders($user)
		{
			$this->cleanUp($user);
			return $this->arrayQuery("SELECT id, user, planet, offered_resource, amount, requested_resource, min_price, expiration, finish FROM market WHERE user = ".$this->escape($user).";");
		}

		public function cancelOrder($id, $user)
		{
			$u = Classes::User($user);
			$r = $this->arrayQuery("SELECT id, planet, amount, offered_resource FROM market WHERE id = ".$this->escape($id)." AND user = ".$this->escape($u->getName())." AND finish = -1;");
			if(isset($r[0]))
			{
				$this->query("DELETE FROM market WHERE id = ".$this->escape($r[0]['id']).";");
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r[0]['offered_resource']-1] = $r[0]['amount'];
				$planet = $u->getActivePlanet();
				$u->setActivePlanet($r[0]['planet']);
				$u->addRess($ress);
				$u->setActivePlanet($planet);
			}
		}

		public function cleanUp($user)
		{
			$u = Classes::User($user);
			$planet = $u->getActivePlanet();
			$this->query("SELECT id, planet, amount, offered_resource FROM market WHERE expiration < ".$this->escape(time())." AND finish = -1 AND user = ".$this->escape($u->getName()).";");
			while($r = $this->nextResult())
			{
				$ress = array(0, 0, 0, 0, 0);
				$ress[$r['offered_resource']-1] = $r['amount'];
				$u->setActivePlanet($r['planet']);
				$u->addRess($ress);
				$this->transactionQuery("DELETE FROM market WHERE id = ".$this->escape($r['id']).";");
			}
			$this->endTransaction();
			$u->setActivePlanet($planet);
		}
	}
?>