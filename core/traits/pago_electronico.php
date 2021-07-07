<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Pago_electronico
 *
 * @author ariel
 */
abstract class Pago_electronico {
    public static $mp;
    
    abstract public function obtener_siguiente_num_transaccion();
    abstract public function generar_pago(...$param);
    abstract public function obtener_pagos();
    abstract public function devolver($param);
    abstract public function devolver_parcial($param,$monto);
    abstract public function guardar_transaccion(...$param);
     public static function obtener_issued($nrotarj) {


//        visa debito return new RegExp("^(" + this.visaDebitBinesRegex + ")[0-9]{10}$"
        //visa debito return [400276, 400448, 400615, 402789, 402914, 404625, 405069, 405515, 405516, 405517, 405755, 405896, 405897, 406290, 406291, 406375, 406652, 406998, 406999, 408515, 410082, 410083, 410121, 410122, 410123, 410853, 411849, 417309, 421738, 423623, 428062, 428063, 428064, 434795, 437996, 439818, 442371, 442548, 444060, 444493, 446343, 446344, 446345, 446346, 446347, 450412, 451377, 451701, 451751, 451756, 451757, 451758, 451761, 451763, 451764, 451765, 451766, 451767, 451768, 451769, 451770, 451772, 451773, 457596, 457665, 462815, 463465, 468508, 473227, 473710, 473711, 473712, 473713, 473714, 473715, 473716, 473717, 473718, 473719, 473720, 473721, 473722, 473725, 476520, 477051, 477053, 481397, 481501, 481502, 481550, 483002, 483020, 483188, 489412, 492528, 499859]

        $array_issues = array(
            "WishGift" => "/^637046[0-9]{10}$/",
            "Favacard" => "/^504408[0-9]{12}$/",
            "Naranja" => "/^589562[0-9]{10}$/",
            "Visa Debito" => self::obtener_visa_debito_regex(),
            "CoopePlus" => "/^627620[0-9]{10}$/",
            "Nevada" => "/^504363[0-9]{10}$/",
            "Nativa" => "/^(520053|546553|487017)[0-9]{10}$/",
            "Cencosud" => "/^603493[0-9]{10}$/",
            "Carrefour" => "/^(507858|585274)[0-9]{10}(?:[0-9]{3})?$/",
            "PymeNacion" => "/^504910[0-9]{10}$/",
            "BBPS" => "/^627401[0-9]{10}$/",
            "Qida" => "/^504570[0-9]{10}$/",
            "Grupar" => "/^(606301|605915)[0-9]{10}$/",
            "Patagonia 365" => "/^504656[0-9]{10}$/",
            "Club Día" => "/^636897[0-9]{10}$/",
            "Tuya" => "/^588800[0-9]{10}$/",
            "La Anonima" => "/^421024[0-9]{10}$/",
            "CrediGuia" => "/^603288[0-9]{10}$/",
            "Cabal Prisma" => "/^589657[0-9]{10}$/",
            "SOL" => "/^504639[0-9]{10}$/",
            "Cabal 24" => "/^(6042|6043)[0-9]{12}$/",
            "Musicred" => "/^636435[0-9]{10}$/",
            "Credimas" => "/^504520[0-9]{10}$/",
            "Discover" => "/^(65[0-9]2|6011)[0-9]{12}$/",
            "Diners" => "/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/",
            "Shopping" => "/^(279[0-9]{3}|603488|606488|589407)[0-9]{10}(?:[0-9]{3})?$/",
            "Amex" => "/^3[47][0-9]{13}$/",
            "Visa" => "/^4[0-9]{12}(?:[0-9]{3})?$/",
            "Mastercard Debit" => self::rangosMcDebito(),
            "MasterCard" => self::rangosMcCredito(),
            "MasterCard" => "/^(5[1-6]|^2[2-7])[0-9]{14}$/",
            "Maestro" => "/^5[0,8][0-9]{14},16$/",
            "visa" => "/^778899000000094008$/" //test
        );


        foreach ($array_issues as $issued => $regex) {
            if(is_callable($regex)){
                if($regex($nrotarj)){
                    return utf8_decode($issued);
                }
            }
            if(is_string($regex)){
                if (preg_match($regex, $nrotarj)) {
                    return utf8_decode($issued);
                }
            }
        }






//        Visa 	 4- 
//        Mastercard 51-, 52-, 53-, 54-, 55-
//        Diners Club 36-, 38-
//        Discover 6011-, 65-
//        JCB 35-
//        American Express 34-, 37-
    }

    protected static function rangosMcDebito() {
        return function($tarj){
            $array = self::rangosMcDebit();
            foreach ($array as $obj){
                $bin = (int) substr($tarj, 0,6);
                if($bin >= $obj["inferior"] and $bin <= $obj["superior"]){
                    return true;
                }
            }
            return false;
        };
    }
    protected static function rangosMcCredito() {
        return function($tarj){
            $array = self::rangosMastercard();
            foreach ($array as $obj){
                $bin = (int) substr($tarj, 0,6);
                if($bin >= $obj["inferior"] and $bin <= $obj["superior"]){
                    return true;
                }
            }
            return false;
        };
    }
    protected static function rangosMcDebit() {
        return json_decode('[{
                "inferior": 510250,
                "superior": 510399
            }, {
                "inferior": 510550,
                "superior": 511204
            }, {
                "inferior": 511215,
                "superior": 511314
            }, {
                "inferior": 511750,
                "superior": 511999
            }, {
                "inferior": 512965,
                "superior": 512999
            }, {
                "inferior": 514130,
                "superior": 514730
            }, {
                "inferior": 514741,
                "superior": 514860
            }, {
                "inferior": 514961,
                "superior": 515430
            }, {
                "inferior": 515461,
                "superior": 515585
            }, {
                "inferior": 515910,
                "superior": 515999
            }, {
                "inferior": 516400,
                "superior": 516439
            }, {
                "inferior": 516680,
                "superior": 516729
            }, {
                "inferior": 517542,
                "superior": 517581
            }, {
                "inferior": 517860,
                "superior": 517909
            }, {
                "inferior": 519031,
                "superior": 519105
            }, {
                "inferior": 519635,
                "superior": 519694
            }, {
                "inferior": 520512,
                "superior": 520601
            }, {
                "inferior": 520811,
                "superior": 520830
            }, {
                "inferior": 521400,
                "superior": 521449
            }, {
                "inferior": 521681,
                "superior": 521755
            }, {
                "inferior": 523802,
                "superior": 523899
            }, {
                "inferior": 526234,
                "superior": 526263
            }, {
                "inferior": 526400,
                "superior": 526499
            }, {
                "inferior": 526701,
                "superior": 526710
            }, {
                "inferior": 527470,
                "superior": 527494
            }]',true);
    }

    protected static function rangosMastercard() {
        return json_decode('[{
                "inferior": 100383,
                "superior": 100383
            }, {
                "inferior": 201282,
                "superior": 201282
            }, {
                "inferior": 308800,
                "superior": 309499
            }, {
                "inferior": 309600,
                "superior": 310299
            }, {
                "inferior": 311200,
                "superior": 312099
            }, {
                "inferior": 315800,
                "superior": 315999
            }, {
                "inferior": 333700,
                "superior": 334999
            }, {
                "inferior": 352800,
                "superior": 358999
            }]', true);
    }

    protected static function obtener_visa_debito_regex() {
        return function($tarj){
            return preg_match(self::obtener_regexvisa(), $tarj);
        };
    }
    function obtener_regexvisa(){
        return "/^(" . self::visaDebitBinesRegex() . ")[0-9]{10}$/";
    }
    protected static function visaDebitBinesRegex() {
        $visa = [400276, 400448, 400615, 402789, 402914, 404625, 405069, 405515, 405516, 405517, 405755, 405896, 405897, 406290, 406291, 406375, 406652, 406998, 406999, 408515, 410082, 410083, 410121, 410122, 410123, 410853, 411849, 417309, 421738, 423623, 428062, 428063, 428064, 434795, 437996, 439818, 442371, 442548, 444060, 444493, 446343, 446344, 446345, 446346, 446347, 450412, 451377, 451701, 451751, 451756, 451757, 451758, 451761, 451763, 451764, 451765, 451766, 451767, 451768, 451769, 451770, 451772, 451773, 457596, 457665, 462815, 463465, 468508, 473227, 473710, 473711, 473712, 473713, 473714, 473715, 473716, 473717, 473718, 473719, 473720, 473721, 473722, 473725, 476520, 477051, 477053, 481397, 481501, 481502, 481550, 483002, 483020, 483188, 489412, 492528, 499859];
        
        return implode("|", $visa);
    }

}
