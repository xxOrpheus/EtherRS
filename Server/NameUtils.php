<?php
namespace Server;


/**
 * Name utility class.
 *
 * @author Graham Edgecombe
 * @author David Harris <lolidunno@live.co.uk>
 *
 */
class NameUtils {

    /**
     * Checks if a name is valid.
     *
     * @param s The name.
     * @return <code>true</code> if so, <code>false</code> if not.
     */
    public function isValidName($s) {
        return preg_match('/[^a-zA-Z0-9]/', $s) == false;
    }

    /**
     * Converts a name to a long.
     *
     * @param s The name.
     * @return The long.
     */
	public function nameToLong($s) {
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

    /**
     * Converts a long to a name.
     *
     * @param l The long.
     * @return The name.
     */
    /*public function longToName($l) {
        $i = 0;
        $ac = array();
        while ($l != (long)0) {
            $l1 = $l;
            $l /= (long)37;
            $ac[11 - $i++] = Constants.VALID_CHARS[(int) ($l1 - $l * (long)37)];
        }
        return new String(ac, 12 - i, i);
    }*/

    /**
     * Formats a name for use in the protocol.
     *
     * @param s The name.
     * @return The formatted name.
     */
    public function formatNameForProtocol($s) {
        return preg_replace('/\s/', '_', $s);
    }

    /**
     * Formats a name for display.
     *
     * @param s The name.
     * @return The formatted name.
     */
    public function formatName($s) {
        return $this->fixName($this->formatNameForProtocol($s));
    }

    /**
     * Method that fixes capitalization in a name.
     *
     * @param s The name.
     * @return The formatted name.
     */
    protected function fixName($s) {
        if (strlen($s) > 0) {
            $ac = str_split($s);
			$aclength = count($ac);
            for ($j = 0; $j < $aclength; $j++) {
                if ($ac[$j] == '_') {
                    $ac[$j] = ' ';
                    if (($j + 1 < $aclength) && ($ac[$j + 1] >= 'a')
                            && ($ac[$j + 1] <= 'z')) {
                        $ac[$j + 1] = (($ac[$j + 1] + 65) - 97);
                    }
                }
            }

            if (($ac[0] >= 'a') && ($ac[0] <= 'z')) {
                $ac[0] = (($ac[0] + 65) - 97);
            }
            return $ac;
        } else {
            return $s;
        }
    }
}
