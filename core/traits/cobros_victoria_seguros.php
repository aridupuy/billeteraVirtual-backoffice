<?php

class Cobros_victoria_seguros extends Cobros_cobradores {

    const ID_MARCHAND = 243;

//0940640004001201171010000000000000000018
    public function verificar_barcode($barcode) {
        $digito_verificador = substr($barcode,-1);
        $secuencia = "135793579357935793579357935793579357935";
        $i = 1;
        $suma = 0;
        $digitos= str_split($barcode);
        unset($digitos[39]);
        foreach ($digitos as $digito) {
            $suma += $digito * $secuencia{$i-1};
            $i++;
        }
        $verificador_calculado=intval(($suma/2));
        $verificador_calculado=substr($verificador_calculado,-1);
        if ($digito_verificador === $verificador_calculado)
            return true;
        return false;
    }
    public static function carcular_digito_verificador($barcode) {
//        $digito_verificador = substr($barcode,-1);
        $secuencia = "135793579357935793579357935793579357935";
        $i = 1;
        $suma = 0;
        $digitos= str_split($barcode);
        unset($digitos[39]);
        foreach ($digitos as $digito) {
            $suma += $digito * $secuencia{$i-1};
            $i++;
        }
        $verificador_calculado=intval(($suma/2));
        $verificador_calculado=substr($verificador_calculado,-1);
        return $verificador_calculado;
    }
    protected function verificar_existencia_cobro($barcode){
        $recordset= Cobros_cobrador::select(array("codigo_de_barras"=>$barcode));
        if($recordset and $recordset->rowCount()==0)
            return false;
        return true;
    }

}
