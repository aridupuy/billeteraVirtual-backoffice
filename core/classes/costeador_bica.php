<?php

class Costeador_bica extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_bica($this->limite_de_registros_por_ejecucion);
	}
}