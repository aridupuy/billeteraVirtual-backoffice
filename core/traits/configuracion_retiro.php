<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Configuracion_retiro {
    
    const ACTIVAR_DEBUG = true;
    public $pricing_pag = false; # Es mas complicado pero podria optimizarlo mas adelante
    public $pricing_cdi = false; # Es mas complicado pero podria optimizarlo mas adelante
    public $id_marchand;
    public static $grado_de_recursividad = 0;
    public $min;
    public $hou;
    public $dom;
    public $mon;
    public $dow;
    const MONTO_MINIMO_OPERACION = 100;

public function crear($id_marchand, $periodo, $monto_tipo, $porcentaje, $monto_fijo, $monto_limite, $id_cbumarchand) {
        
        $hoy = new DateTime('now');
        $fecha = $hoy->format(FORMATO_FECHA_POST);
        
        switch($periodo){
            case 'mensual': #0 7 1 * *
                $min = '0';
                $hou = '7';
                $dom = strtolower(date("j",strtotime($fecha)));
                $mon = '*';
                $dow = '*';
                break;
            case 'semanal': #0 7 * * 1
                $min = '0';
                $hou = '7';
                $dom = '*';
                $mon = '*';
                $dow = strtolower(date("w",strtotime($fecha)));
                break;
            case 'quincenal': #0 7 1,15 * *
                $min = '0';
                $hou = '7';
                $dom = '1,15';
                $mon = '*';
                $dow = '*';
                break;
            case 'diaria': #0 7 * * *
            case 'monto':
                $min = '0';
                $hou = '7';
                $dom = '*';
                $mon = '*';
                $dow = '*';
                break;
        }
        
       
        Model::StartTrans();
        $this->developer_log("inicia creancion de configuracion de retiro");
        $retiro = new Retiro_automatico();
        $id_retiro = $this->validar($id_cbumarchand);
        if($id_retiro!=NULL)
            $retiro->get($id_retiro);
        else 
            $retiro->set_fecha_gen($fecha);
        
        // DEPENDIENDO SI ES RETIRO POR MONTO, MONTO FIJO, PORCENTAJE 
        switch ($monto_tipo){
            case 'fijo':
                $retiro->set_monto($monto_fijo);
                $retiro->set_porcentaje_monto("null");
                $retiro->set_tipo(1);
                break;
            case 'porcentaje':
                $retiro->set_monto("null");
                $retiro->set_porcentaje_monto($porcentaje);
                $retiro->set_tipo(2);
                break;
            case 'monto':
                $retiro->set_monto($monto_limite);
                $retiro->set_porcentaje_monto(100);
                $retiro->set_tipo(3);
                break;
        }
        
//        if($porcentaje_monto)
//            $retiro->set_porcentaje_monto($porcentaje_monto);
//        if($monto)
//            $retiro->set_monto($monto);
        $retiro->set_id_marchand($id_marchand);
        $retiro->set_id_authstat(1);
        $retiro->set_min($min);
        $retiro->set_hou($hou);
        $retiro->set_dom($dom);
        $retiro->set_mon($mon);
        $retiro->set_dow($dow);
        //$retiro->set_id_usuario($id_usuario);
        //$retiro->set_id_tipouser($id_tipousuer);
        $retiro->set_id_cbumarchand($id_cbumarchand);
        //$retiro->set_fecha_ultimo_proceso($fecha);
        if($retiro->set()){
            Model::CompleteTrans();
            $this->developer_log("Configuracion generada de manera correcta");
            return true;
        }else {
            Model::FailTrans();
            $this->developer_log("Error al generar la configuracion de retiro");
            return false;
        }
         
    }
    
    public function ejecutar(){
        if(CONSOLA){
            $registros = $this->obtener_registros_a_procesar();
            foreach ($registros as $row) {
                $this->developer_log("Iniciando Retiro para IDM: ".$row['id_marchand']);
                if ($this->hacer_retiro($row)){
                    $this->developer_log("Retiro completado para IDM: ".$row['id_marchand']);
                }
            }
            return __METHOD__;
        }
    }
    
    protected function hacer_retiro($array){
        
        $retiro_aut=new Retiro_automatico($array);
        $id_marchand = $array['id_marchand'];
        $estado_cuenta = Cliente::obtener_estado_de_cuenta($id_marchand);
        $saldo_disponible = $estado_cuenta['saldo_disponible'];
        
            
        developer_log("SALDO DISPONIBLE: $saldo_disponible");
        $comisiones = $this->obtener_comisiones($saldo_disponible,$retiro_aut);
        $monto = $this->obtener_monto($saldo_disponible,$retiro_aut,$comisiones);
        $monto = $this->floordec($monto, 2);
        if(!$monto)
            return false;
        if($monto<SELF::MONTO_MINIMO_OPERACION){ //para que no intente ejecutar cosas como $0.00001
            developer_log("No se realizaran retiros por menos de $".SELF::MONTO_MINIMO_OPERACION);
            return false;
	
        }
	developer_log("El monto a retirar es $monto y el saldo disponible es $saldo_disponible"); 
        if($monto<=$saldo_disponible){
            if($this->retirar($id_marchand,$monto,$retiro_aut)){
                return true;
            }
                    
        }else{ 
            $this->developer_log("El monto a retirar es mayor al saldo disponible");
            return false;
        }
    }
    
    protected function obtener_monto($saldo_disponible, Retiro_automatico $retiro_aut,$comisiones) {
        developer_log($saldo_disponible);
        developer_log($saldo_disponible-$comisiones);
//        if($retiro_aut->get_porcentaje_monto()!=null)
//            $monto_retirar = (($saldo_disponible*$retiro_aut->get_porcentaje_monto())/100)-$comisiones;
//        else if($retiro_aut->get_monto()!=null)
//            $monto_retirar = $retiro_aut->get_monto();
//        $th
        $this->developer_log($retiro_aut->get_tipo());
        $monto_retirar=0;
        switch ($retiro_aut->get_tipo()) {
            case '1':
                if($retiro_aut->get_monto()!=null and $retiro_aut->get_porcentaje_monto()==null){
                    $monto_retirar = $retiro_aut->get_monto();
                }
                else {
                    developer_log("Retiro automatico mal configurado para IDM: ".$retiro_aut->get_id_marchand());
                }
//                return $monto_retirar;
                break;
            case '2':
                if($retiro_aut->get_monto()==null and $retiro_aut->get_porcentaje_monto()!=null){
                    $monto_retirar = (($saldo_disponible*$retiro_aut->get_porcentaje_monto())/100)-$comisiones;
                }
                else {
                    developer_log("Retiro automatico mal configurado para IDM: ".$retiro_aut->get_id_marchand());
                }
//                return $monto_retirar;
                break;
            case '3':
                if($retiro_aut->get_monto()!=null and $retiro_aut->get_porcentaje_monto()!=null){
                    $monto_retirar = $saldo_disponible-$comisiones;
                }
                else {
                    developer_log("Retiro automatico mal configurado para IDM: ".$retiro_aut->get_id_marchand());
                }
//                return $monto_retirar;
                break;

            default:
//                return null;
                $this->developer_log("sale por el defaul");
                break;
        }
            return $monto_retirar;
    }
    
    protected function obtener_comisiones($saldo_disponible,$retiro_aut) {
        $transaccion = new Transaccion();
        $this->developer_log($saldo_disponible);
        $saldos = $transaccion->calculo_directo($retiro_aut->get_id_marchand(), Mp::RETIROS, (float) $saldo_disponible);
        $comisiones= $saldos[1]+$saldos[2]+$saldos[4]+$saldos[5];
        
        return $this->floordec($comisiones,2);
    }
    
    function floordec($zahl,$decimals=2){   
         return floor($zahl*pow(10,$decimals))/pow(10,$decimals);
    }
    
    public function retirar($id_marchand, $monto,$retiro_aut){
       $transaccion = new Transaccion();
       $hoy = new DateTime('now');
       $hoy->format('Y-m-d');
       Model::StartTrans();
       $this->developer_log("Inicia el proceso de retiro en el traits Transaccion");
       if($transaccion->crear($id_marchand, Mp::RETIROS, $monto, $hoy, $retiro_aut->get_id_cbumarchand())){
            $this->developer_log("Se genero el retiro automatico de manera correcta");
            Model::CompleteTrans();
            return True;
       }else{
            $this->developer_log("No se pudo generar el retiro automatico");
            Model::FailTrans();
            return FALSE;
        }
    
    }
    
    protected function obtener_registros_a_procesar(){
        $rs = Retiro_automatico::obtener_retiros();
        $this->developer_log("obtiene registros a procesar");
        $this->developer_log("Tiene ".$rs->RowCount()." Registos");
        return $rs;
    }

        protected function developer_log($string) {
        $tabulacion = '';
        for ($i = 0; $i < self::$grado_de_recursividad - 1; $i++) {
            $tabulacion .= "+";
        }
        if ($tabulacion !== '') {
            $tabulacion .= " ";
        }
        if (self::ACTIVAR_DEBUG) {
            developer_log($tabulacion . $string);
        }
        $this->log[] = $tabulacion . $string;
    }
    
    protected function validar($id_cbumarchand) {
        
        $recordset = Retiro_automatico::select(array('id_cbumarchand'=>$id_cbumarchand,'id_authstat'=>1));
        
        if ($recordset->RowCount()>0){
            $arrRetiro = $recordset->FetchRow();
            $id_retiro = $arrRetiro['id_retiro'];
            $this->developer_log("id_retiro = ".$id_retiro);
            return $id_retiro;
        }
        return NULL;
        
    }
}

?>
