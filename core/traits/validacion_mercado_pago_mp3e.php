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
class Validacion_mercado_pago_mp3e extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_3 , array('client_id' => '420311948104044',
                'client_secret' => 'y3MnDnV0aBDXPUR5vmB3kTboyr9sFVbL',
                'correo' => self::MERCADOPAGO_CORREO_3 . self::DOMINIO_CORREO,
                'alias' => 'MP3',
                'id_peucd' => Peucd::MERCADOPAGO_MP3
            ));
    }

}