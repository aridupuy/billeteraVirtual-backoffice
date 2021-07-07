<?php

class webservice_consultar_boletas extends Webservice{
    public function ejecutar($array) {
        $filtros=$array["filtros"];
        $desde=DateTime::createFromFormat("Ymd",$array["desde"]);
        $hasta=DateTime::createFromFormat("Ymd",$array["hasta"]);
        $recordset = Bolemarchand::select_id_marchand_full(self::$id_marchand, $filtros, $desde, $hasta);
        $array =$this->preparar_array($recordset);
        if(!$array)
            $this->adjuntar_mensaje_para_usuario ("No existen boletas con esos campos.");
        else
            foreach ($array as $row){
                $this->adjuntar_dato_para_usuario($row);
            }
    }
  private function preparar_array(ADORecordSet_postgres8 $recordset) 
    {
        $matriz = array();
        if (!$recordset or $recordset->RowCount() == 0) {
            return false;
        }
        foreach ($recordset as $registro) {
            $array = array();
            $nombre = '';
            $documento = '';
            if (isset($registro['climarchand_apellido'])) {
                $nombre = $registro['climarchand_apellido'];
                $documento = 'ID: ' . $registro['climarchand_identificador'];
            } elseif (isset($registro['climarchand_apellidors'])) {
                $nombre = $registro['climarchand_apellidors'];
                $documento = 'ID: ' . $registro['climarchand_identificador'];
            } elseif (isset($registro['clima_apellido_rs']) OR isset($registro['clima_nombre'])) {
                if (isset($registro['clima_apellido_rs']))
                    $nombre = $registro['clima_apellido_rs'];
                if (isset($registro['clima_nombre']))
                    $nombre.=' ' . $registro['clima_nombre'];
                if ($registro['id_tipodoc'] == Tipodoc::CUIT_CUIL) {
                    $documento = 'CUIT/CUIL: ' . $registro['clima_documento'];
                } elseif ($registro['id_tipodoc'] == Tipodoc::DNI) {
                    $documento = 'DNI: ' . $registro['clima_documento'];
                }
            } elseif ((isset($registro['otro_nombre']) OR isset($registro['otro_apellido'])) OR isset($registro['otro_documento'])) {
                if (isset($registro['otro_apellido']) AND $registro['otro_apellido'] != Bolemarchand::DUMMY_BOPA_APELLIDO)
                    $nombre = $registro['otro_apellido'];
                if (isset($registro['otro_nombre']) AND $registro['otro_nombre'] != Bolemarchand::DUMMY_BOPA_NOMBRE)
                    $nombre.=" " . $registro['otro_nombre'];
                if (isset($registro['otro_documento']) AND $registro['otro_documento'] != Bolemarchand::DUMMY_TXI_ID)
                    $documento = $registro['otro_documento'];
            }
            unset($fecha);
            if ($fecha = Datetime::createFromFormat("!Y-m-d H:i:s", $registro['emitida'])) {
                $array['Fecha_emision'] = $fecha->format('d/m/Y');
            } else
                $array['Fecha_emision'] = 'Error';

            $array["identificacion"] = $documento;
            $array["Nombre"] = ucwords(strtolower($nombre));
            $array["numero_Boleta"] = $registro['nroboleta'];
            $array["concepto"] = $registro['boleta_concepto'];
            $matriz[]=$array;
        }
        return array($matriz);
    }
}
