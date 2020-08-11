<?php

class Costeador_linkpagos extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_linkpagos($this->limite_de_registros_por_ejecucion);
	}
}