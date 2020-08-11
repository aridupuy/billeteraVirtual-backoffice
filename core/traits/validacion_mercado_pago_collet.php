<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_mercado_pago_mp
 *
 * @author ariel
 */
class Validacion_mercado_pago_collet extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_2 , array('client_id' => '4812010488902074',
                'client_secret' => 'RSXDuaXJJSI7FSl1OzhfPeu9hKdqIywR',
                'correo' => self::MERCADOPAGO_CORREO_2 . self::DOMINIO_CORREO,
                'alias' => 'MP2',
                'id_peucd' => Peucd::MERCADOPAGO_MP2
            ));
    }

}