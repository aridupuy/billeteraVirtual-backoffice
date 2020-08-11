<?php

class Service_peticion_retiro extends Device_service {

    const PARAMETRO_CUIT = "cuit";
    const PARAMETRO_NOMBRE = "nombre";
    const PARAMETRO_PLATA = "plata";
    const PARAMETRO_TITULAR = "titular";
    const PARAMETRO_MEDIO_DE_PAGO = "medio_de_pago";

    public function ejecutar($array) {

        if (!isset($array[self::PARAMETRO_MEDIO_DE_PAGO])) {
            $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_MEDIO_DE_PAGO);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::PARAMETRO_NOMBRE])) {
            $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_NOMBRE);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::PARAMETRO_PLATA])) {
            $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_PLATA);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        Model::StartTrans();
        switch ($array[self::PARAMETRO_MEDIO_DE_PAGO]) {
            case Mp::RETIROS:
                if (!isset($array[self::PARAMETRO_CUIT])) {
                    $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_CUIT);
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }


                if (!isset($array[self::PARAMETRO_TITULAR])) {
                    $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_TITULAR);
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }
                $recordset_cuenta = Cbumarchand::select_cuenta(self::$id_marchand, $array[self::PARAMETRO_CUIT], $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_TITULAR]);
                if ($recordset_cuenta->rowCount() < 0) {
                    $this->adjuntar_mensaje_para_usuario("Error no se pudo identificar la cuenta bancaria.");
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }

                foreach ($recordset_cuenta as $row_cuenta) {
                    try {
                        if (!Model::hasFailedTrans()) {
                            $monto_pagador = $array[self::PARAMETRO_PLATA];
                            $id_referencia = $row_cuenta['id_cbumarchand'];
                            $fecha = new DateTime('now');
                            $id_mp = Mp::RETIROS;
                            $transaccion = new Transaccion();
                            if (!$this->crear_transaccion($transaccion, $id_mp, $monto_pagador, $fecha, $id_referencia)) {
                                Model::FailTrans();
                                developer_log('Ha ocurrido un error al procesar la transaccion. ');
                                $this->adjuntar_mensaje_para_usuario('Ha ocurrido un error al procesar la transaccion. ');
                            }
                        }
                    } catch (Exception $e) {
                        Model::FailTrans();
                        $this->adjuntar_mensaje_para_usuario($e->getMessage());
                    }
                }
                break;
            case Mp::RETIROS_CHEQUE:
                $monto_pagador = $array[self::PARAMETRO_PLATA];
                $id_referencia = self::$id_marchand;
                $fecha = new DateTime("now");
                $id_mp = Mp::RETIROS_CHEQUE;
                $transaccion = new Transaccion();
                if (!$this->crear_transaccion($transaccion, $id_mp, $monto_pagador, $fecha, $id_referencia)) {
                    Model::FailTrans();
                    developer_log('Ha ocurrido un error al procesar la transaccion. ');
                }
                $transaccion->moves->set_moves_xml("cheque A nombre de " . $array[self::PARAMETRO_TITULAR]);
                if (!$transaccion->moves->set()) {
                    Model::FailTrans();
                    developer_log("Error al insertar la referencia del cheque");
                    $this->adjuntar_mensaje_para_usuario('Ha ocurrido un error al procesar la transaccion. ');
                }
                break;
            case Mp::ADELANTOS_CHEQUE:
                $monto_pagador = $array[self::PARAMETRO_PLATA];
                $id_referencia = self::$id_marchand;
                $fecha = new DateTime("now");
                $id_mp = Mp::ADELANTOS_CHEQUE;
                $transaccion = new Adelanto(self::$id_marchand);
                try {
                    $this->crear_transaccion($transaccion, $id_mp, $monto_pagador, $fecha, $id_referencia);
                    $transaccion->moves->set_moves_xml("cheque A nombre de " . self::$marchand->get_apellido_rs());
                    if (!$adelanto->moves->set()) {
                        Model::FailTrans();
                        $this->adjuntar_mensaje_para_usuario("Error al insertar la referencia del cheque");
                        ;
                    }
                } catch (Exception $e) {
                    Model::FailTrans();
                    Model::CompleteTrans();
                    $transaccion->liberar_semaforo($transaccion->semaforo);
                    Gestor_de_log::set($e->getMessage());
                    $this->adjuntar_mensaje_para_usuario($e->getMessage());
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                }
                break;
            case Mp::ADELANTOS_TRANSFERENCIA:
                if (!isset($array[self::PARAMETRO_CUIT])) {
                    $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_CUIT);
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }


                if (!isset($array[self::PARAMETRO_TITULAR])) {
                    $this->adjuntar_mensaje_para_usuario("Error falta parametro " . self::PARAMETRO_TITULAR);
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }
                $recordset_cuenta = Cbumarchand::select_cuenta(self::$id_marchand, $array[self::PARAMETRO_CUIT], $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_TITULAR]);
                if ($recordset_cuenta->rowCount() < 0) {
                    $this->adjuntar_mensaje_para_usuario("Error no se pudo identificar la cuenta bancaria.");
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }

                foreach ($recordset_cuenta as $row_cuenta) {
                    $fecha = new DateTime("now");
                    $transaccion = new Adelanto(self::$id_marchand);
                    try {
                        if (!Model::hasFailedTrans()) {
                            if (!$this->crear_transaccion($transaccion, Mp::ADELANTOS_TRANSFERENCIA, $array[self::PARAMETRO_PLATA], $fecha, $row["id_cbumarchand"])) {
                                Model::FailTrans();
                                Model::CompleteTrans();
                                $transaccion->liberar_semaforo($transaccion->semaforo);
                                $this->adjuntar_mensaje_para_usuario('Ha ocurrido un error al procesar la transaccion. ');
                                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                            }
                        }
                    } catch (Exception $e) {
                        Model::FailTrans();
                        Model::CompleteTrans();
                        $transaccion->liberar_semaforo($transaccion->semaforo);
                        Gestor_de_log::set($e->getMessage());
                        $this->adjuntar_mensaje_para_usuario($e->getMessage());
                        $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    }
                }
                break;
        }
        if (!Model::HasFailedTrans() AND Model::CompleteTrans()) {
            $this->adjuntar_mensaje_para_usuario("Peticion generada correctamente.");
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_CORRECTA;
            return;
        } else {
            $this->adjuntar_mensaje_para_usuario("Error al generar la petición.");
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
    }

    private function crear_transaccion($transaccion, $id_mp, $monto_pagador, $fecha, $id_referencia) {

        if (!$transaccion->crear(self::$id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia)) {
            return false;
        }
        return true;
    }

}
