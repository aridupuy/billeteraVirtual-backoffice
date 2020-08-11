<?php

class Ordenador_servicios_pago extends Ordenador
{
    const ACTIVAR_DEBUG                     = true;
    const CANTIDAD_DE_ARCHIVOS_PROCESABLES  = 99999;
    const CANTIDAD_DE_REGISTROS_POR_ARCHIVO = 99999997;
    const MAXIMO_MONTO_TOTAL                = 999999999999999999;
    const DESACTIVAR_BDD                    = false;
    const LONGITUD_FILA                     = '131';
    const FORMATO_FECHA                     = 'ymd';
    const PREFIJO_ARCHIVO                   = "CD_CE";
    const NUM_TRANS                         = "num_trans";
    const FECHA_PAGO                        = "fecha_pago";
    const ID_SERV_EMP                       = "id_serv_emp";
    const MONTO_1                           = "importe";
    const MONTO_2                           = false;
    const MONTO_3                           = false;
    const ID_MOVES                          = "id_moves";
    const SC                                = "sc";
    const IDENTIFICADOR_CONCEPTO            = "";
    const ID_MP                             = Mp::PAGO_DE_SERVICIOS;
    const PROCESA_ARCHIVO_CONTROL           = false;
    const PERMITIR_ZIPEADO                  = false;
    const ID_BARCODE_1                      = "id_serv_pago";
    const CODIGO_DE_BARRAS                  = "id_moves";
    const FECHA_1                           = "fecha_pago";
    const PREFIJO_NOMBRE_ARCHIVO_CONTROL    = "CDCENOMB";
    const PREFIJO_HEADER_ARCHIVO_CONTROL    = "CDCEHEAD";
    const CODIGO_ENTE                       = "CD_CE";
    protected $importe_1                    = 0;
    protected $importe_2                    = 0;
    protected $importe_3                    = 0;
    protected $ultimo_vencimiento;
    protected $nro_archivo;
//    public $recordset;
    //$fecha_a_ordenar = new DateTime('now');
    protected function consultar_bdd(DateTime $fecha_a_ordenar, $nro_archivo)
    {
        developer_log("| Ordenador Pago de servicios Consultando datos espere...");

//         print_r($fecha_a_ordenar);
        $hoy = new DateTime('now');
        if($fecha_a_ordenar->format("Y-m-d")==$hoy->format("Y-m-d")){
            $fecha_a_ordenar->sub(new DateInterval('P1D'));
        }
        $recordset = Servicios_pago::select_ordenar_servi_pagos((self::CANTIDAD_DE_ARCHIVOS_PROCESABLES * self::CANTIDAD_DE_REGISTROS_POR_ARCHIVO) + 1);

        // print_r("<pre>");
        // print_r($recordset->FetchRow());
        // print_r("</pre>");

        if (!$recordset) {
            //print_r("NO HAY RECORDSET");
            return false;
        }

        developer_log("| Se han obtenido " . $recordset->rowCount() . " registros.");
//        $this->recordset = $recordset;
        return $recordset;
    }
    
    protected function calcular_monto_total($recordset, $nro_archivo, $current_position)
    {
        $current_row = $recordset->currentRow();
        $i           = 0;
        $monto_total = array($nro_archivo => 0);
        if (self::ACTIVAR_DEBUG) {
            developer_log("Obteniendo el monto total  comenzando por el archivo nro: $nro_archivo");
        }

//        $resultset->move(0);
        foreach ($recordset as $row) {
            if ($nro_archivo <= static::CANTIDAD_DE_ARCHIVOS_PROCESABLES and $i <= static::CANTIDAD_DE_REGISTROS_POR_ARCHIVO) {
                $monto_total[$nro_archivo] = floatval($row[static::MONTO_1]) + $monto_total[$nro_archivo];
                $i++;
            } else {
                $nro_archivo++;
                $monto_total[$nro_archivo] = 0;
                $i                         = 0;
            }

        }
        $recordset->move($current_row);
        //var_dump($monto_total);
        //exit();
        return $monto_total;
    }
    protected function nombrar_archivo(DateTime $hoy, $nro_archivo)
    {

        $nombre = self::PREFIJO_ARCHIVO . $hoy->format('Y') . $hoy->format('m') . $hoy->format('d').'.csv';

        // print_r("<pre>");
        // print_r($nombre);
        // print_r("</pre>");
        return $nombre;
    }
    protected function obtener_encabezado($nro_archivo)
    {
        //        $salto_de_linea = "\n";
        $fecha_generacion = new DateTime("now");
        $encabezado       = "COD_AGE;";
        $encabezado .= "FECHA_HORA;";
        $encabezado .= "COD_EMP;";

        $encabezado .= "NRO_OPERACION;";
        $encabezado .= "IMPORTE;";
        $encabezado .= "TRN_CLIENTE";
        $encabezado .= "\n";
        //        $encabezado.=$salto_de_linea;
        // print_r("<pre>");
        // print_r($encabezado);
        // print_r("</pre>");

        return $encabezado;
    }
    protected function obtener_fila($row)
    {
        $registro_serv_pago = new Registro_servi_pagos("");

        //Si no tiene segundo vencimiento pero si tiene el tercero este no se informara, solo se informara el primero
        $empresa = Servicios_emp::conseguir_cod_emp($row[self::ID_SERV_EMP]);
        $cod_emp = $empresa->FetchRow();
        // print_r("<pre>");
        // print_r($cod_emp['cod_emp']);
        // print_r("</pre>");
        if ((isset($row[self::MONTO_1]) and !empty($row[self::FECHA_PAGO]))) {
            $registro_serv_pago->generar_fila($row[self::FECHA_PAGO], $cod_emp['cod_emp'], $row[self::NUM_TRANS], $row[self::MONTO_1], $row[self::ID_MOVES]);

//            $serv_pago = new Servicios_pago();
//            $serv_pago->get($row[self::ID_SERV_EMP]);
//            $serv_pago->set_id_authstat(Authstat::DEBITO_ENVIADO);
//            $serv_pago->set();

        }
        // print_r("<pre>");
        // print_r($registro_link);
        // print_r("</pre>");
        return $registro_serv_pago;
    }
    protected function obtener_pie($nro_archivo)
    {
        //$fecha_generacion = new DateTime("now");
        $pie = "";

        return $pie;
    }
    protected function actualizar_deuda($row)
    {
        if (self::DESACTIVAR_BDD) {
            return true;
        }
        
//         print_r("<pre>");
//         print_r($row);
//         print_r("</pre>");
         $serv_pago = new Servicios_pago();
         $serv_pago->get($row['id_serv_pago']);
         $serv_pago->set_id_authstat(Authstat::TRANSACCION_COBRADO_POSTEADO);
         
        return $serv_pago->set();
    }
    # SUBIR ESTA FUNCION A ORDENADOR?
    private function obtener_pmc_deuda($fecha_1)
    {
        $fecha_referencia = DateTime::createFromFormat("!Y-m-d", self::FECHA_DE_REFERENCIA);
        $fecha_1          = new DateTime($fecha_1);
        $diferencia       = $fecha_referencia->diff($fecha_1);
        $dias             = $diferencia->format($diferencia->days);
        //        if(strlen($dias)<=5){
        $pmcdeuda = str_pad($dias, 5, "0", STR_PAD_LEFT);
        return $pmcdeuda;
        //        }
        //        return substr($string, $start);
    }
    protected function procesar_archivo_control(Datetime $fecha_a_ordenar, $archivos, $nombre)
    {

        return false;
    }
    
}
