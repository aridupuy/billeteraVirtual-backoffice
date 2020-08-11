<?php

class Costeador_prontopago extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_prontopago($this->limite_de_registros_por_ejecucion);
	}
}