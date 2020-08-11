<?php

class Costeador_rapipago extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_rapipago($this->limite_de_registros_por_ejecucion);
	}
}