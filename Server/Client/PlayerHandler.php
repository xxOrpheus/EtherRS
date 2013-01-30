<?php
namespace Server\Client;

/**
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>, Mitchell Murphy <mitchell@fl3x.co>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/mitchellm/EtherRS/
 */

class PlayerHandler extends \Server\Server {
	protected $players = array();
	protected $active_sessions = 0;

	public function __construct() {}

	/**
	 *
	 * In the future, this will save all online players.
	 *
	 */
	public function save() {
		return false;
	}

	/**
	 * 
	 * Add a client to the handler
	 * 
	 */
	public function add($socket, \Server\Server $server, \Server\Network\SQL $sql) {
		$player = new Player($socket, $this->active_sessions, $server, $sql, $this);
		$server->handleModules('__onConnect', $socket, $this);
	}

	/**
	 *
	 * Remove all null players
	 * 
	 */
	protected function cycleEvent() {
		$playerCount = count($this->players);
		$this->players = array_values($this->players);

		for($x = 0; $x < $playerCount; $x++) {
			if(!isset($this->players[$x])) {
				continue;
			}

			if(time() - $this->players[$x]->getLastPacket() > 120) {
				socket_close($this->players[$x]->getConnection());
				$this->log('Closing ' . $this->players[$x]->getUsername() .'\'s connection');
				$this->active_sessions--;
				unset($this->players[$x]);
				continue;
			}

			@socket_send($this->players[$x]->connection, " ", 1, MSG_OOB);
			if(socket_last_error($this->players[$x]->connection) != 0) {
				$this->active_sessions--;
				$this->players[$x] = null;
				unset($this->players[$x]);
			}
		}
	}

	public function modActiveSessions($number) {
		$this->active_sessions += intval($number);
	}

	public function addPlayer(Player $player) {
		$this->players[] = $player;
	}

	public function getPlayers() {
		return $this->players;
	}
}
?>
