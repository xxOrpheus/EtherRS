<?php
namespace Server\Network;

require_once(ROOT_DIR . '/data/config.Server.php');

class SQL extends \Server\Server {
	protected $conn;

	public function __construct() {
		try {
			$this->conn = new \PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DB, SQL_USER, SQL_PASS);
			$this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			$this->log($e->getMessage(), true, 2);
		}
		$this->log('SQL connection initialized');
	}
	
	/**
	 *
	 * Get the number of rows from a certain table based on column and value.
	 *
	 * @param  string $table   The table(s) to select from -- Be careful, we can't properly parameterize these.
	 * @param  array  $columns The column(s) to select with -- We can't parameterize these either!
	 * @param  array  $values  The value(s) we need.
	 *
	 * @return int
	 *
	 */
	public function getCount($table, array $columns, array $values) {
		try {
			if($this->conn == false) {
				throw new \Exception(__METHOD__ . ': No SQL connection');
			}
			if(count($columns) != count($values)) {
				throw new \Exception(__METHOD__ . ': Unmatching column and value arrays');
			}
			$sql = '';
			foreach($columns as $column) {
				$sql .= '`' . $column . '` = ? AND ';
			}
			$sql = substr($sql, 0, -5);
			$sql = 'SELECT COUNT(*) as `rows` FROM ' . $table . ' WHERE ' . $sql;
			$query = $this->conn->prepare($sql);
			$query->execute($values);
			$rs = $query->fetch(\PDO::FETCH_ASSOC);
			
			return $rs['rows'];
		} catch(\PDOException $e) {
			$this->log($e->getMessage());
			return -1;
		}
	}
	
	/**
	 *
	 * Insert data to the database
	 *
	 * @param string $table   The table to insert into.
	 * @param array  $columns The column values
	 * @param array  $values  The values
	 *
	 * @return int
	 *
	 */
	public function insert($table, array $columns, array $values) {
		if($this->conn == false) {
			throw new \Exception(__METHOD__ . ': No SQL connection');
		}

		if(count($columns) != count($values)) {
			throw new \Exception(__METHOD__ . ': Unmatching column and value arrays');
		}

		try {
			$sql = '';
			foreach($columns as $column) {
				$sql .= '`'.$column.'`, ';
			}
			$sql = substr($sql, 0, -2);
			$sql = 'INSERT INTO ' . $table . ' ('.$sql.') VALUES(';
			$columnCount = count($columns);
			for($i = 0; $i < $columnCount; $i++) {
				$sql .= '?, ';
			}
			$sql = substr($sql, 0, -2);
			$sql .= ')';
			//var_dump($sql);
			$query = $this->conn->prepare($sql);
			$exec = $query->execute($values);
			return $exec == true ? 1 : -1;
		} catch(\PDOException $e) {
			$this->log($e->getMessage());
			return -1;
		}
	}
	
	/**
	 * 
	 * Select data from a table
	 * $table and $row cannot be parameterized, so do not allow user input into this function.
	 * 
	 * @param $table The table name
	 * @param $row   The row
	 * @param $value The value
	 * 
	 * @return mixed
	 * 
	 */
	public function select($table, $row, $value) {
	    try {
	        if(empty($table) || empty($row) || empty($value)) {
	            $this->log(__METHOD__ . ': You must fill in all fields.');
	            return false;
	        }
		    $query = $this->conn->prepare('SELECT * FROM `' . $table . '` WHERE `' . $row . '` = ?'); // ??? is this bad. this seems bad.
		    $query->execute($value);
		    if(!$query) {
			    return false;
	    	}
	    	return $query->fetchAll(\PDO::FETCH_ASSOC);
	    } catch(\PDOException $e) {
	        $this->log('Fatal SQL error: ' . $e->getTraceAsString());
	    }
	}
	/**
	 *
	 * Get the PDO instance
	 *
	 * @return PDO object
	 *
	 */
	 public function getConn() {
	 	return $this->conn;
	 }
}
?>
