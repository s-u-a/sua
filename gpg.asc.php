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
	require("engine.php");

	$public_key = GPG::init(true);
	if($public_key)
	{
		header("Content-type: application/pgp");
		header("Content-disposition: attachment;filename=suabot.asc");
		print($public_key);
	}
	else
	{
		header($_SERVER["SERVER_PROTOCOL"]." 501 Not Implemented");
		header("Content-type: text/plain");

		echo "GPG is not activated.\n";
	}
?>