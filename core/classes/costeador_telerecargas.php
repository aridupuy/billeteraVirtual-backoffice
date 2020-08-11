<?php

class Costeador_telerecargas extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_telerecargas($this->limite_de_registros_por_ejecucion);
	}
}