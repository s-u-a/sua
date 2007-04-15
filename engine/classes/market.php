<?php
	class Market extends SQLite
	{
		protected $tables = array (
			"market" => array("id PRIMARY KEY", "user", "planet", "offered_resource", "amount", "requested_resource", "min_price", "expiration", "date", "finish"),
			"market_rate" => array("offer", "request", "price", "date")
		);

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
			$this->recalcRate($offered_resource, $requested_resource, true);

			for($i=1; $i<=5; $i++)
			{
				if($i == $requested_resource) continue;
				$this->checkAccepted($offered_resource, $i, true);
			}

			$this->endTransaction();

			return $id;
		}

		public function checkAccepted($offered_resource, $requested_resource, $transaction=false)
		{
			# Bestehende Angebote einzuloesen versuchen
			$this->query("SELECT * FROM market WHERE offered_resource = ".$this->escape($offered_resource)." AND finish = -1 AND expiration < ".$this->escape(time())." AND min_price <= ".$this->escape($this->getRate($offered_resource, $requested_resource)).";");
			while($r = $this->nextResult())
			{
				$sum = $this->singleQuery("SELECT sum(amount),count(DISTINCT user) FROM market WHERE offered_resource = ".$this->escape($r['offered_resource'])." AND date >= ".$this->escape($r['date'])." AND user != ".$this->escape($r['user']).";");
				$count = $this->singleQuery("SELECT count(DISTINCT user) FROM market WHERE offered_resource = ".$this->escape($r['offered_resource'])." AND user != ".$this->escape($r['user']).";");
				if($sum >= global_setting('MARKET_MIN_AMOUNT')*$r['amount'] && $count > global_setting('MARKET_MIN_USERS'))
				{
					# Auftrag wird ausgefuehrt
					$this->acceptOrder($r['id'], $transaction);
				}
			}
		}

		public function acceptOrder($id, $transaction=false)
		{
			# TODO: Benachrichtigung
			$q = "UPDATE market SET finish = ".$this->escape(time()+7200)." WHERE id = ".$this->escape($id).";";
			if($transaction)
				$this->transactionQuery($q);
			else
				$this->query($q);
		}

		public function getOrders($user)
		{
			$this->cleanUp($user);
			return $this->arrayQuery("SELECT id, user, planet, offered_resource, amount, requested_resource, min_price, expiration, finish FROM market WHERE user = ".$this->escape($user).";");
		}

		public function getFinishingOrdersList($max_time)
		{
			return $this->arrayQuery("SELECT id, finish FROM market WHERE finish <= ".$this->escape($max_time)." AND finish != -1 ORDER BY finish ASC;");
		}

		public function finishOrder($id)
		{
			# TODO: Benachrichtigung

			$r = $this->arrayQuery("SELECT * FROM market WHERE id = ".$this->escape($id).";");
			if(!isset($r[0])) return false;
			$u = Classes::User($r[0]['user']);
			if(!$u->getStatus()) return false;
			$this->query("DELETE FROM market WHERE id = ".$this->escape($id).";");
			$u->setActivePlanet($r[0]['planet']);
			$u->addRess(array($r[0]['requested_resource']-1 => max($r[0]['amount']*$r[0]['min_price'], $r[0]['amount']*$this->getRate($r[0]['offered_resource'], $r[0]['requested_resource']))));
			return true;
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

				$this->recalcRate($r[0]['offered_resource'], $r[0]['requested_resource']);
			}
		}

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
?>