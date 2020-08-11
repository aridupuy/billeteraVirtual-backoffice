<?php

class Costeador_multipago extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_multipago($this->limite_de_registros_por_ejecucion);
	}
}