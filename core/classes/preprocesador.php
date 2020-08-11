
<?php

abstract class Preprocesador {

    const ACTIVAR_DEBUG = true;
    const ACTIVAR_TEST = false; # Falla la transaccion siempre
    const PERMITIR_REGISTROS_UFO = true;
    const ARCHIVOS_DIARIOS = 1;
    const POSICION_ENCABEZADO = false; # Sobreescribir con numero si hace falta
    const CANTIDAD_DE_LINEAS_ENCABEZADO = 1;
    const CANTIDAD_DE_LINEAS_POR_REGISTRO = 1;
    const POSICIONES_FINALES_DESCARTABLES = false; # Contar lineas que tienen solo un salto de carro
    const CONTROL_POSICION_REGISTRO = false; # Positivo si empieza desde el final. Cero si es la primera linea.
    const DIFERENCIA_CONTROL = 0.01;
    const DUMMY_ID_BARCODE = 1;
    const DIRECTORIO_FTP = '/';

    private $log = false;
    private $codigo_entidad;
    private $codigo_archivo;
    protected $archivo = false; # Ruta absoluta
    protected $puntero_fichero = false;
    private $cantidad_de_lineas = false;
    protected $nlinea = false;
    protected $cantidad_de_registros = 0;
    protected $cantidad_de_registros_ufo = 0;
    protected $monto_acumulado = 0;
    protected $monto_acumulado_cobrado = 0; # Usada en los casos en que un medio de pago pueda acreditar o debitar
    protected $archivo_sin_comprimir;

    const CODIGO_ENTIDAD_GALICIA = '01';
    const CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION_DE_REVERSOS = '02';
    const CODIGO_ENTIDAD_RAPIPAGO = '02';
    const CODIGO_ENTIDAD_RAPIPAGO_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_PAGOFACIL = '03';
    const CODIGO_ENTIDAD_PAGOFACIL_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_PRONTOPAGO = '04';
    const CODIGO_ENTIDAD_PRONTOPAGO_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_PROVINCIAPAGO = '05';
    const CODIGO_ENTIDAD_PROVINCIAPAGO_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_RIPSA = '06';
    const CODIGO_ENTIDAD_RIPSA_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_COBROEXPRESS = '07';
    const CODIGO_ENTIDAD_COBROEXPRESS_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_PAGOMISCUENTAS = '08';
    const CODIGO_ENTIDAD_PAGOMISCUENTAS_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_MULTIPAGO = '09';
    const CODIGO_ENTIDAD_MULTIPAGO_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_BICA = '10';
    const CODIGO_ENTIDAD_BICA_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_LINKPAGOS = '11';
    const CODIGO_ENTIDAD_LINKPAGOS_ARCHIVO_RENDICION = '01';
    const CODIGO_ENTIDAD_CHEQUE_SUCURSAL = '12';

    const ALERTA_ASUNTO = 'Alerta de Preprocesamiento de Cobranzas';

    public function __construct($codigo_preprocesador) {
        $this->codigo_entidad = substr($codigo_preprocesador, 0, 2);
        $this->codigo_archivo = substr($codigo_preprocesador, 2, 2);
        return $this;
    }

    protected function pre_ejecucion($archivo) {
        # Para que Provincia pueda implementar el unzip
        return $archivo;
    }

    protected function post_ejecucion($archivo) {
        # Para que Provincia pueda eliminar el archivo temporal
        return $archivo;
    }

    public function ejecutar($archivo) {
        $tiempo_inicio = microtime(true);
        if (!is_file($archivo)) {
            $this->developer_log('Archivo incorrecto. ');
            return false;
        }
        if (!$this->verificar_inexistencia_archivo($archivo)) {
            return false;
        }
        if (!($clase_registro = $this->obtener_clase_registro($this->obtener_id_mp()))) {
            $this->developer_log('Clase de registro incorrecta.');
            return false;
        }
        $this->archivo = $archivo;
        unset($archivo);

        $archivo_pre_procesado = $this->pre_ejecucion($this->archivo);
        if (!$archivo_pre_procesado) {
            $this->developer_log('Ha ocurrido un error al pre procesar el archivo.');
            return false;
        }
        Model::StartTrans();
        $this->puntero_fichero = new SplFileObject($archivo_pre_procesado);
        $encabezado = trim($this->obtener_encabezado($this->puntero_fichero));

        $this->developer_log('*************************INICIO*********************************');
        $this->developer_log('Medio de Pago: ' . $this->obtener_entidad());
        $this->developer_log("Nombre archivo: '" . basename($this->archivo) . "'");
        $this->developer_log("Encabezado: '" . $encabezado . "'");
        $this->developer_log('Cantidad de lineas: ' . $this->get_cantidad_de_lineas($this->puntero_fichero));
        $this->developer_log('Día:' . date('d/m/Y') . ' Hora:' . date('H:i'));
        $this->developer_log('*****************************************************************');
        if (!Model::hasFailedTrans()) {
            if (!($control = $this->insertar_control($encabezado))) {
                $this->developer_log('Ha ocurrido un error al insertar el registro de Control.');
                Model::FailTrans();
            }
        }
        $this->nlinea = 0;
        unset($fila);
        while (!Model::hasFailedTrans() AND ( $fila = $this->obtener_registro_siguiente($this->puntero_fichero)) != false) {
            $this->nlinea = $this->puntero_fichero->key();
            unset($registro);
            if (($registro = new $clase_registro($fila))) {
                if (!$this->procesar_registro($registro, $control)) {
//                     var_dump(Model::hasFailedTrans());
                    Model::FailTrans();
                    $this->developer_log($this->nlinea . " | Ha ocurrido un error al procesar el registro. ");
                }
            } else {
                $this->developer_log($this->nlinea . " | El registro no es válido. ");
            }
            unset($fila);
        }
//        var_dump(Model::hasFailedTrans());
        if (!Model::hasFailedTrans()) {
            
            if (!$this->controlar()) {
                Model::FailTrans();
                $this->developer_log('Ha ocurrido un error al controlar el archivo.');
            }
        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Esto es un test. Fallando transacciones.');
            Model::FailTrans();
        }
        if (!Model::hasFailedTrans()) {
            if (!$this->post_ejecucion($this->archivo)) {
                Model::FailTrans();
            }
            if (!$this->archivar()) {
                Model::FailTrans();
            }
        }
        $tiempo_total = microtime(true) - $tiempo_inicio;
        $this->developer_log('Tiempo de preprocesamiento total: ' . $tiempo_total);

        $mensaje_aux = 'Hay un total de ' . $this->cantidad_de_registros_ufo . ' registro/s Ufo. ';
        $this->developer_log($mensaje_aux);
        $read_output = '';
        $read_output .= 'Importe total: $' . formato_plata($this->monto_acumulado) . '. ';
        $read_output .= 'Cantidad de registros: ' . $this->cantidad_de_registros . '. ';
        $read_output .= $mensaje_aux;
        $mensaje_aux_2 = "Todos los registros del archivo '" . basename($this->archivo) . "' han sido completados correctamente. ";
        $read_output .= $mensaje_aux_2;

        if (!Model::hasFailedTrans()) {
            if (!$this->actualizar_control($control, $read_output)) {
                $this->developer_log('Ha ocurrido un error al actualizar el control. ');
                Model::FailTrans();
            }
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            $this->developer_log($mensaje_aux_2);
            $this->developer_log('************************FIN CORRECTO*****************************');
            return true;
        }
        $this->developer_log("Ningún registro del archivo '" . basename($this->archivo) . "' ha sido completado correctamente. ");
        $this->developer_log('************************FIN INCORRECTO*****************************');
        if (!$this->enviar_alerta()) {
            developer_log('Ha ocurrido un error al enviar la alerta.');
        } else {
            developer_log('Alerta correctamente enviada.');
        }
        $this->developer_log('*******************************************************************');
        return false;
    }

    protected function verificar_inexistencia_archivo($archivo) {
        if (file_exists(PATH_ARCHIVO . basename($archivo))) {
            $this->developer_log("El fichero '" . basename($archivo) . "' ya existe en el directorio '" . PATH_ARCHIVO );
            return false;
        }
        return true;
    }

    private function insertar_control($encabezado) {
        $recordset = Control::select(array("tplfile" => $encabezado, "id_mp" => $this->obtener_id_mp()));
        if ($recordset->rowCount() == 0) {
            $this->developer_log("Insertando control. ");
            $control = new Control();
            $control->set_date_run('now');
            $control->set_success('0');
            $control->set_script(get_called_class());
            $control->set_id_mp($this->obtener_id_mp());
            $control->set_seq1(Control::SEQ1_NUEVO_PROCESADOR);
            $control->set_tplfile($encabezado);
            $control->set_csvfile($this->nombrar_csvfile($this->archivo)); # AGREGAR INDICE UNIQUE!
            if ($control->set()) {
                return $control;
            }
        }
        return false;
    }

    public function actualizar_control(Control $control, $read_output) {
        $control->set_read_output($read_output);
        if ($control->set()) {
            return $control;
        }
        return false;
    }

    public static function nombrar_csvfile($archivo) {
        return self::nombre_herencia($archivo);
    }
    public static function nombre_herencia($archivo){
        return basename($archivo);
    }	
    public function procesar_registro(Registro $registro, Control $control) {
        $this->cantidad_de_registros++;
        $this->monto_acumulado += $registro->obtener_monto_numerico();
        $this->monto_acumulado_cobrado += $registro->obtener_monto_numerico();

        if (!($barcode = $this->obtener_barcode($registro))) {
            if (!self::PERMITIR_REGISTROS_UFO) {
                return false;
            } else {
                $this->developer_log($this->nlinea . " | Nuevo código de barras no identificado: Ufo. ");
                $this->cantidad_de_registros_ufo++;
                $barcode = null; # Atento a esto
//                var_dump(Model::HasFailedTrans());
            }
        } else {
            # Codigo de barras encontrado
            # Solo Link y PagoMisCuentas necesitan esto, para el resto es indistinto
            $registro->set_codigo_de_barras($barcode->get_barcode());
        }
        if (!($sabana = $this->insertar_sabana($registro, $control, $barcode))) {
            $this->developer_log($this->nlinea . " | Ha ocurrido un error al Insertar la sabana en preprocesador. ");
            return false;
        }
//        var_dump(Model::HasFailedTrans());
        return true; # No esta bueno
    }

    protected function obtener_barcode(Registro $registro) {
        $codigo_de_barras = $registro->obtener_codigo_de_barras();
        $this->developer_log($this->nlinea . " | Obteniendo Barcode '" . $codigo_de_barras . "'. ");
        $recordset = Barcode::select(array('barcode' => $codigo_de_barras));
        if ($recordset AND $recordset->RowCount() == 1) {
            $barcode = new Barcode($recordset->FetchRow());
            return $barcode;
        }
        $this->developer_log($this->nlinea . " | No ha sido posible obtener el Barcode. ");
        return null;
    }

    protected function insertar_sabana(Registro $registro, Control $control, Barcode $barcode = null) {
        if ($barcode == null) {
            $this->developer_log("insertando ufo en sabana");
            # UFO
            $id_authstat = Authstat::SABANA_ORIGENUFO;
            $id_barcode = self::DUMMY_ID_BARCODE;
            $barcode2 = $this->obtener_barcode_para_ufos($registro); # METER CODIGO PMC
            $fecha_vto = new DateTime('now');
            $sc = '0';
            $barrand = '0';
        } else {

            $id_authstat = $registro->obtener_estado_a_insertar_sabana();
            $id_barcode = $barcode->get_id_barcode();
            $barcode2 = $barcode->get_barcode();
            $fecha_vto = $registro->obtener_fecha_de_vencimiento_datetime();
            $sc = $registro->obtener_segmento_comercial();
            $barrand = $registro->obtener_barrand();
        }
        $monto = $registro->obtener_monto_numerico();
        $fecha_pago = $registro->obtener_fecha_de_pago_datetime();
        $id_local_pf = $registro->obtener_id_local();
        $sabana = new Sabana();
        $this->developer_log($this->nlinea . " | Insertando sabana. ");
        $sabana->set_id_local_pf($id_local_pf);
        $sabana->set_id_authstat($id_authstat);
        $sabana->set_id_barcode($id_barcode);
        $sabana->set_id_mp($this->obtener_id_mp($registro));
        $sabana->set_barcode($barcode2);
        //var_dump($registro->obtener_fecha_de_vencimiento_datetime());
	//exit();
	//error_log("preprocesador.php:".$fecha_vto->format("Y-m-d"));
        if (!$fecha_vto) {
            $this->developer_log($this->nlinea . " | La fecha de vencimiento no es válida. ");
            return false;
        }
        $sabana->set_fecha_vto($fecha_vto->format('Y-m-d'));
        $sabana->set_monto($monto);
        $sabana->set_fechagen('now');
        $sabana->set_sc($sc);
        $sabana->set_barrand($barrand);

        if (!$fecha_pago) {
            $this->developer_log($this->nlinea . " | La fecha de pago no es válida. [1] ");
            return false;
        }
        $sabana->set_fecha_pago($fecha_pago->format('Y-m-d'));
        $sabana->set_id_formapago('1');
        $sabana->set_revno($control->get_revno());
        $sabana->set_nlinea($this->nlinea);
// 	developer_log(substr($barcode, 0,4));
	if( ($this->obtener_id_mp($registro)==Mp::DEBITO_AUTOMATICO_REVERSO AND $id_authstat!=Authstat::SABANA_DEBITO_REVERTIDO) OR $id_authstat==Authstat::SABANA_ORIGENUFO OR (is_string($barcode) and  substr($barcode2, 0,4)=="UFO:"))
		$sabana->set_id_marchand(1);
	else
 	       $sabana->set_id_marchand($barcode->get_id_marchand());
        try {
            $sabana->set_xml_extra($this->crear_xml_extra($registro));
        } catch (Exception $e) {
            developer_log($e->getMessage());
            return $sabana;
        }

        if ($sabana->set()) {
            return $sabana;
        }
        return false;
    }

    protected function crear_xml_extra(Registro $registro) {
        return '';
    }

    protected function obtener_barcode_para_ufos(Registro $registro) {
        return $registro->obtener_codigo_de_barras();
    }

    protected function controlar() {
        if (static::CONTROL_POSICION_REGISTRO === false) {
            $this->developer_log("No hay control.");
            return true;
        }
        $this->developer_log("Controlando total de registros e importe total. ");
        if (static::CONTROL_POSICION_REGISTRO > 0) {
            $this->puntero_fichero->seek($this->cantidad_de_registros - static::CONTROL_POSICION_REGISTRO + 2);
        } elseif (static::CONTROL_POSICION_REGISTRO === 0) {
            $this->puntero_fichero->seek(0);
        } else {
            return false;
        }
        $registro = $this->puntero_fichero->current();

        $cantidad_de_registros = intval(substr($registro, static::CONTROL_POSICION_CANTIDAD_DE_REGISTROS, static::CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS));
        if (static::CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS) {
            $cantidad_de_registros = $cantidad_de_registros - static::POSICION_ENCABEZADO * static::CANTIDAD_DE_LINEAS_ENCABEZADO - static::POSICIONES_FINALES_DESCARTABLES + 1;
        }
        $importe_total = substr($registro, static::CONTROL_POSICION_IMPORTE_TOTAL, static::CONTROL_LONGITUD_IMPORTE_TOTAL);
        $importe_total = intval($importe_total);
        $aux = pow(10, static::CONTROL_DECIMALES_IMPORTE_TOTAL);
        $importe_total = $importe_total / ($aux);
        $this->developer_log('Importe total: ' . $importe_total);
        $this->developer_log('Importe controlado: ' . $this->monto_acumulado);
        $this->developer_log('Cantidad de registros: ' . $cantidad_de_registros);
        $this->developer_log('Cantidad de registros controlado: ' . $this->cantidad_de_registros);

        if (($this->cantidad_de_registros == $cantidad_de_registros) AND ( abs($this->monto_acumulado - $importe_total) < static::DIFERENCIA_CONTROL)) {
            return true;
        }
        return false;
    }

    private function enviar_alerta() {
        developer_log("Enviando Alerta. ");
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = Gestor_de_correo::MAIL_DESARROLLO;
        $asunto = self::ALERTA_ASUNTO . ' | ' . $this->obtener_entidad();
        $mensaje = '';
        if ($this->log) {
            foreach ($this->log as $linea) {
                $mensaje .= $linea . '<br/>';
            }
        }
        return Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje);
    }

    protected function archivar() {
        $this->developer_log("Archivando fichero. ");
        $date = new DateTime('now');
        $year = $date->format("Y");
        if (file_exists(PATH_ARCHIVO . basename($this->archivo) .'.' . $year )) {
            # Hacer un throw de este mensaje
            $this->developer_log("El fichero '" . basename($this->archivo) . "' ya existe en el directorio '" . PATH_ARCHIVO . "'(2)");
            return false;
        }
        if (rename($this->archivo, PATH_ARCHIVO . basename($this->archivo .'.' . $year))) {
            $this->borrar_archivo_descomprimido();
            return true;
        }
        return false;
    }

    public function obtener_entidad() {
        $entidad = false;
        switch ($this->codigo_entidad) {
            case self::CODIGO_ENTIDAD_GALICIA: $entidad = 'Galicia';
                break;
            case self::CODIGO_ENTIDAD_RAPIPAGO: $entidad = 'Rapipago';
                break;
            case self::CODIGO_ENTIDAD_PAGOFACIL: $entidad = 'Pago Fácil';
                break;
            case self::CODIGO_ENTIDAD_PROVINCIAPAGO: $entidad = 'Provincia Pago';
                break;
            case self::CODIGO_ENTIDAD_RIPSA: $entidad = 'Ripsa';
                break;
            case self::CODIGO_ENTIDAD_COBROEXPRESS: $entidad = 'Cobro Express';
                break;
            case self::CODIGO_ENTIDAD_PAGOMISCUENTAS: $entidad = 'Pago Mis Cuentas';
                break;
            case self::CODIGO_ENTIDAD_MULTIPAGO: $entidad = 'Multi Pago';
                break;
            case self::CODIGO_ENTIDAD_BICA: $entidad = 'Bica';
                break;
            case self::CODIGO_ENTIDAD_LINKPAGOS: $entidad = 'Link Pagos';
                break;
            case self::CODIGO_ENTIDAD_PRONTOPAGO: $entidad = 'Pronto Pago';
                break;
            case self::CODIGO_ENTIDAD_CHEQUE_SUCURSAL: $entidad = 'Cheque por Sucursal';
                break;
        }
        return $entidad;
    }

    public static function obtener_clase_registro($id_mp) {
        $clase_registro = false;
        switch ($id_mp) {
            case Mp::DEBITO_AUTOMATICO: $clase_registro = 'Registro_galicia';
                break;
            case Mp::RAPIPAGO: $clase_registro = 'Registro_rapipago';
                break;
            case Mp::PAGOFACIL: $clase_registro = 'Registro_pagofacil';
                break;
            case Mp::PROVINCIAPAGO: $clase_registro = 'Registro_provinciapago';
                break;
            case Mp::RIPSA: $clase_registro = 'Registro_ripsa';
                break;
            case Mp::COBROEXPRESS: $clase_registro = 'Registro_cobroexpress';
                break;
            case Mp::PAGOMISCUENTAS: $clase_registro = 'Registro_pagomiscuentas';
                break;
            case Mp::MULTIPAGO: $clase_registro = 'Registro_multipago';
                break;
            case Mp::BICA: $clase_registro = 'Registro_bica';
                break;
            case Mp::LINKPAGOS: $clase_registro = 'Registro_linkpagos';
                break;
            case Mp::PRONTOPAGO: $clase_registro = 'Registro_prontopago';
                break;
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL: $clase_registro = 'Registro_cheque';
                break;
        }
        if (class_exists($clase_registro)) {
            return $clase_registro;
        }
        return false;
    }

    protected function obtener_id_mp($registro = null) {
        $id_mp = false;
        switch ($this->codigo_entidad) {
            # Esta parte es dudosa. Cada registro deberia deducir su id_mp
            case self::CODIGO_ENTIDAD_GALICIA:
                if ($registro === null) {
                    $id_mp = Mp::GALICIA;
                } else
                    $id_mp = $registro->obtener_id_mp();
                break;
            case self::CODIGO_ENTIDAD_RAPIPAGO: $id_mp = Mp::RAPIPAGO;
                break;
            case self::CODIGO_ENTIDAD_PAGOFACIL: $id_mp = Mp::PAGOFACIL;
                break;
            case self::CODIGO_ENTIDAD_PROVINCIAPAGO: $id_mp = Mp::PROVINCIAPAGO;
                break;
            case self::CODIGO_ENTIDAD_RIPSA: $id_mp = Mp::RIPSA;
                break;
            case self::CODIGO_ENTIDAD_COBROEXPRESS: $id_mp = Mp::COBROEXPRESS;
                break;
            case self::CODIGO_ENTIDAD_PAGOMISCUENTAS: $id_mp = Mp::PAGOMISCUENTAS;
                break;
            case self::CODIGO_ENTIDAD_MULTIPAGO: $id_mp = Mp::MULTIPAGO;
                break;
            case self::CODIGO_ENTIDAD_BICA: $id_mp = Mp::BICA;
                break;
            case self::CODIGO_ENTIDAD_LINKPAGOS: $id_mp = Mp::LINKPAGOS;
                break;
            case self::CODIGO_ENTIDAD_PRONTOPAGO: $id_mp = Mp::PRONTOPAGO;
                break;
//            case self::CODIGO_ENTIDAD_COBRADORES: $id_mp = Mp::COBRO_COBRADORES;
//                break;
            case self::CODIGO_ENTIDAD_CHEQUE_SUCURSAL: $id_mp = Mp::RETIROS_CHEQUE_POR_SUCURSAL;
                break;
        }
        return $id_mp;
    }

    protected function obtener_encabezado($puntero_fichero) {
        $linea_anterior = $puntero_fichero->key();
        if (static::POSICION_ENCABEZADO !== false) {
            $puntero_fichero->seek(static::POSICION_ENCABEZADO - 1);
            $encabezado = '';
            for ($i = 0; $i < static::CANTIDAD_DE_LINEAS_ENCABEZADO; $i++) {
                $encabezado .= $puntero_fichero->fgets();
            }
            $puntero_fichero->seek($linea_anterior);
        }
        return $encabezado;
    }

    protected function obtener_registro_siguiente($puntero_fichero) {
        $cantidad_de_lineas = $this->get_cantidad_de_lineas($puntero_fichero);
        $linea_actual = $puntero_fichero->key();
        if (static::POSICION_ENCABEZADO !== false AND $linea_actual == static::POSICION_ENCABEZADO - 1) {
            for ($i = 0; $i < static::CANTIDAD_DE_LINEAS_ENCABEZADO; $i++) {
                $this->developer_log(($linea_actual + 1 + $i) . ' | Salteando encabezado.');
                $puntero_fichero->current();
                $puntero_fichero->next();
            }
        }
        $linea_actual = $puntero_fichero->key();
        if (static::POSICIONES_FINALES_DESCARTABLES !== false) {
            if ($linea_actual == $cantidad_de_lineas - static::POSICIONES_FINALES_DESCARTABLES + 1) {
                # Fin de archivo encontrado
                $this->developer_log(($linea_actual + 1) . ' | Salteando última/s ' . static::POSICIONES_FINALES_DESCARTABLES . ' linea/s');
                return false;
            }
        }
        return $this->obtener_registro($puntero_fichero);
    }
    
    protected function obtener_registro($puntero_fichero) {
        $linea = '';
        for ($i = 0; $i < static::CANTIDAD_DE_LINEAS_POR_REGISTRO; $i++) {
            $linea .= $puntero_fichero->current();
            $puntero_fichero->next();
        }
        return $linea;
    }

    protected function get_cantidad_de_lineas($puntero_fichero) {
        if ($this->cantidad_de_lineas === false) {
            $linea_anterior = $puntero_fichero->key();
            $puntero_fichero->seek($puntero_fichero->getSize());
            $this->cantidad_de_lineas = $puntero_fichero->key();
            $puntero_fichero->seek($linea_anterior);
        }
        return $this->cantidad_de_lineas;
    }

    protected function developer_log($mensaje) {
        if (self::ACTIVAR_DEBUG) {
            developer_log($mensaje);
        }
        $this->log[] = $mensaje;
    }

    public static function nombre_archivo_interfaz($id_mp, $identificador, Datetime $fecha = null) {
        unset($id_mp);
        unset($identificador);
        return static::nombre_archivo($fecha);
    }

    public static function nombre_archivo(Datetime $fecha = null) {
        if ($fecha === null)
            $fecha = new Datetime('now');
        $nombre_archivo = preg_replace("/\((.*)\)/", $fecha->format(static::PATRON_FECHA), substr(static::PATRON, 2, -1));
        return $nombre_archivo;
    }

    public static function obtener_clase_preprocesador_nombre_archivo($nombre_archivo) {
        $id_mp = false;
        if (preg_match(Preprocesador_galicia::PATRON, $nombre_archivo)) {
            $id_mp = Mp::GALICIA;
        }
        elseif (preg_match(Preprocesador_galicia_reporte::PATRON, $nombre_archivo)) {
            $id_mp = Mp::FAKE_GALICIA_RPTE;
        } elseif (preg_match(Preprocesador_galicia::PATRON_REVERSOS, $nombre_archivo)) {
            $id_mp = Mp::DEBITO_AUTOMATICO_REVERSO;
        } elseif (preg_match(Preprocesador_rapipago::PATRON, $nombre_archivo)) {
            $id_mp = Mp::RAPIPAGO;
        } elseif (preg_match(Preprocesador_rapipago::PATRON_ZIPEO, $nombre_archivo)) {
            $id_mp = Mp::RAPIPAGO;
        } elseif (preg_match(Preprocesador_pagofacil::PATRON, $nombre_archivo)) {
            $id_mp = Mp::PAGOFACIL;
        } elseif (preg_match(Preprocesador_provinciapago::PATRON, $nombre_archivo)) {
            $id_mp = Mp::PROVINCIAPAGO;
        } elseif (preg_match(Preprocesador_ripsa::PATRON, $nombre_archivo)) {
            $id_mp = Mp::RIPSA;
        } elseif (preg_match(Preprocesador_multipago::PATRON, $nombre_archivo)) {
            $id_mp = Mp::MULTIPAGO;
        } elseif (preg_match(Preprocesador_pagomiscuentas::PATRON, $nombre_archivo)) {
            $id_mp = Mp::PAGOMISCUENTAS;
        } elseif (preg_match(Preprocesador_bica::PATRON, $nombre_archivo)) {
            $id_mp = Mp::BICA;
        } elseif (preg_match(Preprocesador_cobroexpress::PATRON, $nombre_archivo)) {
            $id_mp = Mp::COBROEXPRESS;
        } elseif (preg_match(Preprocesador_prontopago::PATRON, $nombre_archivo)) {
            $id_mp = Mp::PRONTOPAGO;
        } elseif (preg_match(Preprocesador_linkpagos::PATRON, $nombre_archivo)) {
            $id_mp = Mp::LINKPAGOS;
        } elseif (preg_match(Preprocesador_cheques::PATRON, $nombre_archivo)) {
            $id_mp = Mp::RETIROS_CHEQUE_POR_SUCURSAL;
        }

        if ($id_mp) {
            return self::obtener_clase_preprocesador($id_mp);
        }
        # TEMP
        if ($id_mp == Mp::DEBITO_AUTOMATICO_REVERSO OR $id_mp == Mp::GALICIA) {
            return $id_mp;
        }
        return false;
    }

    public static function obtener_clase_preprocesador($id_mp) {
//                developer_log($id_mp);
        $clase_preprocesador = false;
        switch ($id_mp) {
            case Mp::GALICIA: $clase_preprocesador = 'Preprocesador_galicia';
                break;
            case Mp::DEBITO_AUTOMATICO_REVERSO: $clase_preprocesador = 'Preprocesador_galicia';
                break;
            case Mp::RAPIPAGO: $clase_preprocesador = 'Preprocesador_rapipago';
                break;
            case Mp::PAGOFACIL: $clase_preprocesador = 'Preprocesador_pagofacil';
                break;
            case Mp::PROVINCIAPAGO: $clase_preprocesador = 'Preprocesador_provinciapago';
                break;
            case Mp::RIPSA: $clase_preprocesador = 'Preprocesador_ripsa';
                break;
            case Mp::COBROEXPRESS: $clase_preprocesador = 'Preprocesador_cobroexpress';
                break;
            case Mp::PAGOMISCUENTAS: $clase_preprocesador = 'Preprocesador_pagomiscuentas';
                break;
            case Mp::MULTIPAGO: $clase_preprocesador = 'Preprocesador_multipago';
                break;
            case Mp::BICA: $clase_preprocesador = 'Preprocesador_bica';
                break;
            case Mp::LINKPAGOS: $clase_preprocesador = 'Preprocesador_linkpagos';
                break;
            case Mp::PRONTOPAGO: $clase_preprocesador = 'Preprocesador_prontopago';
                break;
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL: $clase_preprocesador = 'Preprocesador_cheques';
                break;
            case Mp::FAKE_GALICIA_RPTE: $clase_preprocesador = 'Preprocesador_galicia_reporte';
                break;
        }
        if (class_exists($clase_preprocesador)) {
            return $clase_preprocesador;
        }
        return false;
    }

    public static function obtener_archivos_del_dia(Datetime $dia, $id_mp) {

        $agendas = array();
        $agendas[Mp::RAPIPAGO] = array('dias' => array(1, 2, 3, 4, 5, 6, 7), 'archivo_dia' => 0, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::PAGOFACIL] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => 0, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::BICA] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::RIPSA] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => array(6, 7), 'archivos_diarios' => 1);
        $agendas[Mp::PRONTOPAGO] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => 0, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::PROVINCIAPAGO] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => 0, 'arrastrar_dias' => false, 'archivos_diarios' => array('96', '240', '480')); # TEMP! 480
        $agendas[Mp::MULTIPAGO] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => array(6), 'archivos_diarios' => 1);
        $agendas[Mp::COBROEXPRESS] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => 0, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::LINKPAGOS] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::PAGOMISCUENTAS] = array('dias' => array(2, 3, 4, 5, 6), 'archivo_dia' => -1, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::DEBITO_AUTOMATICO] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => false, 'archivos_diarios' => 1);
        $agendas[Mp::DEBITO_AUTOMATICO_REVERSO] = array('dias' => array(1, 2, 3, 4, 5), 'archivo_dia' => -1, 'arrastrar_dias' => false, 'archivos_diarios' => 1);

        if (!isset($agendas[$id_mp])) {
            return false;
        }
        $agenda = $agendas[$id_mp];

        $archivos = array();
        $dia_semana = $dia->format('N');
        if (in_array($dia_semana, $agenda['dias']) !== false) {
            $fecha_de_archivo = clone $dia;
            if (!isset($agenda['archivo_dia']))
                $agenda['archivo_dia'] = 0;

            if ($agenda['archivo_dia'] > 0) {
                $fecha_de_archivo->add(new DateInterval('P' . $agenda['archivo_dia'] . 'D'));
            } elseif ($agenda['archivo_dia'] < 0) {
                $fecha_de_archivo->sub(new DateInterval('P' . abs($agenda['archivo_dia']) . 'D'));
                while (in_array($fecha_de_archivo->format('N'), $agenda['dias']) === false) {
                    $fecha_de_archivo->sub(new DateInterval('P1D'));
                }
            }
            $clase_preprocesador = Preprocesador::obtener_clase_preprocesador($id_mp);

            $archivos_diarios = count($agenda['archivos_diarios']);
            for ($i = 0; $i < $archivos_diarios; $i++) {
                if (isset($agenda['archivos_diarios'][$i])) {
                    $identificador = $agenda['archivos_diarios'][$i];
                } else
                    $identificador = false;
                if($id_mp==Mp::RAPIPAGO)
                    $archivo = $clase_preprocesador::nombre_archivo_interfaz($id_mp, $identificador, $fecha_de_archivo,true);
                else
                    $archivo = $clase_preprocesador::nombre_archivo_interfaz($id_mp, $identificador, $fecha_de_archivo);
	       error_log("adupuy | ".$archivo);
                $archivos[] = $archivo;
            }

            if ($agenda['arrastrar_dias'] !== false) {
                $fecha_de_archivo->add(new DateInterval('P1D'));
                while (in_array($fecha_de_archivo->format('N'), $agenda['arrastrar_dias']) !== false) {
                    for ($i = 0; $i < $archivos_diarios; $i++) {
                        if (isset($agenda['archivos_diarios'][$i])) {
                            $identificador = $agenda['archivos_diarios'][$i];
                        } else
                            $identificador = false;
                        $archivo = $clase_preprocesador::nombre_archivo_interfaz($id_mp, $identificador, $fecha_de_archivo);
                        $archivos[] = $archivo;
                    }
                    $fecha_de_archivo->add(new DateInterval('P1D'));
                }
            }
        }
        return $archivos;
    }

    public static function obtener_directorio_ftp($nombre_archivo = null) {
        # En multipago el directorio es dinamico
        return static::DIRECTORIO_FTP;
    }

    public static function get_nombre_archivo_guardado($server_file) {
        return PATH_PROCESOS . basename($server_file);
    }

    public function borrar_archivo_descomprimido() {
        return false;
    }

}
