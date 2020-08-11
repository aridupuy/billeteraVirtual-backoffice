<?php
// namespace Classes;
class Planilla{

	private $filas=array();
	public function get_filas() { return $this->filas;  }

	public function cargar($recordset){
		if(is_array($recordset))
		{
			# Es un array
			foreach ($recordset as $fila):
				$this->filas[]=$fila;
			endforeach;
		}
		else
		{
			# Es un recordset
	    	if($recordset->rowCount())
	    	{
			    	foreach ($recordset as $row):
			    		$fila=array();
			    		foreach ($row as $columna => $valor):
			    			if(!is_numeric($columna)) #TEMP? Puede adodb no retornar dos punteros por columna?
								$fila[]= $row[$columna];
			    		endforeach;
			     		$this->filas[]=$fila;
			    	endforeach;
			}
		}
		return $this;
	}

}

