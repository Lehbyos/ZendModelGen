<?php
include_once 'classes/common/Column.php';
class MySqlColumn extends Column
{
	public    $key;
	protected $extra;
	protected $autoIncrement;
	
	public function getExtra(){
		return $this->extra;
	}
	public function setExtra($extra){
		$this->extra = $extra;
	}
	
	public function getAutoIncrement(){
		return $this->autoIncrement;
	}
	public function setAutoIncrement($autoIncrement){
		$this->autoIncrement = $autoIncrement;
	}
	
	public function getUpdateAssignment()
	{
		if ($this->dataType == 'date')
			return $this->name . ' = str_to_date(p_' . $this->name . ', \'%d/%m/%Y\')';
		return $this->name . ' = p_' . $this->name;
	}
	
	
	public function getProcedureParameter($withType = false)
	{
		$resp = 'p_' . $this->name;
		if ($withType){
			if ($this->dataType == 'date')
				$resp .= ' varchar(10)';
			else
				$resp .= ' ' . $this->type;
		}
		return $resp;
	}
	
	public function getWhereFilter($parameterNull = true)
	{
		$resp = '';
		if ($parameterNull)
			$resp .= '(p_' . $this->name . ' IS NULL) OR (';
		if ($this->dataType == 'varchar')
			$resp .= 'UPPER(' . $this->name . ') LIKE CONCAT(\'%\', UPPER(p_'.$this->name.'), \'%\')';
		else if ($this->dataType == 'date')
			$resp .= $this->name . ' = str_to_date(p_' . $this->name . ', \'%d/%m/%Y\')';
		else
			$resp .= $this->name . ' = p_' . $this->name;
		if ($parameterNull)
			$resp = '(' . $resp . '))';
		return $resp;
	}
	
	public function getModelParameter($default = null)
	{
		$resp = '$' . $this->name;
		if ($default !== null)
			$resp .= ' = ' . $default;
		return $resp;
	}
}
?>
