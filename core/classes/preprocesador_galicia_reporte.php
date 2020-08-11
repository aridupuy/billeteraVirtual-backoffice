<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of preprocesador_galicia_reporte
 *
 * @author ariel
 */
class Preprocesador_galicia_reporte extends Preprocesador_galicia {



    const PATRON = "/^RPTE.COB-COBC8496.COB-([0-9]{8}).TXT/";
    const PATRON_FECHA = "Ymd";
    private $archivo_de_control = false;
    const POSICION_ENCABEZADO = 1;
    const POSICIONES_FINALES_DESCARTABLES = 8;
    const CONTROL_POSICION_REGISTRO = false;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS = 39;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS = 7;
    const CONTROL_POSICION_IMPORTE_TOTAL = 25;
    const CONTROL_LONGITUD_IMPORTE_TOTAL = 14;
    const CONTROL_DECIMALES_IMPORTE_TOTAL = 2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS = false;
    const DIRECTORIO_FTP = "/home/samba/shares/mp/";
    public function __construct() {
        $codigo_entidad = self::CODIGO_ENTIDAD_GALICIA;
        $codigo_archivo = false;
        $tipo = 1;
        switch ($tipo) {
            # Esto no va a nada -_-
            case '1': $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION;
                break;
            default: $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION_DE_REVERSOS;
                break;
        }
        
        if (!$codigo_archivo) {
            return false;
        }
        
        parent::__construct($codigo_entidad . $codigo_archivo);
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
        $this->developer_log('Obteniendo clase registro.');
        if (!($clase_registro = self::obtener_clase_registro($this->obtener_id_mp()))) {
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
        $this->puntero_fichero->setFlags(SplFileObject::READ_CSV);
        $encabezado = trim($this->obtener_encabezado($this->puntero_fichero));
        
        $this->developer_log('*************************INICIO*********************************');
        $this->developer_log('Medio de Pago: ' . $this->obtener_entidad());
        $this->developer_log("Nombre archivo: '" . basename($this->archivo) . "'");
        $this->developer_log("Encabezado: '" . $encabezado . "'");
        $this->developer_log('Cantidad de lineas: ' . $this->get_cantidad_de_lineas($this->puntero_fichero));
        $this->developer_log('Día:' . date('d/m/Y') . ' Hora:' . date('H:i'));
        $this->developer_log('*****************************************************************');
        if (!Model::hasFailedTrans()) {
            if (!($control = $this->insertar_control($this->obtener_pie($this->puntero_fichero)))) {
                $this->developer_log('Ha ocurrido un error al insertar el registro de Control.');
                Model::FailTrans();
            }
        }
        $this->nlinea = 0;
        unset($fila);
        $i=0;
        while (!Model::hasFailedTrans() AND ( $fila = $this->obtener_registro_siguiente($this->puntero_fichero)) != false) {
//            var_dump($fila);
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
            $i++;
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
    
    
     
    
    public static function obtener_clase_registro($id_mp) {
        developer_log("Registro_galicia_rpte");
        return 'Registro_galicia_rpte'; //para mantener la herencia;
    }
    public static function obtener_clase_preprocesador($id_mp) {
        return get_class($this); //para mantener la herencia;
    }
     private function insertar_control($encabezado) { //por que no se puede usar sin sobrescribir
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
    
    public function obtener_registro($puntero_fichero) {
//        $linea = '';
        $encabezado= $this->obtener_encabezado($puntero_fichero);
        for ($i = 0; $i < static::CANTIDAD_DE_LINEAS_POR_REGISTRO; $i++) {
            $linea = $puntero_fichero->current();
            $puntero_fichero->next();
        }
        $encabezado= str_getcsv($encabezado,";");
        $linea= str_getcsv($linea[0],";");
        //descarto ultimos dos campos
        unset($linea[13]);
        unset($linea[14]);
        $linea=array_combine($encabezado, $linea);
        return $linea;
    }
    public function obtener_pie($puntero_fichero){
        $cantidad_linas= $this->get_cantidad_de_lineas($puntero_fichero);
        $linea_anterior = $puntero_fichero->key();
        $puntero_fichero->seek($cantidad_linas-self::POSICIONES_FINALES_DESCARTABLES);
        $pie="";
        for ($i = 0; $i < static::POSICIONES_FINALES_DESCARTABLES; $i++) {
            $pie.= $puntero_fichero->fgets();
        }
        $pie= substr($pie, 0,240);
        $fecha=new DateTime("now");
        $pie.=basename($this->archivo);
        $puntero_fichero->seek($linea_anterior);
        return $pie;
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
}
