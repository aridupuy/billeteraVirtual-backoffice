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
class Validacion_mercado_pago_clicpagos extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_4 , array('client_id' => '8255255304938580',
                'client_secret' => '1f0M14QqNFOXkfqWJPyNOtdl4YdO8zeX',
                'correo' => self::MERCADOPAGO_CORREO_4 . self::DOMINIO_CORREO,
                'alias' => 'MP4',
                'id_peucd' => Peucd::MERCADOPAGO_MP4
            ));
    }

}