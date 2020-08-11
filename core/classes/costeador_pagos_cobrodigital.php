<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of costeador_pagos_cd
 *
 * @author ariel
 */
class Costeador_pagos_cobrodigital extends Costeador{
    protected function obtener_recordset() {
       return Sabana::registros_a_costear_pagos_cobrodigital($this->limite_de_registros_por_ejecucion);
    }
}
