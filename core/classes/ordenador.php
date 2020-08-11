<?php

abstract class Ordenador {

    const ACTIVAR_PORCENTAJES = true;
    const DESACTIVAR_BDD_CONTROL = false;
    const DESACTIVAR_BDD_SABANA = false;
    const SEGUNDOS = 5;
    const SEG_TOTAL = 15;
    const FECHA_DE_REFERENCIA = "2016-01-01";
    const TAMAÑO_MINIMO_ZIPEO = 7340032; // 7mb
    const PERMITIR_ZIPEADO = true;
    const ACTIVAR_DEBUG = true;

    protected $cantidad_registros = 0;
    protected $monto_total = array();
    protected $mostrar = true;
    protected $mostrar_todo = true;

    final function ordenar(datetime $fecha_a_ordenar = null) {
        if ($fecha_a_ordenar == null)
            $fecha_a_ordenar = new DateTime("now");
        $nro_archivo = 1;
        $recordset = $this->consultar_bdd($fecha_a_ordenar, $nro_archivo);
        if (!$recordset OR $recordset->RowCount() == 0) {
            return null;
        }
        $archivos = array();
        $nombre = array(array());
        if ($recordset->rowCount() > (STATIC::CANTIDAD_DE_REGISTROS_POR_ARCHIVO * STATIC::CANTIDAD_DE_ARCHIVOS_PROCESABLES)) {
            Gestor_de_log::set("La cantidad de registros a exportar supera al limite impuesto para esta operación, no se puede continuar.", 0);
            if (CONSOLA)
            //envia alerta ya que no se puede procesar el archivo
                $this->enviar_alerta($nombre[$nro_archivo], $nro_archivo);
            return false;
        }
        Model::StartTrans();
        $terminar = false;

        $progreso = 0;
        $recordset->move(0);
        while ($terminar == false AND ( $nro_archivo = $this->obtener_siguiente_nro_archivo($fecha_a_ordenar, $nro_archivo)) != false AND ! Model::HasFailedTrans()) {
            if (static::ACTIVAR_DEBUG)
                developer_log("| Nro de archivo a generar es $nro_archivo");
            $this->cantidad_registros = $this->cantidad_total_archivo(($recordset->rowCount() - $recordset->currentRow()));
            $this->monto_total = $this->calcular_monto_total($recordset, $nro_archivo, $recordset->CurrentRow());
            $archivo = "";
            $nombre[$nro_archivo] = $this->nombrar_archivo($fecha_a_ordenar, $nro_archivo);
            if (!($archivo = $this->obtener_encabezado($nro_archivo))) {
                if (static::ACTIVAR_DEBUG)
                    developer_log("|| Error, no se pudo generar el encabezado fila $fila");
                Model::FailTrans();
            }
            if (!($control = $this->insertar_control($fecha_a_ordenar, $nombre[$nro_archivo], $nro_archivo, $archivo))) {
                if (static::ACTIVAR_DEBUG)
                    developer_log("|| Error, no se pudo insertar el control");
                Model::FailTrans();
            }
            $fila = 1;
            if (static::ACTIVAR_DEBUG)
                developer_log("| Procesando archivo $nro_archivo");
            while (!Model::HasFailedTrans() AND $fila <= Static::CANTIDAD_DE_REGISTROS_POR_ARCHIVO AND $this->monto_total > STATIC::MAXIMO_MONTO_TOTAL AND ( $row = $recordset->FetchRow()) != false) {
                if (!($registro = $this->obtener_fila($row)) AND ! Model::HasFailedTrans()) {
                    if (static::ACTIVAR_DEBUG)
                        developer_log("|| Error, no se pudo obtener la fila $fila Correspondiente al PMC" . $row[static::IDENTIFICACION_CLIENTE]);
                    Model::FailTrans();
                }
                if (!Model::HasFailedTrans())
                    if (!$this->insertar_orden($row, $fila, $registro, $control->get_revno())) {
                        if (static::ACTIVAR_DEBUG)
                            developer_log("|| Error, no se pudo insertar la sabana, $fila");
                        Model::FailTrans();
                    }
                $archivo .= $registro->fila;
                if (!($this->actualizar_deuda($row))) {
                    if (static::ACTIVAR_DEBUG) {
                        //developer_log("|| Deuda: " . $row[Static::IDENTIFICACION_CLIENTE] . " Con 1er Vencimiento : " . $row[static::FECHA_1] . " con Monto: " . $row[static::MONTO_1]);
                        developer_log("|| Error, no se pudo actualizar");
                    }
                    Model::FailTrans();
                }
                $fila++;
                $progreso++;
                if (self::ACTIVAR_PORCENTAJES) {
                    $this->mostrar_progreso($fila, $progreso, $nro_archivo, $recordset->RowCount());
                }
            }
            if ($row == false)
                $terminar = true;
            $archivo .= $this->obtener_pie($nro_archivo);
            $archivos[$nro_archivo] = $archivo;
            $nro_archivo++;
        }
        $url = false;
        if (!Model::HasFailedTrans()) {
            if (STATIC::PROCESA_ARCHIVO_CONTROL)
                list($archivos, $nombre) = $this->procesar_archivo_control($fecha_a_ordenar, $archivos, $nombre);
            $url = $this->exportar_archivos($archivos, $nombre);
            if ($url !== false) {
                if (Model::HasFailedTrans()) {
                    Gestor_de_log::set("Ha ocurrido un error.");
                    return false;
                }
                developer_log("Ejecutado correctamente " . $recordset->rowCount() . " Registros");
                Gestor_de_log::set("| Ejecutado correctamente.", 1);
                Model::CompleteTrans();
                return $url;
            }
            Gestor_de_log::set("Ha ocurrido un error al guardar el archivo.");
            Model::fallar_transacciones_pendientes();
            Model::CompleteTrans();
            return false;
        }
        //envia alerta por que tiene transacciones fallidas.
        if (CONSOLA)
            $this->enviar_alerta($nombre[$nro_archivo], $nro_archivo);
        return $url;
    }

    private function obtener_siguiente_nro_archivo(DateTime $fecha_a_ordenar, $nro_archivo) {
        if (static::ACTIVAR_DEBUG)
            developer_log("Comprobando existencia archivo nro $nro_archivo");
        $recordset = Control::preexistencia_archivo_ordenador(static::ID_MP, $fecha_a_ordenar, $nro_archivo);
        $row = $recordset->FetchRow();
        if ($row['cantidad'] != 0) {
            if (static::ACTIVAR_DEBUG)
                developer_log("Archivo nro $nro_archivo ya existe, probando el siguiente " . ($nro_archivo + 1));
            $nro_archivo++;
            $nro_archivo = $this->obtener_siguiente_nro_archivo($fecha_a_ordenar, $nro_archivo);
        }
        if ($nro_archivo > static::CANTIDAD_DE_ARCHIVOS_PROCESABLES OR ! $nro_archivo)
            return false;
        return $nro_archivo;
    }

    final function insertar_control($fecha_a_ordenar, $nombre_archivo, $nro_archivo, $encabezado) {

        if (static::ACTIVAR_DEBUG) {
            developer_log("| Insertando control. para  " . $nombre_archivo);
        }
        $control = new Control();
        if (null == $fecha_a_ordenar)
            $control->set_date_run('now()');
        else
            $control->set_date_run($fecha_a_ordenar->format('Y-m-d'));
        $control->set_success('0');
        $control->set_script(get_called_class());
        $control->set_id_mp(static::ID_MP);
        $control->set_seq1($nro_archivo); // $nro del archivo procesado puede traer problemas con el nuevo esquema de constrints en control
        $control->set_tplfile(substr($encabezado, 0, 253) . "_" . $nro_archivo);
        $control->set_csvfile($nombre_archivo); # AGREGAR INDICE UNIQUE!
        if (self::DESACTIVAR_BDD_CONTROL) {
            $control->set_id(rand(1, 1000));
            return $control;
        }
        if ($control->set()) {
            if (static::ACTIVAR_DEBUG)
                developer_log("| Control insertado correctamente.");
            return $control;
        } else
            return false;
    }

    function insertar_orden($row, $nlinea, $registro, $revno) {
        if (self::DESACTIVAR_BDD_SABANA)
            return true;
        $ordenador = new Csv_ordenador();
        $ordenador ->set_id_entidad(Entidad::ENTIDAD_BARCODE);
        $ordenador ->set_id_referencia($row[static::ID_BARCODE_1]);
        $ordenador ->set_referencia_univoca($row[static::CODIGO_DE_BARRAS]);
        $ordenador ->set_fechagen('now');
        $ordenador ->set_id_authstat(Authstat::DEBITO_ENVIADO);
        $ordenador ->set_fecha_vto($row[STATIC::FECHA_1]);
        $ordenador ->set_revno($revno);
        $ordenador ->set_nlinea($nlinea);
        $ordenador ->set_monto($row[STATIC::MONTO_1]);
        if(isset($row[STATIC::CODIGO_DE_BARRAS])){
        	$ordenador ->set_sc(Barcode::obtener_segmento_comercial($row[STATIC::CODIGO_DE_BARRAS]));
        	$ordenador ->set_barrand(Barcode::obtener_barrand($row[STATIC::CODIGO_DE_BARRAS]));
        }
	$ordenador ->set_id_mp(static::ID_MP);
        if($ordenador ->get_id_entidad()== Entidad::ENTIDAD_BARCODE){
            $barcode=new Barcode();
            $barcode->get($ordenador ->get_id_referencia());
            $ordenador->set_id_marchand($barcode->get_id_marchand());
        }
        $ordenador ->set_linea($registro->fila);
        if ($ordenador ->set()) {
            return true;
        }
        if (static::ACTIVAR_DEBUG)
            developer_log("|| Error al insertar ordenador.");
        return false;
    }

    final function exportar_archivos($archivos, $nombre) {
        $gestor_de_disco = new Gestor_de_disco();
        $hoy = new DateTime("now");
        if (empty($archivos))
            throw new Exception("No se pueden generar más archivos.");
        foreach ($archivos as $nro_archivo => $archivo) {
            if ($gestor_de_disco->crear_archivo(PATH_CDEXPORTS, $nombre[$nro_archivo], $archivo, true) != false) {
                $array[$nro_archivo] = array($nombre[$nro_archivo], URL_DOWNLOAD . $nombre[$nro_archivo]);
            } else {
                if (static::ACTIVAR_DEBUG)
                    developer_log("|| No se pudo crear el archivo numero $nro_archivo");
            }
        }
        if (!empty($array)) {
            return $this->comprimir($array);
        }
        return false;
    }

    private function cantidad_total_archivo($registros_restantes) {
        return min($registros_restantes, static::CANTIDAD_DE_REGISTROS_POR_ARCHIVO);
    }

    protected function calcular_monto_total($recordset, $nro_archivo, $current_position) {
        $i = 0;
//        $monto_total = array($nro_archivo => 0);
        if ($this->monto_total != null) {
            if (static::ACTIVAR_DEBUG)
                developer_log("Montos ya calculados.");
            return $this->monto_total;
        }
        if (static::ACTIVAR_DEBUG)
            developer_log("Obteniendo el monto total  comenzando por el archivo nro: $nro_archivo");
        $this->monto_total=array($nro_archivo=>0);
        foreach ($recordset as $row) {
            if ($i >= STATIC::CANTIDAD_DE_REGISTROS_POR_ARCHIVO) {
                $nro_archivo++;
                $i = 0;
            }
            if (isset($row[static::MONTO_1]))
                $this->monto_total[$nro_archivo] += doubleval($row[STATIC::MONTO_1]);
            if (isset($row[static::MONTO_2]))
                $this->monto_total[$nro_archivo] += doubleval($row[STATIC::MONTO_2]);
            if (isset($row[static::MONTO_3]))
                $this->monto_total[$nro_archivo] += doubleval($row[STATIC::MONTO_3]);
            $i++;
//                
        }
//        var_dump($this->monto_total);
        $recordset->move($current_position);
        return $this->monto_total;
    }

    final function mostrar_progreso($fila, $progreso, $nro_archivo, $cantidad_total) {

        $porcentaje = number_format(intval((($fila - 1) * 100) / $this->cantidad_registros), 2);
        $porcentaje_total = number_format(((($progreso) * 100 / $cantidad_total)), 2);
        if (date('s') % self::SEGUNDOS == 0 AND $this->mostrar) {
            $this->mostrar = false;
            $string = "[";
            $valor = 20 * $porcentaje / 100;
            for ($i = 0; $i < ($valor); $i++) {
                $string .= "#";
            }
            $string = str_pad($string, 20, ".");
            $string .= "]    Archivo $nro_archivo ";
            developer_log($string);
        } elseif (!$this->mostrar AND date("s") % self::SEGUNDOS != 0)
            $this->mostrar = true;
        if (($this->mostrar_todo AND date('s') % self::SEG_TOTAL == 0) OR ( $fila == $this->cantidad_registros) OR ( $porcentaje_total == 99.89 AND $this->mostrar_todo)) {
            $this->mostrar_todo = false;
            $string = "[";
            $valor = 50 * $porcentaje_total / 100;
            for ($i = 0; $i < ($valor); $i++) {
                $string .= "#";
            }
            $string = str_pad($string, 50, ".", STR_PAD_RIGHT);
            $string .= "] TOTAL ";
            developer_log($string);
        } elseif ((!$this->mostrar_todo AND date('s') % self::SEG_TOTAL != 0 AND $porcentaje_total < 99.89)) {
            $this->mostrar_todo = true;
        }
    }

    protected function enviar_alerta($nombre_archivo, $numero_archivo) {
        developer_log("Enviando Alerta. ");
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = Gestor_de_correo::MAIL_DESARROLLO;
        $asunto = 'Alerta de Ordenador | Fallo al ordenar' . $nombre_archivo;
        $mensaje = 'Error al ordenar archivo: ' . $nombre_archivo . "Numero " . $numero_archivo . ", Reordenar.\n" . Gestor_de_log::ultimos_logs();
        return Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje);
    }

    protected abstract function consultar_bdd(DateTime $fecha_a_ordenar, $nro_archivo);

    protected abstract function nombrar_archivo(DateTime $hoy, $nro_archivo);

    protected abstract function obtener_encabezado($nro_archivo);

    protected abstract function obtener_fila($row);

    protected abstract function obtener_pie($nro_archivo);

    protected abstract function procesar_archivo_control(Datetime $fecha_a_ordenar, $archivos, $nombre);

    protected abstract function actualizar_deuda($row);

    protected function comprimir($array) {
        $archivos_zip = array();
        $archivos = array();
        $path = "";
        foreach ($array as $archivo) {
            $nombre = $archivo[0] . ".zip";
            if (static::PERMITIR_ZIPEADO AND filesize(PATH_CDEXPORTS . $archivo[0]) >= self::TAMAÑO_MINIMO_ZIPEO) {
                $archivos_zip[] = $archivo[0];
            } else {
                $archivos[] = $archivo;
            }
        }
        $gestor_de_disco = new Gestor_de_disco();
        $gestor_de_disco->comprimir_zip($path/* PATH */, $nombre, $archivos_zip);
        if (count($archivos_zip) > 0) {
            $download_zip = array(array($nombre, URL_DOWNLOAD . $nombre));
            $array_resultante = array_merge($download_zip, $archivos);
        } else
            $array_resultante = $archivos;
        return $array_resultante;
    }

}
