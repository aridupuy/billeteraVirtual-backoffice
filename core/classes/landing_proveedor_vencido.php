<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of proveedor_vencido
 *
 * @author juan
 */
class Landing_proveedor_vencido extends Landing_proveedor_action {
    //put your code here
    public function procesar(Landing_proveedor $padre ){
       /* 
         echo "<h1> EL link ya vencio tenia 6 dias,ara responderlo</h2>";
         echo "<h2> Comuniquese con Cobro digital</h1>";
        * 
        */
         $padre->set_view('views/landing_proveedor_vencido.html');

    }
    public function acepto(){
        // no hace nada esta vencido
    }
    public function rechazo(){}
    public function putData(View $view, Landing_proveedor $model, $p) {

      //  $val = $this->armar_data($model);
      /*  $p = $view->getElementById('texto_p');
        $n = $view->createTextNode($val);
        $p->appendChild($n);
       * 
       */
    }

    private function armar_data(Landing_proveedor $l) {
       /* $c = $l->cliente();
        $id = $l->get_id_marchand();
        $m = new Marchand();

        $m->get($id);
        //
        $move = new Moves();
        $move->get($l->get_id());
        
        $t ="Estimado $c" ;
    $t .="La solicitud del pago generada por usted a favor de ". $m->get_nombre() . " " . $m->get_apellido_rs() .", no ha recibido respuesta, por lo tanto, la misma ha sido cancelada.";

$t .="Se realizará un reverso de la reserva de dinero de su cuenta en las próximas 24hs.";

$t .="Por favor, comuníquese con ".  $m->get_nombre(). " para coordinar nuevamente el pago.";

$t .="S.E.U.O.";



        
        return $t;
*/
        /*

         * 
         */
        
    }

}
