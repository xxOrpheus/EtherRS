<?php
namespace Server;
/**
 * A RuneScape private server made in PHP
 * Contributors: Mitchell Murphy <mitchell@fl3x.co>
 * 
 * PHP 5
 * 
 * 
 * @category RSPS
 * @package EtherRS
 * @author David Harris <lolidunno@live.co.uk>
 * @copyright 2013 EtherRS
 * @version GIT: $Id:$
 * @link https://github.com/xxOrpheus/EtherRS/
 */

chdir(__DIR__);
define('ROOT_DIR', __DIR__);

require 'data/config.Server.php';
require 'Client/PlayerHandler.php';
require 'Client/Player.php';
require 'Network/SQL.php';
require 'Network/Socket.php';
require 'Network/Stream.php';
require 'IO.php';
require 'Misc.php';

class Server {
	protected $server, $bytes, $raw;
	protected $useSQL = true, $sql, $IO;

	protected $playerHandler, $player, $socket;

	private $modules = array();

	public function __construct(array $args = null) {
		if(!extension_loaded('sockets')) {
			throw new \Exception('You need sockets enabled to use this!');
		}
		$this->log('EtherRS running...');
		$this->log('Attempting to bind and listen on port ' . SERVER_PORT . '...');
		$this->server = @socket_create(AF_INET, SOCK_STREAM, 0);
		$bind = @socket_bind($this->server, 0, SERVER_PORT);
		$listen = @socket_listen($this->server);
		socket_set_nonblock($this->server);
		if(!$this->server || !$bind || !$listen) {
			throw new \Exception('Could not bind to ' . SERVER_PORT);
		}
		
		$this->playerHandler = new Client\PlayerHandler();
		$this->sql = new Network\SQL();
		$this->IO = new \Server\IO();
		$this->socket = new Network\Socket();

		$this->loadModules();
		$this->start();
		$this->handleModules('__onLoad', $this);
	}

	/**
	 *
	 * Load all modules
	 * 
	 * @param string $dir The directory to load modules from
	 *
	 */
	private function loadModules($dir = null) {
		if($dir === null) {
			$dir = __DIR__ . '/Modules';
		}

		$modules = glob($dir . '/mod.*.php');

		if(count($modules) <= 0) {
			return false;
		}

		foreach($modules as $module) {
			$module = basename($module);
			$module = substr($module, 4);
			$module = substr($module, 0, -4);
			require_once($dir . '/mod.' . $module . '.php');
			$class = '\Server\Modules\\' . $module; 
			if(!class_exists($class)) {
				throw new \Exception('Module ' . $class . ' failed to load -- Does the class name match the file name?');
			}
			$this->modules[$module] = new $class($this);
		}

		$this->log('Finished loading all server modules, the server will continue running!');
	}

	/**
	 *
	 * Handle all modules
	 *
	 * @param string $method_name Method name
	 * @version 1.2
	 *
	 */
	public function handleModules($handler) {
		$args = func_get_args();
		$modules = $this->getModules();

		foreach($modules as $module) {
			if(method_exists($module, $handler)) {
				call_user_func_array(array($module, $handler), $args);
			}
		}
	}


	/**
	 *
	 * Start the server
	 * 
	 */
	private function start() {
		$this->handleModules('__beforeStart');
		$cycleTimed = 0;
		while($this->server) {
			$cycleStart = time();

			$this->cycle();
			$deltaTime = time() - $cycleStart;

			usleep((CYCLE_TIME * 1000) - $deltaTime);
		}
	}

	/**
	 * 
	 * The sequence of a single server cycle
	 * 
	 */
	private function cycle() {
	 	//Listen for and process a new client, runs 10 times per cycle. Limited to prevent abuse 
		for($i = 0; $i < 10; $i++) {
			$client = @socket_accept($this->server);
			if(!($client == false)) {
				$this->playerHandler->add($client, $this, $this->sql);
			}
			$this->playerHandler->cycleEvent();
		}
	}

	/**
	*
	* @return Client\PlayerHandler Instance
	*
	*/
	public function getPlayerHandler() {
		return $this->playerHandler;
	}

	/**
	 *
	 * @return array
	 *
	 */
	public function getModules() {
		return $this->modules;
	}

	/**
	 *
	 * @return Server\Network\Socket
	 *
	 */
	public function getSocket() {
		return $this->socket;
	}

	/**
	 *
	 * Write to STDOUT and append to log file
	 *
	 */
	public function log($msg, $log = true, $prefix = '[SERVER] ') {
		$msg = $prefix . $msg . PHP_EOL;
		echo $msg;
		if($log) {
			file_put_contents('log/log-' . date('d-m-Y') .'.txt', $msg, FILE_APPEND);
		}
	}

	/**
	 *
	 * Stop the server.
	 *
	 */
	public function stop() {
		$this->handleModules('__onStop', $this);
		$this->log('Server stopping...');
		$this->playerHandler->save();

		unset($this->playerHandler);
		unset($this->sql);
		unset($this->socket);
		socket_close($this->server);

		exit('Server stopped.' . PHP_EOL);
	}
}
?>
