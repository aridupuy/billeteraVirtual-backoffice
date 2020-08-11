<?php

class Costeador_pagofacil extends Costeador
{
	protected function obtener_recordset()
	{
		return Sabana::registros_a_costear_pagofacil($this->limite_de_registros_por_ejecucion);
	}
}