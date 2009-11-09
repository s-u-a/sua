<?php
	if(isset($_GET["session"]))
		session_id($_GET["session"]);
	session_start();

	if(isset($_SERVER["REMOTE_ADDR"]))
		$_SESSION["ipv4"] = $_SERVER["REMOTE_ADDR"];

	header("Content-type: image/png");
	print base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABlBMVEUAAAD///+l2Z/dAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=");