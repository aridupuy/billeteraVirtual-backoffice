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
class Validacion_mercado_pago_mp extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_1 , array('client_id' => '1872102149935260',
                'client_secret' => 'U8SInUyeb58ixtUQanTvTCrMnYWRuEff',
                'correo' => self::MERCADOPAGO_CORREO_1 . self::DOMINIO_CORREO,
                'alias' => 'MP1',
                'id_peucd' => Peucd::MERCADOPAGO_MP1
            ));
    }

}