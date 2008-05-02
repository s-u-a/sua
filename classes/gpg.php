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

	class GPG
	{
		/**
		* Oeffnet den GPG-Schluessel entsprechend der Konfiguration und liefert bei Erfolg ein gnupg-Object zurueck.
		* @return null, wenn keine Konfiguration vorliegt oder der Schluessel nicht geoeffnet werden kann.
		* @return (gnupg)
		*/

		static function init($return_public_key=false)
		{
			static $gpg,$config;

			if(!isset($config))
			{
				if(!is_file(global_setting("DB_GPG"))) return null;
				$config = parse_ini_file(global_setting("DB_GPG"));
			}
			if(!$config || !isset($config["fingerprint"]))
				return null;

			if(!isset($gpg))
			{
				if(!class_exists("gnupg"))
					return null;
				$gpg = new gnupg();
				$gpg->seterrormode(gnupg::ERROR_WARNING);
				$gpg->setsignmode(gnupg::SIG_MODE_CLEAR);
				if(isset($config["gpghome"]))
					putenv("GNUPGHOME=".$config["gpghome"]);
				if(!$gpg->addsignkey($config["fingerprint"]))
					return null;
				$gpg->adddecryptkey($config["fingerprint"], "");
			}
			if($return_public_key)
				return $gpg->export($config["fingerprint"]);
			else
				return $gpg;
		}

		/**
		* Signiert den gegebenen Text wenn moeglich per GPG.
		*/

		static function sign($text)
		{
			$gpg = self::init();
			if(!$gpg)
				return $text;
			$return = $gpg->sign($text);
			if($return === false)
				return $text;
			return $return;
		}

		/**
		* Signiert den Text, gibt aber nur die Signatur, ohne Header, zurück.
		* @return false Bei Fehlschlag
		*/

		static function smallsign($text)
		{
			$gpg = self::init();
			if(!$gpg) return false;

			$signed = $gpg->sign($text);
			if(!preg_match("/(^|\n)-----BEGIN PGP SIGNATURE-----\r?\n.*?\r?\n\r?\n(.*?)\r?\n-----END PGP SIGNATURE-----(\r?\n|\$)/s", $signed, $m))
				return false;
			return $m[2];
		}

		/**
		* Signiert und verschluesselt den gegebenen Text wenn moeglich per GPG.
		*/

		static function encrypt($text, $fingerprint)
		{
			$gpg = self::init();
			if(!$gpg)
				return $text;
			$gpg->addencryptkey($fingerprint);
			$encrypted = $gpg->encryptsign($text);
			$gpg->clearencryptkeys();
			if($encrypted === false)
				return $text;
			return $encrypted;
		}

		/**
		* Verschlüsselt und signiert $text für $fingerprint, gibt aber nur den verschlüsselten Text ohne Header zurück.
		* @return false Bei Fehlschlag.
		*/

		static function smallencrypt($text, $fingerprint)
		{
			$signed = self::encrypt($text, $fingerprint);
			if(!preg_match("/(^|\n)-----BEGIN PGP MESSAGE-----\r?\n.*?\r?\n\r?\n(.*?)\r?\n-----END PGP MESSAGE-----(\r?\n|\$)/s", $signed, $m))
				return false;
			return $m[2];
		}

		/**
		* Entschlüsselt den Text.
		*/

		static function decrypt($text)
		{
			$gpg = self::init();
			return $gpg->decrypt($text);
		}
	}