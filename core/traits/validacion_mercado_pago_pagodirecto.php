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
class Validacion_mercado_pago_pagodirecto extends Validacion_mercado_pago {
    //put your code here
    public function obtener_cuenta() {
        
        return array(self::MERCADOPAGO_CORREO_7 ,array(
                'access_token' => self::MERCADOPAGO_CLAVE_PRIVADA_PAGODIRECTO,
                'correo' => self::MERCADOPAGO_CORREO_7 . self::DOMINIO_CORREO,
                'alias' => 'MP7',
                'id_peucd' => Peucd::MERCADOPAGO_MP7
            ));
    }

}