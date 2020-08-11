<?php

class Cobros_cobradores {

    protected $cobrador;
    protected $lote;
    protected $ultimo_cobro;

    const ID_MP = "91";
    const SABANA_AUTHSTAT = "1";

    public function get_cobrador() {
        return $this->cobrador;
    }
    public function get_ultimo_cobro() {
        return $this->ultimo_cobro;
    }

    public function get_lote() {
        return $this->lote;
    }

    public final function __construct(Cobrador $cobrador) {
        $this->cobrador = $cobrador;
    }

    public function procesar_cobranza($barcode, $endoso = false, $ramo = false) {
        if ($this->validar_barcode($barcode)) {
            if ($this->cobrar($barcode, $ramo, $endoso))
                return true;
        }
        return false;
    }

    protected function validar_barcode($barcode) {
        return true;
    }

    public static final function obtener_ente_cobrador($barcode, $id_cobrador) {//barcode no de cobrodigital
        $recordset = Cobrador_marchand::obtener_codigos_ente_disponibles($id_cobrador);
        if ($recordset AND $recordset->rowCount() > 0) {
            foreach ($recordset as $row) {
                if (strpos($barcode, $row["codigo_ente"]) !== false) {
                    return $row["clase_cobrador"]; //nombre de la clase a instanciar
                }
            }
        }

        return false;
    }

    public function cobrar($barcode, $ramo = false, $endoso = false) {
        if ($this->verificar_barcode($barcode)) {
            Model::StartTrans();
            $importe = (substr($barcode, 6, 8) / 100);
            $codigo_ente=substr($barcode,0,6);
            $cuota = substr($barcode, 14, 2);
            $vencimiento = substr($barcode, 16, 6);
            $fecha = DateTime::createFromFormat("ymd", $vencimiento);
            $poliza = substr($barcode, 24, 9);
//	    $poliza = substr($barcode, 33, 9);
	    $rs_marchand_ente=Marchand_ente::select(array("codigo_ente"=>$codigo_ente));
            if($rs_marchand_ente and $rs_marchand_ente->rowCount()==1){
                  $row=$rs_marchand_ente->fetchRow();
                  $id_marchand=$row["id_marchand"];
            }

            if (($this->obtener_lote_disponible($id_marchand)))
                if (!$this->verificar_existencia_cobro($barcode)) {
                    $cobro = new Cobros_cobrador();
                    $cobro->set_cuota($cuota);
                    $fecha_gen = new DateTime("now");
                    $cobro->set_fecha_gen($fecha_gen->format("Y-m-d H:i:s"));
                    $cobro->set_id_cobrador($this->cobrador->get_id_cobrador());
                    $cobro->set_id_marchand($id_marchand); 
                    $cobro->set_importe($importe);
                    $cobro->set_poliza($poliza);
                    $cobro->set_endoso($endoso);
                    $cobro->set_ramo($ramo);
                    $cobro->set_vencimiento($fecha->format("Y-m-d"));
                    $cobro->set_codigo_de_barras($barcode);
                    $cobro->set_id_authstat(Authstat::ACTIVO);
                    $cobro->set_id_lote_cobrador($this->lote->get_id_lote_cobrador());
                    if ($cobro->set()) {
                        if (!Model::HasFailedTrans() and Model::CompleteTrans())
                            $this->ultimo_cobro=$cobro;
                            return "Cobro procesado correctamente.";
                    } else
                        throw new Exception("Ocurrio un error al procesar el pago.");
                }
                else {
                    Model::FailTrans();
                    throw new Exception("El codigo de barras ya fue cobrado.");
                }
        } else {
            Model::FailTrans();
            throw new Exception("El codigo de barras es incorrecto.");
        }
    }

    protected function obtener_lote_disponible($id_marchand) {
        $fecha = new DateTime("now");
        $lote = $this->obtener_lote_actual($fecha);
        $lote->set_id_cobrador($this->cobrador->get_id_cobrador());
        $lote->set_id_marchand($id_marchand);
        $lote->set_fecha_gen($fecha->format("Y-m-d"));
        $lote->set_id_authstat(Authstat::ACTIVO);
        if ($lote->set()) {
            $this->lote = $lote;
            return $lote;
        } else
            return false;
    }

    private function obtener_lote_actual(DateTime $fecha) {
        $recorset = Lote_cobrador::select(array("fecha_gen" => $fecha->format("Y-m-d"), "id_authstat" => Authstat::ACTIVO, "id_cobrador" => $this->cobrador->get_id_cobrador()));
        if ($recorset->rowCount() == 0)
            return new Lote_cobrador();
        else {
            return new Lote_cobrador($recorset->fetchRow());
        }
    }

    protected function verificar_existencia_cobro($barcode) {
        return false;
    }

}
