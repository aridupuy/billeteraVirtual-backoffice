<?php

class Costeador_cobroexpress extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_cobroexpress($this->limite_de_registros_por_ejecucion);
	}
}