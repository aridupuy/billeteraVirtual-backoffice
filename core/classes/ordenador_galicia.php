<?php

class ordenador_galicia extends Ordenador {

    const ACTIVAR_DEBUG = true;
    const DESACTIVAR_BDD = false; //CAMBIAR ESTO LUEGO DE LA PRUEBA DE MAÑANA
    const CANTIDAD_DE_ARCHIVOS_PROCESABLES = 99999;
    const CANTIDAD_DE_REGISTROS_POR_ARCHIVO = 8000;
    const MAXIMO_MONTO_TOTAL = 99999999999999;
    const PERMITIR_MENOR_TIEMPO = true;
    const INTERVALO_ACEPTABLE = "72";
    ########configurador de encabezado##############
    const TIPO_REGISTRO_HEADER = "0000";
    const TIPO_REGISTRO_FOOTER = "9999";
    const NRO_PRESTACION = "8496";
    const SERVICIO = "C";
    const IDENTIFICACION_ARCHIVO = "1"; #hacer algo para permitir mas archivos
    const IDENTIFICACION_CLIENTE = "id_clima"; #identificacion de los clientes
    const CBU = "cbu"; #identificacion de los clientes
    const ORIGEN = "EMPRESA";
    const LONGITUD_IMPORTE = "14";
    const LONGITUD_CANT_FILAS = "7";
    const TIPO_DOC = "id_tipodoc";
    const DOCUMENTO = "documento";
    const CODIGO_DE_BARRAS = "barcode";
    const CONCEPTO_FACTURA = "concepto";
    const MENSAJE_ATM = "mensaje_atm";
    const IMPORTE_MINIMO = "importe_minimo";
    const FORMATO_FECHA_INPUT = "!Y-m-d";
    #################################################
    const LONGITUD_FILA = '350';
    const FORMATO_FECHA = 'Ymd';
    const ID_BARCODE_1 = "id_debito";
    const ID_BARCODE_2 = "id_debito";
    const ID_BARCODE_3 = "id_debito";
    const MONTO_1 = "monto_1";
    const FECHA_1 = "fecha_1";
    const MONTO_2 = "monto_2";
    const FECHA_2 = "fecha_2";
    const MONTO_3 = "monto_3";
    const FECHA_3 = "fecha_3";
    const REFERENCIA_UNIVOCA = "referencia_univoca"; //aqui va lo que la persona ve en el tiquet
    const LONGITUD_REFERENCIA_UNIVOCA = "14";
    const ALINEACION_REFERENCIA_UNIVOCA = "0"; #STR_PAD_LEFT
    const RELLENO_REFERENCIA_UNIVOCA = "0";
    const RELLENO_IDENTIFICADOR_CLIENTE = ' '; #espacio#
    const ALINEACION_IDENTIFICADOR_CLIENTE = '1'; #STR_PAD_rigth
    const LONGITUD_IDENTIFICADOR_CLIENTE = "22";
    const LONGITUD_CBU_PARTE_1 = "9";
    const LONGITUD_CBU_PARTE_2 = "13";
    const LONGITUD_CBU_DIGITO_VERIFICADOR_1 = "4";
    const LONGITUD_CBU_DIGITO_VERIFICADOR_2 = "1";
    const LONGITUD_REFERENCIA_CBU = "24";
    const RELLENO_CBU = "0";
    const ALINEACION_CBU = '0'; #STR_PAD_LEFT
    const FECHA_GEN = "fechagen";
    const FECHA = "fechagen"; #deben ser el mismo
    const SC = "sc"; #necesario para sabana
    const BARRAND = "barrand"; #necesario para sabana
    const ID_MP = Mp::DEBITO_AUTOMATICO;
    const PROCESA_ARCHIVO_CONTROL = FALSE;
    const PERMITIR_ZIPEADO = false;
    const ACTIVAR_MONTOS = true;
    const ACTIVAR_FECHAS = true;
    const ID_MARCHAND = "id_marchand";
    const TAG_PADRE = "debito";
    const TAG_PORCENTAJE_MINIMO = "porcentajeminimo";
    const TAG_VENCIMIENTOS = "vencimientos";
    const TAG_DIAS = "diashabiles";
    const TAG_PORCENTAJE_VENCIMIENTOS = "porcentajevencimientos";
    const CANTIDAD_DE_DIAS_ANTICIPACION=2;

    private $xml;
    private $config_marchand;
    private $montos;

    protected function consultar_bdd(DateTime $fecha_a_ordenar, $nro_archivo) {
        developer_log("| Ordenador Galicia. Consultando débitos. Espere...");
        $hoy = new DateTime("now");
        //$fecha_a_ordenar=$this->siguiente_dia_habil($fecha_a_ordenar,self::CANTIDAD_DE_DIAS_ANTICIPACION);
        $recordset = Debito_cbu::select_debitos_a_procesar($hoy, (self::CANTIDAD_DE_ARCHIVOS_PROCESABLES * self::CANTIDAD_DE_REGISTROS_POR_ARCHIVO) + 1);
        if ($recordset == false) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error en la consulta del archivo " . $nro_archivo . ".");
            return false;
        }

        if ($recordset->RowCount() == 0) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Ya no hay debitos que deban ser informados para el archivo " . $nro_archivo . ".");
            return $recordset;
        }

        developer_log("| Se obtuvieron " . $recordset->rowCount() . " Débitos.");
        return $recordset;
    }

    private function siguiente_dia_habil(Datetime $fecha, $cantidad_de_dias = 1) {
//        if(Calendar::es_dia_habil($fecha))
//            return $fecha;
        return Calendar::siguiente_dia_habil($fecha, $cantidad_de_dias);
    }

    protected function nombrar_archivo(DateTime $hoy, $nro_archivo) {

        $nombre = "galicia2_" . $hoy->format('Ymd') . "_" . ($nro_archivo) . ".txt";
        if (!$nombre) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se puede nombrar el archivo.");
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("Nombre asignado: " . $nombre);
        return $nombre;
    }

    protected function obtener_fila($row) {
        $registro_galicia = new Registro_galicia();
        $mensaje_atm = $importe_minimo = null;
        $fecha_2 = null;
        $fecha_3 = null;
        $monto_2 = null;
        $monto_3 = null;
        $importe_minimo = $row["monto_minimo"];

        if (isset($row[self::MENSAJE_ATM]) AND $row[self::MENSAJE_ATM] != "" AND $row[self::MENSAJE_ATM] != false) {
            $mensaje_atm = substr("A orden de " . $row[self::MENSAJE_ATM], 0, 40);
        }
        $fecha_datetime_1 = DateTime::createFromFormat(self::FORMATO_FECHA_INPUT, $row[self::FECHA_1]);
        if (isset($row["fecha_2"]) AND ( isset($row["monto_2"]))) {
            $fecha_datetime_2 =Datetime::createFromFormat('Y-m-d',$row["fecha_2"]);
            $diff = $fecha_datetime_2->diff($fecha_datetime_1);
            $intervalo = new DateInterval("PT" . self::INTERVALO_ACEPTABLE . "H");
            if (self::PERMITIR_MENOR_TIEMPO AND isset($fecha_datetime_2) AND $diff >= $intervalo) {
                $fecha_2 = $fecha_datetime_2->format(self::FORMATO_FECHA);
                $monto_2 = $row["monto_2"];
            }
        }
        if (isset($row["fecha_3"]) AND isset($row["monto_3"])) {
            $fecha_datetime_3 =Datetime::createFromFormat('Y-m-d',$row["fecha_3"]);
            $diff = $fecha_datetime_3->diff($fecha_datetime_2);
            $intervalo = new DateInterval("PT" . self::INTERVALO_ACEPTABLE . "H");
            if (self::PERMITIR_MENOR_TIEMPO AND isset($fecha_datetime_3) AND $diff >= $intervalo) {
                $fecha_3 = $fecha_datetime_3->format(self::FORMATO_FECHA);
                $monto_3 = $row["monto_3"];
            }
        }
        $registro_galicia = $registro_galicia->generar_fila($row[self::MONTO_1], $row[self::IDENTIFICACION_CLIENTE], $row[self::CBU], $row[self::REFERENCIA_UNIVOCA], $row[self::FECHA_1], $row[self::TIPO_DOC], $row[self::DOCUMENTO], $row[self::CONCEPTO_FACTURA], $importe_minimo, $mensaje_atm, $monto_2, $fecha_2, $monto_3, $fecha_3);
        if ($registro_galicia)
            return $registro_galicia;
        return false;
    }

    protected function obtener_encabezado($nro_archivo) {
        if (self::ACTIVAR_DEBUG)
            developer_log("| generando encabezado.");
        $salto_de_linea = "\n";
        $fecha_generacion = new DateTime("now");
        $encabezado = self::TIPO_REGISTRO_HEADER . self::NRO_PRESTACION . self::SERVICIO;
        $encabezado .= $fecha_generacion->format(self::FORMATO_FECHA);
        $encabezado .= self::IDENTIFICACION_ARCHIVO . self::ORIGEN;
//        var_dump($nro_archivo);
//        var_dump($this->monto_total);
        $importe = number_format($this->monto_total[$nro_archivo], 2);
        $importe = str_replace(",", "", $importe);
        $importe = str_replace(".", "", $importe);
        $encabezado .= str_pad($importe, (self::LONGITUD_IMPORTE), '0', STR_PAD_LEFT);
        $encabezado .= str_pad($this->cantidad_registros, (self::LONGITUD_CANT_FILAS), '0', STR_PAD_LEFT);
        $encabezado = str_pad($encabezado, self::LONGITUD_FILA, " ", STR_PAD_RIGHT);
        $encabezado .= $salto_de_linea;
        if ($encabezado == "" or ! $encabezado) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se pudo generar el encabezado.");
            Model::FailTrans();
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("| Encabezado creado correctamente.");
        return $encabezado;
    }

    protected function obtener_pie($nro_archivo) {
        if (self::ACTIVAR_DEBUG)
            developer_log("| Creando pie.");
        $salto_de_linea = "\n";
        $fecha_generacion = new DateTime("now");
        $pie = self::TIPO_REGISTRO_FOOTER . self::NRO_PRESTACION . self::SERVICIO;
        $pie .= $fecha_generacion->format(self::FORMATO_FECHA);
        $pie .= self::IDENTIFICACION_ARCHIVO . self::ORIGEN;
        $importe = number_format($this->monto_total[$nro_archivo], 2);
        $importe = str_replace(",", "", $importe);
        $importe = str_replace(".", "", $importe);
        $pie .= str_pad($importe, (self::LONGITUD_IMPORTE), '0', STR_PAD_LEFT);
        $pie .= str_pad($this->cantidad_registros, (self::LONGITUD_CANT_FILAS), '0', STR_PAD_LEFT);
        $pie = str_pad($pie, self::LONGITUD_FILA, " ", STR_PAD_RIGHT);
        $pie .= $salto_de_linea;
        if ($pie == "" or ! $pie) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se pudo generar el pie.");
            Model::FailTrans();
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("| Pie creado correctamente.");
        return $pie;
    }

    protected function actualizar_deuda($row) {
        if (self::DESACTIVAR_BDD)
            return true;
        $debito_cbu = new Debito_cbu();
        $debito_cbu->set_id_debito($row['id_debito']);
        $debito_cbu->set_id_authf1(Authstat::DEBITO_ENVIADO);
        //$debito_cbu->set_id_authf2(Authstat::DEBITO_ENVIADO);
        //$debito_cbu->set_id_authf3(Authstat::DEBITO_ENVIADO);
        if (!$debito_cbu->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("||No se pudo actualizar Debito_cbu.");
            return false;
        }
        return true;
    }
    final function insertar_sabana($row, $nlinea, $registro, $revno) {
         if (self::DESACTIVAR_BDD_SABANA)
            return true;
        $sabana = new Sabana();
        $sabana->set_id_barcode(1);
        $sabana->set_barcode($row[self::REFERENCIA_UNIVOCA]);
        $sabana->set_fechagen('now');
        $sabana->set_id_authstat(Authstat::DEBITO_ENVIADO);
        $sabana->set_fecha_vto($row[STATIC::FECHA_1]);
        $sabana->set_revno($revno);
        $sabana->set_nlinea($nlinea);
        $sabana->set_monto($row[STATIC::MONTO_1]);
        $sabana->set_sc(2040);
        $sabana->set_barrand(1);
        $sabana->set_id_mp(static::ID_MP);
        $sabana->set_xml_extra($registro->fila);
        $sabana->set_id_formapago('1'); # TEMP PARA IDENTIFICAR NUEVOS PROCESADOR Y ORDENADOR
        if ($sabana->set()) {
            return true;
        }
        if (static::ACTIVAR_DEBUG)
            developer_log("|| Error al insertar sabana.");
        return false;
    }
    protected function procesar_archivo_control(DateTime $fecha_a_ordenar, $archivos, $nombre) {
        parent::procesar_archivo_control($fecha_a_ordenar, $archivos, $nombre);
    }

    public function proximo_dia_habil($dias, DateTime $fecha_datetime) {
        $fecha_datetime_2 = clone $fecha_datetime;
        $fecha_datetime_2 = $this->obtener_fechas_habiles($fecha_datetime, $fecha_datetime_2, $dias);
        return $fecha_datetime_2;
    }

    public function obtener_fechas_habiles(DateTime $fecha1, DateTime $fecha_objetivo, $dias, $dias_habiles = 0) {
        if ($dias == $dias_habiles) {
            if (Calendar::es_dia_habil($fecha_objetivo))
                return $fecha_objetivo;
        }
//    var_dump($dias);
//    var_dump($dias_habiles);
        if (Calendar::es_dia_habil($fecha_objetivo)) {
            $dias_habiles += 1;
        }
        $fecha_objetivo->add(new DateInterval("P1D"));
        return $this->obtener_fechas_habiles($fecha1, $fecha_objetivo, $dias, $dias_habiles);
    }

}
