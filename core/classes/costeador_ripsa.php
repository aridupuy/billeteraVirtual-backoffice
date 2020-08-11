<?php

class Costeador_ripsa extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_ripsa($this->limite_de_registros_por_ejecucion);
	}
}