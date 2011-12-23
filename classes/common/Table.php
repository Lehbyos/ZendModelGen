<?php
abstract class Table
{
	protected $name;
	protected $camelCaseName;
	protected $columns;
	protected $comment;
	protected $routinesNames;
	protected $routinesParameters;
	protected $routinesConditions;
	protected $updateAssignments;
	protected $modelParameters;
	protected $analizer;
	
	public function getName(){
		return $this->name;
	}
	public function setName($name){
		$this->name = $name;
		$parts = explode('_', $name);
		if (count($parts) > 1){
			$this->camelCaseName = '';
			foreach($parts as $part)
				$this->camelCaseName .= ucfirst($part);
		}
		else
			$this->camelCaseName = ucfirst($name);
	}
	public function getCamelcaseName(){
		return $this->camelCaseName;
	}
	
	public function getComment(){
		return $this->comment;
	}
	public function setComment($comment){
		$this->comment = $comment;
	}
	
	public function __construct($name)
	{
		$this->setName($name);
		$this->routinesNames = array(
			/*
			'select' => 'sp_' . $this->name . '_select',
			'insert' => 'sp_' . $this->name . '_insert',
			'update' => 'sp_' . $this->name . '_update',
			'delete' => 'sp_' . $this->name . '_delete'
			*/
			'select' => 'pa_' . $this->name . '_seleccionar',
			'insert' => 'pa_' . $this->name . '_insertar',
			'update' => 'pa_' . $this->name . '_modificar',
			'delete' => 'pa_' . $this->name . '_eliminar'
		);
		$this->routinesParameters = array(
			'select' => array(),
			'insert' => array(),
			'update' => array(),
			'delete' => array()
		);
		$this->routinesConditions = array(
			'select' => array(),
			'insert' => array(),
			'update' => array(),
			'delete' => array()
		);
		$this->modelMethodsNames = array(
			'select' => 'buscar',
			'insert' => 'agregar',
			'update' => 'modificar',
			'delete' => 'eliminar'
		);
		$this->modelParameters = array(
			'select' => array(),
			'insert' => array(),
			'update' => array(),
			'delete' => array()
		);
		$this->updateAssignments = array();
		
	}
}
?>
