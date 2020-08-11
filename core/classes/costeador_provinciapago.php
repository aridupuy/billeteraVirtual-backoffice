<?php

class Costeador_provinciapago extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_provinciapago($this->limite_de_registros_por_ejecucion);
	}
}