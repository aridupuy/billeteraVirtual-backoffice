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
class Validacion_mercado_pago_pvp extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_5 , array(
                'access_token' => MERCADOPAGO_CLAVE_PRIVADA_PVP,
                'correo' => self::MERCADOPAGO_CORREO_5 . self::DOMINIO_CORREO,
                'alias' => 'MP5',
                'id_peucd' => Peucd::MERCADOPAGO_MP5
            ));
    }

}
