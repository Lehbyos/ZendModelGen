<?php
include_once 'Table.php';
include_once 'Column.php';

abstract class Analizer
{
	protected $dbConn;
	protected $user;
	protected $password;
	protected $host;
	protected $port;
	protected $schema;
	
	public abstract function getTable($name);
	public abstract function getTables();
	public abstract function getTableColumns($table);
}
?>