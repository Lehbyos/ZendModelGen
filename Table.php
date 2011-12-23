<?php
include_once 'Analizer.php';
include_once 'classes/common/Table.php';

class MySqlTable extends Table
{
	public $engine;
	public $version;
	public $autoIncrement;
	
	
	private $sequentialPk;
	
	public function __construct(Analizer $analizer, $name, $engine, $version, $autoIncrement, $comment)
	{
		parent::__construct($name);
		$this->analizer      = $analizer;
		$this->engine        = $engine;
		$this->version       = $version;
		$this->autoIncrement = $autoIncrement;
		$this->comment       = $comment;
		$this->sequentialPk  = false;
		
		$this->columns = $this->analizer->getTableColumns($this);
		foreach($this->columns as $column)
		{
			$spParameter        = $column->getProcedureParameter();
			$spParameterType    = $column->getProcedureParameter(true);
			$spWhereFilter      = $column->getWhereFilter();
			$modelParameter     = $column->getModelParameter();
			$modelParameterNull = $column->getModelParameter('null');
			
			$this->routinesParameters['select'][] = $spParameterType;
			$this->routinesConditions['select'][] = $spWhereFilter;
			$this->modelParameters['select'][] = $modelParameterNull;
			if (!$column->isPk())
			{
				$this->routinesParameters['insert'][] = $spParameterType;
				$this->routinesParameters['update'][] = $spParameterType;
				$this->updateAssignments[] = $column->getUpdateAssignment();
				$this->modelParameters['insert'][] = ($column->getNullable()) ? $modelParameterNull : $modelParameter;
				$this->modelParameters['update'][] = ($column->getNullable()) ? $modelParameterNull : $modelParameter;
			}
			else
			{
				$this->modelParameters['update'][] = $modelParameter;
				if (!$column->getAutoIncrement())
				{
					$this->routinesParameters['insert'][] = $spParameterType;
					$this->modelParameters['insert'][] = $modelParameter;
				}
				else
					$this->sequentialPk = true;
				
				$this->routinesParameters['update'][] = $spParameterType;
				$this->routinesParameters['delete'][] = $spParameterType;
				
				$this->routinesConditions['update'][] = $column->getWhereFilter(false);
				$this->routinesConditions['delete'][] = $spWhereFilter;
			}
		}
	}
	
	public function getSelectProcedure()
	{
		$resp = 'CREATE PROCEDURE ' . $this->analizer->getDbName() . '.' . $this->routinesNames['select'] . "(\n";
		$max = count($this->routinesParameters['select']);
		for($i = 0; $i < $max; ++$i)
		{
			$param = $this->routinesParameters['select'][$i];
			$resp .= "\t" . $param;
			if ($i + 1 < $max)
				$resp .= ",\n";
		}
		$resp .= "\n)\nBEGIN\n";
		$resp .= "\tSELECT\n";
		$max = count($this->columns);
		for($i = 0; $i < $max; ++$i)
		{
			$column = $this->columns[$i];
			if ($column->getType() == 'date')
				$resp .= "\t\tdate_format(" . $column->getName() . ', \'%d/%m/%Y\') ' . $column->getName();
			else
				$resp .= "\t\t" . $this->columns[$i]->getName();
			if ($i + 1 < $max)
				$resp .= ",\n";
		}
		$resp .= "\n\tFROM " . $this->name;
		$max = count($this->routinesConditions['select']);
		if ($max > 0)
		{
			$resp .= "\n\tWHERE\n";
			for($i = 0; $i < $max; ++$i)
			{
				$cond = $this->routinesConditions['select'][$i];
				$resp .= "\t\t" . $cond;
				if ($i + 1 < $max)
					$resp .= " AND\n";
			}
		}
		$resp .= ";\nEND\n\$\$\n";
		return $resp;
	}
	
	public function getInsertProcedureName(){
		return $this->routinesNames['insert'];
	}
	
	public function getUpdateProcedureName(){
		return $this->routinesNames['update'];
	}
	
	public function getSelectProcedureName(){
		return $this->routinesNames['select'];
	}
	
	public function getInsertProcedure($toDb = false)
	{
		$resp = 'CREATE PROCEDURE ' . $this->analizer->getDbName() . '.' . $this->routinesNames['insert'] . "(";
		$max = count($this->routinesParameters['insert']);
		if ($max > 0)
		{
			$resp .= "\n";
			for($i = 0; $i < $max; ++$i)
			{
				$resp .= "\t" . $this->routinesParameters['insert'][$i];
				if ($i + 1 < $max)
					$resp .= ",\n";
			}
			$resp .= "\n";
		}
		$resp .= ")\nBEGIN\n";
		$resp .= "\tINSERT INTO " . $this->name . " (\n";
		$names = "";
		$values = "";
		$lastID = "";
		$max = count($this->columns);
		for($i = 0; $i < $max; ++$i)
		{
			$names .= "\t\t" . $this->columns[$i]->getName();
			if ($this->columns[$i]->getAutoIncrement())
			{
				$values .= "\t\tNULL";
				$lastID = 'SELECT LAST_INSERT_ID() as "'.$this->columns[$i]->getName().'"';
			}
			else{
				if ($this->columns[$i]->getDataType() == 'date')
					$values .= "\t\tstr_to_date(p_" . $this->columns[$i]->getName() . ', \'%d/%m/%Y\')';
				else
					$values .= "\t\tp_" . $this->columns[$i]->getName();
			}
			if ($i + 1 < $max)
			{
				$names  .= ",\n";
				$values .= ",\n";
			}
		}
		$resp .= $names . "\n\t)\n\tVALUES(\n" . $values . "\n\t);\n";
		if ($lastID != '')
			$resp .= "\t" . $lastID . ";\n";
		$resp .= "END\n\$\$\n";
		return $resp;
	}
	
	
	public function getUpdateProcedure()
	{
		$resp = 'CREATE PROCEDURE ' . $this->analizer->getDbName() . '.' . $this->routinesNames['update'] . '(';
		$max = count($this->routinesParameters['update']);
		if ($max > 0)
		{
			$resp .= "\n";
			for($i = 0; $i < $max; ++$i)
			{
				$param = $this->routinesParameters['update'][$i];
				$resp .= "\t" . $param;
				if ($i + 1 < $max)
					$resp .= ",\n";
			}
			$resp .= "\n";
		}
		$resp .= ")\nBEGIN\n\tUPDATE " . $this->name . " SET\n";
		$max = count($this->updateAssignments);
		for($i = 0; $i < $max; ++$i)
		{
			$resp .= "\t\t" . $this->updateAssignments[$i];
			if ($i + 1 < $max)
				$resp .= ",\n";
		}
		$resp .= "\n\tWHERE\n";
		$max = count($this->routinesConditions['update']);
		for($i = 0; $i < $max; ++$i)
		{
			$resp .= "\t\t" . $this->routinesConditions['update'][$i];
			if ($i + 1 < $max)
				$resp .= " AND\n";
		}
		$resp .= ";\nEND\n\$\$\n";
		return $resp;
	}
	
	
	private function getModelDB()
	{
		$resp = "\tpublic static function getDB()\n\t{\n";
		$resp .= "\t\t\$db = Zend_Db_Table_Abstract::getDefaultAdapter();\n";
		$resp .= "\t\t\$db->query('SET NAMES \"utf8\"');\n";
		$resp .= "\t\t\$db->setFetchMode(Zend_Db::FETCH_OBJ);\n";
		$resp .= "\t\treturn \$db;\n";
		$resp .= "\t}\n";
		return $resp;
	}
	
	private function getModelSelectMethod()
	{
		$resp = "\tpublic static function " . $this->modelMethodsNames['select'] . "(";
		$max = count($this->modelParameters['select']);
		$parameters = '';
		$parametersCount = 0;
		if ($max > 0)
		{
			for($i = 0; $i < $max; ++$i)
			{
				$param = $this->modelParameters['select'][$i];
				$resp .= $param;
				$paramParts = explode(' ', $param);
				$parameters .= "\t\t\t\t" . $paramParts[0];
				$parametersCount++;
				if ($i + 1 < $max)
				{
					$resp .= ', ';
					$parameters .= ",\n";
				}
			}
		}
		$resp .= ")\n\t{\n";
		$resp .= "\t\t\$db = self::getDB();\n";
		$resp .= "\t\t\$resp = \$db->fetchAll";
		$resp .= "(\n\t\t\t'call ".$this->routinesNames['select']."(";
		if ($parametersCount > 0)
		{
			for($i = 0; $i < $parametersCount; ++$i)
			{
				$resp .= '?';
				if ($i + 1 < $parametersCount)
					$resp .= ', ';
			}
		}
		$resp .= ")',\n\t\t\tarray(\n".$parameters."\n\t\t\t)\n\t\t);\n";
		$resp .= "\t\treturn \$resp;\n";
		$resp .= "\t}\n";
		return $resp;
	}
	
	private function getModelInsertMethod()
	{
		$resp = "\tpublic static function " . $this->modelMethodsNames['insert'] . "(";
		$max = count($this->modelParameters['insert']);
		$parameters = '';
		$parametersCount = 0;
		if ($max > 0)
		{
			for($i = 0; $i < $max; ++$i)
			{
				$param = $this->modelParameters['insert'][$i];
				$resp .= $param;
				$paramParts = explode(' ', $param);
				$parameters .= "\t\t\t\t" . $paramParts[0];
				$parametersCount++;
				if ($i + 1 < $max)
				{
					$resp .= ', ';
					$parameters .= ",\n";
				}
			}
		}
		$resp .= ")\n\t{\n";
		$resp .= "\t\t\$db = self::getDB();\n";
		$resp .= "\t\t\$resp = \$db->";
		$resp .= ($this->sequentialPk) ? 'fetchOne' : 'query';
		$resp .= "(\n\t\t\t'call " . $this->routinesNames['insert'] . '(';
		for($i = 0; $i < $parametersCount; ++$i)
		{
			$resp .= '?';
			if ($i + 1 < $parametersCount)
				$resp .= ', ';
		}
		$resp .= ")',\n";
		$resp .= "\t\t\tarray(\n" . $parameters . "\n\t\t\t)\n\t\t);\n";
		$resp .= "\t\treturn \$resp;";
		$resp .= "\n\t}\n";
		return $resp;
	}
	
	private function getModelUpdateMethod()
	{
		$resp = "\tpublic static function " . $this->modelMethodsNames['update'] . "(";
		$max = count($this->modelParameters['update']);
		$parameters = '';
		$parametersCount = 0;
		if ($max > 0)
		{
			for($i = 0; $i < $max; ++$i)
			{
				$param = $this->modelParameters['update'][$i];
				$resp .= $param;
				$paramParts = explode(' ', $param);
				$parameters .= "\t\t\t\t" . $paramParts[0];
				$parametersCount++;
				if ($i + 1 < $max)
				{
					$resp .= ', ';
					$parameters .= ",\n";
				}
			}
		}
		$resp .= ")\n\t{\n";
		$resp .= "\t\t\$db = self::getDB();\n";
		$resp .= "\t\t\$resp = \$db->query";
		//$resp .= ($this->sequentialPk) ? 'fetchOne' : 'query';
		$resp .= "(\n\t\t\t'call " . $this->routinesNames['update'] . '(';
		for($i = 0; $i < $parametersCount; ++$i)
		{
			$resp .= '?';
			if ($i + 1 < $parametersCount)
				$resp .= ', ';
		}
		$resp .= ")',\n";
		$resp .= "\t\t\tarray(\n" . $parameters . "\n\t\t\t)\n\t\t);\n";
		$resp .= "\t\treturn \$resp;";
		$resp .= "\n\t}\n";
		return $resp;
	}
	
	public function getMapper($module = null)
	{
		$resp = "<?php\nclass ";
		if ($module != null)
			$resp .= ucfirst($module) . '_';
		else
			$resp .= 'Application_';
		$resp .= 'Model_' . $this->camelCaseName . "{\n";
		$resp .= $this->getModelDB() . "\n";
		$resp .= $this->getModelSelectMethod() . "\n";
		$resp .= $this->getModelInsertMethod() . "\n";
		$resp .= $this->getModelUpdateMethod() . "\n";
		$resp .= "}\n?>\n";
		return $resp;
	}
	
	public function getModel($module = null)
	{
		$resp = "<?php\nclass ";
		$resp .= ($module === null) ? '' : ucfirst($module);
		$resp .= ucfirst($this->name) . "\n{";
		foreach($this->columns as $column)
		{
			$resp .= "\tprivate $" . $column->name . ";\n";
		}
		$resp .= "\n?>";
		return $resp;
	}
}
?>
