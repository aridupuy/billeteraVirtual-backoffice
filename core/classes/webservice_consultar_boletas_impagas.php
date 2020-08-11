<?php

class webservice_consultar_boletas_impagas extends Webservice {

    const PARAMETRO_CAMPO = "campo";
    const PARAMETRO_BUSCAR = "buscar";
    const FORMATO_FECHA_VTO = 'Y-m-d H:i:s';
    const PARAMETRO_OFFSET="offset";
    const PARAMETRO_LIMIT="limit";
    public function ejecutar($array) {
        $limit=-1;
        $offset=-1;
        if (!isset($array[self::PARAMETRO_CAMPO])) {
            $campo = false;
        } else
            $campo = $array[self::PARAMETRO_CAMPO];
        if (!isset($array[self::PARAMETRO_BUSCAR])) {
            $buscar = false;
        } else
            $buscar = $array[self::PARAMETRO_BUSCAR];
        if (isset($array[self::PARAMETRO_OFFSET])) {
            if (is_numeric($array[self::PARAMETRO_OFFSET])) {
                $offset = $array[self::PARAMETRO_OFFSET];
            } else {
                $mensaje = "El offset debe ser numerico.";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            }
        }

        if (isset($array[self::PARAMETRO_LIMIT])) {
            if (is_numeric($array[self::PARAMETRO_LIMIT])) {
                $limit = $array[self::PARAMETRO_LIMIT];
            } else {
                $mensaje = "El limit debe ser numerico.";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            }
        }
        if ($buscar !== false AND $campo !== false) {
            $pagador = new Pagador();
            $identificador_nombre = $pagador->obtener_nombre_desde_label(self::$marchand->get_id(), $campo);
            if (!$identificador_nombre) {
                $mensaje = "El identificador no pertenece a la estructura de clientes. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            }
            $climarchand = $this->obtener_climarchand(self::$id_marchand, $identificador_nombre, $buscar);
            if (!$climarchand) {
                $this->adjuntar_mensaje_para_usuario("No se pudo encontrar el pagador.");
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                return false;
            }
            $recordset = Bolemarchand::select_proximos_vencimientos($climarchand->get_id_climarchand(), false, self::$id_marchand,$limit,$offset);
        } else {
            $recordset = Bolemarchand::select_proximos_vencimientos(false, false, self::$id_marchand,$limit,$offset);
        }
        $array = $this->preparar_array($recordset);
        if (!$array)
            $this->adjuntar_mensaje_para_usuario("No existen boletas con para los datos suministrados.");
        else {
            foreach ($array as $row) {
//                print_r($row);
//                developer_log("linea--------------------------------------------------------------------------");
//                developer_log(json_encode($row));
                $this->adjuntar_dato_para_usuario($row);
            }
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
    }

    private function preparar_array(ADORecordSet_postgres8 $recordset) {
        $matriz = array();
        if (!$recordset or $recordset->RowCount() == 0) {
            return false;
        }
        foreach ($recordset as $registro) {
            $array = array();
            $array["nro_boleta"] = $registro["nroboleta"];
            $array["concepto"] = $registro["boleta_concepto"];
            $fecha = DateTime::createFromFormat(self::FORMATO_FECHA_VTO, $registro["fecha_vto"]);
            $array["fecha"] = $fecha->format("d-m-Y");
            $array["monto"] = $registro['monto'];
            $array["codigo_electronico"] = $registro['pmc19'];
            $array["codigo_de_barras"] = $registro['barcode'];
            $array["estado"] = $registro['id_authstat'];
            $array["identificacion"] = $registro["identificador"];
//            $array["Nombre"] = ucwords(strtolower($nombre));
//            $array["numero_Boleta"] = $registro['nroboleta'];
//            $array["concepto"] = $registro['boleta_concepto'];
            $matriz[] = $array;
        }
        return $matriz;
    }

}
