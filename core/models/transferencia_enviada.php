<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of transferencia_recibida
 *
 * @author ariel
 */
class Transferencia_enviada extends Model
{

    //put your code here
    public static $id_tabla = "id_transferencia";

    public $id_transferencia;
    public $id_destinatario;
    public $status;
    public $monto;
    public $id_authstat;
    public $id_cuenta;
    public $id_usuario;
    public $respuesta_servicio;
    public function get_id_transferencia()
    {
        return $this->id_transferencia;
    }

    public function get_id_destinatario()
    {
        return $this->id_destinatario;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function get_monto()
    {
        return $this->monto;
    }

    public function get_id_authstat()
    {
        return $this->id_authstat;
    }

    public function get_id_cuenta()
    {
        return $this->id_cuenta;
    }

    public function get_id_usuario()
    {
        return $this->id_usuario;
    }

    public function get_respuesta_servicio()
    {
        return $this->respuesta_servicio;
    }

    public function set_id_transferencia($id_transferencia)
    {
        $this->id_transferencia = $id_transferencia;
        return $this;
    }

    public function set_id_destinatario($id_destinatario)
    {
        $this->id_destinatario = $id_destinatario;
        return $this;
    }

    public function set_status($status)
    {
        $this->status = $status;
        return $this;
    }

    public function set_monto($monto)
    {
        $this->monto = $monto;
        return $this;
    }

    public function set_id_authstat($id_authstat)
    {
        $this->id_authstat = $id_authstat;
        return $this;
    }

    public function set_id_cuenta($id_cuenta)
    {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_id_usuario($id_usuario)
    {
        $this->id_usuario = $id_usuario;
        return $this;
    }

    public function set_respuesta_servicio($respuesta_servicio)
    {
        $this->respuesta_servicio = $respuesta_servicio;
        return $this;
    }

    public function select_cashout($variables = false)
    {
        unset($variables['motivo']);
        unset($variables['dataTable_length']);
        unset($variables['checkbox_todo']);
        unset($variables['selector_']);

        if (isset($variables['id_transferencia'])) {
            $variables['a.id_transferencia'] = $variables['id_transferencia'];
            unset($variables['id_transferencia']);
        }else{
            $and = "WHERE true ";
        }

        if (isset($variables['email'])) {
            $and .= "AND (e.email ilike '%" . $variables['email'] . "%' OR b.email ilike '%" . $variables['email'] . "%') ";
            unset($variables['email']);
        }

        if (isset($variables['nombre'])) {
            $and .= "AND (b.nombre ilike '%" . $variables['nombre'] . "%')";
            unset($variables['nombre']);
        }

        if (isset($variables['apellido'])) {
            $and .= "AND (b.apellido ilike '%" . $variables['apellido'] . "%')";
            unset($variables['apellido']);
        }

        if (isset($variables['status'])) {
            $and .= "AND (a.status ilike '%" . $variables['status'] . "%')";
            unset($variables['status']);
        }

        if (isset($variables['monto_desde']) || isset($variables['monto_hasta'])) {
            if (isset($variables['monto_desde']) && isset($variables['monto_hasta'])) {
                $and .= "AND a.monto >= " . $variables['monto_desde'] . " AND a.monto <=" . $variables['monto_hasta'] . " ";
            }
            if (isset($variables['monto_hasta']) === false) {
                $and .= "AND a.monto >= " . $variables['monto_desde'] . " ";
            }
            if (isset($variables['monto_desde']) === false){
                $and .= "AND a.monto <= " . $variables['monto_hasta'] . " ";
            }

            unset($variables['monto_desde']);
            unset($variables['monto_hasta']);
        }

        if (isset($variables['fecha_desde']) || isset($variables['fecha_hasta'])) {
            if (isset($variables['fecha_desde']) && isset($variables['fecha_hasta'])) {
                $and .= "AND a.fecha_gen >= '" . $variables['fecha_desde'] . "' AND a.fecha_gen <= '" . $variables['fecha_hasta'] . "' ";
            }
            if (isset($variables['fecha_hasta']) === false) {
                $and .= "AND a.fecha_gen >= '" . $variables['fecha_desde'] . "' ";
            }
            if (isset($variables['fecha_desde']) === false) {
                $and .= "AND a.fecha_gen <= '" . $variables['fecha_hasta'] . "' ";
            }

            unset($variables['fecha_desde']);
            unset($variables['fecha_hasta']);
        }

        if (isset($variables['cbucvu'])) {
            $and .= "OR b.cvu ilike '%" . $variables['cbucvu'] . "%' OR b.cbu ilike '%" . $variables['cbucvu'] . "%' ";
            unset($variables['cbucvu']);
        }

        $filtros = self::preparar_filtros($variables);

        //Falta Referencia y Motivo en CashOut
        $sql = "SELECT a.id_transferencia,a.fecha_gen,e.email as email_origen,c.cuil as cuil_origen,a.monto,a.status,b.email as email_destino ,b.cvu,b.cbu,b.alias,b.nombre,b.apellido,b.cuit as cuit_destino,b.nombre_banco,b.cod_banco 
        from ef_transferencia_enviada
        a left join ef_destinatario b on a.id_destinatario = b.id_destinatario 
        left join ef_cuenta c on a.id_cuenta = c.id_cuenta 
        left join ho_authstat d on a.id_authstat = d.id_authstat 
        left join ef_usuario e on a.id_usuario = e.id_usuario $filtros $and";

        echo $sql;
        // var_dump($sql);
        // exit;
        return self::execute_select($sql, $variables, 10000);
    }
}
