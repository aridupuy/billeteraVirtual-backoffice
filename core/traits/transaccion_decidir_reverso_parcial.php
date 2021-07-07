<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of transaccion_decidir_reverso_parcial
 *
 * @author ariel
 */
class Transaccion_decidir_reverso_parcial extends Transaccion_decidir {

    //put your code here



   
    public function calculo_directo($id_marchand, $id_mp, $monto_pagador, \Sabana $sabana = null, \Barcode $barcode = null, $id_pricing_pag = false, $id_pricing_cdi = false, $traslada_comision = false) {
        developer_log("aca2");
       
        if (!is_numeric($monto_pagador)) {
            $this->developer_log("Solo puede realizar el calculo directo de números: '" . $monto_pagador);
            return false;
        }

        if (!$this->optimizar_mp($id_mp)) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
            return false;
        }
        
            
        $pag = '1';
        $cdi = '2';
        $this->pricing_pag = $this->obtener_comisiones($id_marchand, $id_mp, $pag, $sabana, $barcode);
        $this->pricing_cdi = $this->obtener_comisiones($id_marchand, $id_mp, $cdi, $sabana, $barcode);
        list($pricing_cdi, $pricing_pag) = $this->calcular_descuento($this->pricing_pag, $this->pricing_cdi, $id_marchand);
        $this->pricing_cdi = $pricing_cdi;
        $this->pricing_pag = $pricing_pag;
        unset($pricing_cdi);
        unset($pricing_pag);
            
            
        if ($this->pricing_pag === false OR $this->pricing_cdi === false) {
            $this->developer_log("Ha ocurrido un error al obtener las Comisiones. ");
            return false;
        }

        $operacion_comisiones = $this->operacion_comisiones(self::$mp);
//        var_dump($operacion_comisiones);
        $monto_pagador = $this->floordec(abs($monto_pagador), 2);
        // reset todos los valores 
        $pag_fix = 0;
        $pag_var = 0;
        $monto_cd = 0;
        $cdi_fix = 0;
        $cdi_var = 0;

        $monto_marchand = 0;
        //    echo  "<br> transaccion : $id_mp ";
        //   var_dump($traslada_comision);
        if (in_array($id_mp, array(Mp::COBRO_COMO_PROVEEDOR, Mp::PAGO_A_PROVEEDOR, Mp::PAGO_PROVEEDOR_PENDIENTE, Mp::RETIROS))) {
            if ($traslada_comision) { // paga el proveedor
                $array = $this->monto_siguiente($monto_pagador, $this->pricing_pag->get_pri_fijo(), $this->pricing_pag->get_pri_variable(), $this->pricing_pag->get_pri_minimo(), $this->pricing_pag->get_pri_maximo(), $operacion_comisiones);
                if ($array !== false) {
                    developer_log(json_encode($array));
                    list($monto_cd, $pag_fix, $pag_var) = $array;
                    $monto_marchand = $monto_cd;
                    unset($array);
                } else {
                    return false;
                }
            } else {  //page el marchand
                developer_log($this->pricing_cdi->get_pri_variable() . " " . $this->pricing_cdi->get_id_pricing());
                $array = $this->monto_siguiente($monto_pagador, $this->pricing_cdi->get_pri_fijo(), $this->pricing_cdi->get_pri_variable(), $this->pricing_cdi->get_pri_minimo(), $this->pricing_cdi->get_pri_maximo(), $operacion_comisiones);
                if ($array !== false) {
                    list($monto_marchand, $cdi_fix, $cdi_var) = $array;
                    $monto_cd = $monto_pagador;
                    unset($array);
                } else {
                    return false;
                }
            }
        } else {
            $array = $this->monto_siguiente($monto_pagador, $this->pricing_pag->get_pri_fijo(), $this->pricing_pag->get_pri_variable(), $this->pricing_pag->get_pri_minimo(), $this->pricing_pag->get_pri_maximo(), $operacion_comisiones);
            if ($array !== false) {
                list($monto_cd, $pag_fix, $pag_var) = $array;
                unset($array);
            } else {
                return false;
            }
            $array = $this->monto_siguiente($monto_cd, $this->pricing_cdi->get_pri_fijo(), $this->pricing_cdi->get_pri_variable(), $this->pricing_cdi->get_pri_minimo(), $this->pricing_cdi->get_pri_maximo(), $operacion_comisiones);
            if ($array !== false) {
                list($monto_marchand, $cdi_fix, $cdi_var) = $array;
                unset($array);
            } else {
                return false;
            }
        }
        $this->developer_log('CD: ' . $monto_pagador . ' | ' . $pag_fix . ' | ' . $pag_var . ' | ' . $monto_cd . ' | ' . $cdi_fix . ' | ' . $cdi_var . ' | ' . $monto_marchand);
        if (in_array(self::$mp->get_id_mp(), array(Mp::COSTO_DECIDIR_DEVOLUCION))) {
            $diferencia = $monto_marchand - $monto_pagador; //se da vuelta el signo por que es 0 y sino queda positivo siempre
//                    $pag_fix=$diferencia;
            $monto_pagador = 0;
            $monto_marchand = $diferencia;
            $monto_cd = 0;
            $this->developer_log('COSTO EFECTIVO CD: ' . $monto_pagador . ' | ' . $pag_fix . ' | ' . $pag_var . ' | ' . $monto_cd . ' | ' . $cdi_fix . ' | ' . $cdi_var . ' | ' . $monto_marchand);
            return array($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand);
        }

        return array($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand);
    }

   protected function operacion_comisiones(Mp $mp) {
       $sumar = function($a, $b, $c = 0) {
            return $a + $b + $c;
        };
        $restar = function($a, $b, $c = 0) {
            return $a - $b - $c;
        };
        
        return $restar;
    }
    
    public function debe_procesar_costo_asociado($id_mp) {
       if(in_array($id_mp, array(Mp::COSTO_PEI_DEVOLUCION, Mp::COSTO_DECIDIR_DEVOLUCION)) ){
            return false;
        }
        return true;
    }
    protected function deducir_id_mp_para_costo_asociado($id_mp) {
        return Mp::COSTO_DECIDIR_DEVOLUCION;
    }
    protected function deducir_id_referencia_para_costo_asociado($id_mp, \Moves $moves) {
        return $moves->get_id();
    }
    protected function procesar_costo_asociado($moves, Sabana $sabana = null, $traslada_comision = false) {
        if(in_array($moves->get_id_mp(), array(Mp::COSTO_PEI_DEVOLUCION, Mp::COSTO_DECIDIR_DEVOLUCION)) ){
            return parent::procesar_costo_asociado($moves,$sabana, $traslada_comision);
        }
        $id_marchand = $moves->get_id_marchand();
        $id_mp = $this->deducir_id_mp_para_costo_asociado($moves->get_id_mp());
        $id_referencia = $this->deducir_id_referencia_para_costo_asociado($id_mp, $moves);
        $monto_pagador = $moves->get_monto_pagador();
        # Los costos asociados son registros extra que solo tienen pricing
        //$monto_pagador = 0;
        $fecha = new Datetime('now');
        return Transaccion::crear($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia);
    }
}
