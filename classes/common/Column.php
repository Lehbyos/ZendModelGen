<?php
abstract class Column
{
	protected $name;
	protected $camelCaseName;
	protected $nullable;
	protected $dataType;
	protected $characterLength;
	protected $octetLength;
	protected $precision;
	protected $scale;
	protected $type;
	protected $comment;
	protected $isNumeric;
	protected $isPK;
	
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
	public function setNullable($nullable){
		$this->nullable = $nullable;
	}
	public function getNullable(){
		return $this->nullable;
	}
	public function getDataType(){
		return $this->dataType;
	}
	public function setDataType($dataType){
		$this->dataType = $dataType;
	}
	
	public function getCharacterLength(){
		return $this->characterLength;
	}
	public function setCharacterLength($length){
		$this->characterLength = $length;
	}
	public function getOctetLength(){
		return $this->octetLength;
	}
	public function setOctetLength($length){
		$this->octectLength = $length;
	}
	public function getPrecision(){
		return $this->precision;
	}
	public function setPrecision($precision){
		$this->precision = $precision;
	}
	public function getScale(){
		return $this->scale;
	}
	public function setScale($scale){
		$this->scale = $scale;
	}
	public function getType(){
		return $this->type;
	}
	public function setType($type){
		$this->type = $type;
	}
	public function getComment(){
		return $this->comment;
	}
	public function setComment($comment){
		$this->comment = $comment;
	}
	public function getIsNumeric(){
		return $this->isNumeric;
	}
	public function isPk(){
		return $this->isPK;
	}
	public function setPK(){
		$this->isPK = true;
	}
	
	public abstract function getUpdateAssignment();
	public abstract function getProcedureParameter($withType = false);
	public abstract function getWhereFilter($nullValue = true);
	public abstract function getModelParameter($defaultValue = null);
}
?>
