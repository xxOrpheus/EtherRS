<?php
namespace Server;

class Misc {
	public static function nameToLong($s) {
		$l = 0;
		$strlen = strlen($s);

		for ($i = 0; $i < $strlen && $i < 12; $i++) {
			$c = $s[$i];
			$l *= 37;
			if($c >= 'A' && $c <= 'Z')
				$l += (1 + $c) - 65;
			else if ($c >= 'a' && $c <= 'z')
				$l += (1 + $c) - 97;
			else if ($c >= '0' && $c <= '9')
				$l += (27 + $c) - 48;
		}
		while ($l % 37 == 0 && $l != 0)
			$l /= 37;
		return $l;
	}
}
?>