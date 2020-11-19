<?php

	// Identifies Sqli attempts. Returns true if input is safe.
	function sqlIClear($input) {
		if ((strpos($input, ";") !== false) || (strpos($input, "DROP TABLES") !== false) 
			|| (strpos($input, "drop tables") !== false) || (strpos($input, "droptables") !== false) 
			|| (strpos($input, "DROPTABLES") !== false)) {
			return false;
		} else {
			return true;
		}
	}

	function getUserIP() {
		$ip = "ip: ";
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $ip . $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}


?>