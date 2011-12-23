<?php
include_once 'classes/common/Analizer.php';
include_once 'Table.php';
include_once 'Column.php';

//include_once 'Table.php';
//include_once 'Column.php';

$fSqlTodos = null;

class MySqlAnalizer extends Analizer
{
	private $db;
	private $dbName;
	
	public function __construct($server, $user, $password, $dbName)
	{
		$this->db = mysql_connect($server, $user, $password);
		if ($this->db === false)
			throw new Exception('No se puede conectar');
		if (!mysql_select_db($dbName, $this->db))
			throw new Exception('No se puede usar base de datos ' . $dbName);
		$this->dbName = $dbName;
	}
	
	public function getDbName(){
		return $this->dbName;
	}
	
	public function getTable($name)
	{
		$sql = 'select table_name, engine, version, auto_increment, table_comment ';
		$sql .= 'from information_schema.tables where table_schema = \''.$this->dbName.'\' ';
		$sql .= 'and table_name = \''.$name.'\'';
		$rs = mysql_query($sql, $this->db);
		echo 'Reading table ' . $name . '... ';
		if ($rs !== false)
		{
			$row = mysql_fetch_assoc($rs);
			mysql_free_result($rs);
			if ($row !== false)
			{
				$resp = new MySqlTable(
					$this,
					$row['table_name'],
					$row['engine'],
					$row['version'],
					$row['auto_increment'],
					$row['table_comment']
				);
				echo "OK\n";
				return $resp;
			}
		}
		echo "Table not found\n";
		return null;
	}
	
	public function getTables()
	{
		$sql = 'select table_name, engine, version, auto_increment, table_comment ';
		$sql .= 'from information_schema.tables where table_schema = \''.$this->dbName.'\'';
		$rs = mysql_query($sql, $this->db);
		$resp = array();
		if ($rs !== false)
		{
			while($fila = mysql_fetch_assoc($rs))
			{
				echo 'Reading table ' . $fila['table_name'] . '... ';
				$resp[] = new MySqlTable(
					$this,
					$fila['table_name'],
					$fila['engine'],
					$fila['version'],
					$fila['auto_increment'],
					$fila['table_comment']
				);
				echo "OK\n";
			}
			mysql_free_result($rs);
		}
		else
			echo "Error: no tables where found!\n";
		return $resp;
	}
	
	public function getTableColumns($table)
	{
		$sql  = 'select cols.table_schema "schema",	cols.table_name "table", cols.column_name "column",';
		$sql .= 'if(cols.is_nullable = \'YES\', 1, 0) "nullable", cols.data_type "data_type",';
		$sql .= 'cols.character_maximum_length "char_max_length", cols.character_octet_length "char_octet_length",';
		$sql .= 'cols.numeric_precision "precision",cols.numeric_scale "scale",cols.column_type "type",';
		$sql .= 'cols.column_comment "comments",cols.extra,(select count(1) from information_schema.key_column_usage ';
		$sql .= 'where table_schema = cols.table_schema and table_name = cols.table_name ';
		$sql .= 'and column_name = cols.column_name and constraint_name = \'PRIMARY\') "PK", ';
		$sql .= 'if (o.referenced_table_name is not null, 1, 0) "FK" from information_schema.columns cols ';
		$sql .= 'left join (select constraint_name, table_schema, table_name, column_name, referenced_table_schema, ';
		$sql .= 'referenced_table_name, referenced_column_name from information_schema.key_column_usage where ';
		$sql .= 'referenced_table_name is not null) o on cols.table_schema = o.table_schema ';
		$sql .= 'and cols.table_name = o.table_name and cols.column_name = o.column_name where ';
		$sql .= 'cols.table_schema = \''.$this->dbName.'\' and cols.table_name = \''.$table->getName().'\'';

		$rs = mysql_query($sql, $this->db);
		$resp = array();
		if ($rs !== false)
		{
			while($fila = mysql_fetch_assoc($rs))
			{
				$nuevo = new MySqlColumn();
				$nuevo->setName($fila['column']);
				$nuevo->setNullable($fila['nullable'] == 1);
				$nuevo->setCharacterLength($fila['char_max_length']);
				$nuevo->setComment($fila['comments']);
				$nuevo->setDataType($fila['data_type']);
				$nuevo->setExtra($fila['extra']);
				$nuevo->setOctetLength($fila['char_octet_length']);
				$nuevo->setPrecision($fila['precision']);
				$nuevo->setScale($fila['scale']);
				$nuevo->setType($fila['type']);
				if ($fila['PK'] == 1)
					$nuevo->setPK();
				$nuevo->setAutoIncrement((strpos($fila['extra'], 'auto_increment') !== false));
				//$nuevo->validate();
				$resp[] = $nuevo;
			}
			mysql_free_result($rs);
		}
		return $resp;
	}
	
	public function dropTableProcedures(Table $t){
		$sql = 'DROP PROCEDURE IF EXISTS ' . $this->dbName . '.' . $t->getSelectProcedureName() . ';';
		
		if (!mysql_query($sql, $this->db)){
			echo "Error:" . mysql_error($this->db) . "\n";
			return false;
		}
			
		$sql = 'DROP PROCEDURE IF EXISTS ' . $this->dbName . '.' . $t->getInsertProcedureName() . ';';
		if (!mysql_query($sql, $this->db)){
			echo 'Error: ' . mysql_error($this->db) . "\n";
			return false;
		}
		
		$sql = 'DROP PROCEDURE IF EXISTS ' . $this->dbName . '.' . $t->getUpdateProcedureName() . ';';
		if (!mysql_query($sql, $this->db)){
			echo 'Error: ' . mysql_error($this->db) . "\n";
			return false;
		}
			
		return true;
	}
	
	public function createTableProcedures(Table $t){
		if (!mysql_query($t->getSelectProcedure(), $this->db)){
			echo 'Error: ' . mysql_error($this->db) . "\n";
			return false;
		}
		
		if (!mysql_query($t->getInsertProcedure(), $this->db)){
			echo 'Error: ' . mysql_error($this->db) . "\n";
			return false;
		}
		
		if (!mysql_query($t->getUpdateProcedure(), $this->db)){
			echo 'Error: ' . mysql_error($this->db) . "\n";
			return false;
		}
		return true;
	}
}
?>
