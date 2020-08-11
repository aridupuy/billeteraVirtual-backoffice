<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of generador_barcode
 *
 * @author ariel
 */
class Generador_barcode extends Pear {

    public function mostrar($array) {
        $id = $array["barcode"];

        if (isset($array['tipo']) AND trim($array['tipo'])) {
            $ti = trim($array['tipo']);
        } else {
            $ti = 'h';
        }
//        var_dump($ti);
        developer_log($ti);
// $test=(isset($_REQUEST['test']));
        $test = false;
        if (@trim($id)) {
//            $bc = new Barcode();
//  if ( preg_match("/t/msi",$ti,$rett )) $test=1;
            //da($id,$ti,$test);
            $imgR = $this->mimg($id, 'code128', $ti, $test);
// } else {
//  $imgR = @imageCreateFromPng('demo.png');
// }

           
//            $img=imagepng($imgR);
            
            return $imgR;
        }
    }
//    public function mostrar($array) {
//        $id = $array["barcode"];
//        $_GET["codigo_de_barras"]=$id;
//        include_once '../public/codigo_de_barras.php';
////  $imgR = @imageCreateFromPng('demo.png');
//// }
//
//           
////            return imagepng($imgR);
//        
//    }

    private function mimg($text, $type = 'code128', $bartype = 'HL', $test = 0) {
        // defaults for all
        $test = 0;
        $orienta = 'h';
        $tamano = 5;
        $logo = 0;
        
        //Make sure no bad files are included
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $type)) {
            return PEAR::raiseError('Invalid barcode type ' . $type);
        }
        if (!include_once('code128.class.php')) {
            return PEAR::raiseError($type . ' barcode is not supported');
        }

      
        $aa = array(
            'barheight' => 60,
            'inva' => 56,
            'nopa' => 10,
            'hlog' => 135,
        );
        if ($tamano == 1) {
            $aa['barheight'] = 30;
            $aa['inva'] = 26;
            $aa['nopa'] = -10;
            $aa['hlog'] = 105;
        }

//        developer_log(json_encode(get_class_methods($classname)));
//        if (!in_array('draw', get_class_methods($classname))) {
//            return PEAR::raiseError("Unable to find draw method in '$classname' class");
//        }

        $obj = new Image_Barcode_code128();
        developer_log( $aa['barheight']);
        $img = $obj->draw($text,'code_128','png',60);
//        $img = $obj->draw($text,'code_128','png',false);
        
        if (PEAR::isError($img)) {
            var_dump("aca");
            return $img;
        }

//        if ($test) {
//            $src = @imageCreateFromPng('nopag.png');
//            imagecopymerge($img, $src, 30, $aa['nopa'], 0, 0, 310, 50, 70);
//        }
//        var_dump(substr($text, 28, 1) != $this->getMod10(substr($text, 0, 28)));
        if (substr($text, 28, 1) != $this->getMod10(substr($text, 0, 28))) {
            $src = @imageCreateFromPng('../public/norob_invalido.png');
            imagecopymerge($img, $src, 120, $aa['inva'], -10, 0, 140, 20, 160);
        }
        $logo=0;
        if(strstr($bartype, "l") OR strstr($bartype, "L"))
            $logo=1;
        $bartype=$bartype{0};
        developer_log($logo);
        developer_log($orienta);
        developer_log($bartype);
        if ($logo) {
            switch ($bartype) {
                case 'h':
                default:
                    developer_log("aca logo");
                    $imgr = imagecreate(374, $aa['hlog']);
                    imagecolorallocate($imgr, 255, 255, 255);
                    $src = imageCreateFromPng('../public/norob_logo.png');
                    imagecopy($imgr, $src, 50, 0, 0, 0, 272, 50);
                    imagecopy($imgr, $img, 0, 50, 0, 0, 374, 88);
                    break;
                case 'v':
                    $image = imagecreate(374, $aa['hlog']);
                    imagecolorallocate($image, 255, 255, 255);
                    $src = imageCreateFromPng('../public/norob_logo.png');
                    imagecopy($image, $src, 50, 0, 0, 0, 272, 50);
                    imagecopy($image, $img, 0, 50, 0, 0, 374, 88);
                    $imgr = imagerotate($image, 90, 0);
                    break;
                case 'i':
                    $image = imagecreate(374, $aa['hlog']);
                    imagecolorallocate($image, 255, 255, 255);
                    $src = imageCreateFromPng('../public/norob_logo.png');
                    imagecopy($image, $src, 50, 0, 0, 0, 272, 50);
                    imagecopy($image, $img, 0, 50, 0, 0, 374, 88);
                    $imgr = imagerotate($image, 180, 0);
                    break;
                case 'w':
                    $image = imagecreate(374, $aa['hlog']);
                    imagecolorallocate($image, 255, 255, 255);
                    $src = imageCreateFromPng('../public/norob_logo.png');
                    imagecopy($image, $src, 50, 0, 0, 0, 272, 50);
                    imagecopy($image, $img, 0, 50, 0, 0, 374, 88);
                    $imgr = imagerotate($image, 270, 0);
                    break;
            }
        } else {
            switch ($bartype) {
                case 'h':
                default:
                    $imgr = imagerotate($img, 0, 0);
                    break;
                case 'v':
                    $imgr = imagerotate($img, 90, 0);
                    break;
                case 'i':
//                    developer_log("aca$bartype");
                    $imgr = imagerotate($img, 180, 0);
                    break;
                case 'w':
                    $imgr = imagerotate($img, 270, 0);
                    break;
            }
        }
//        developer_log("llegue 2");
//        developer_log($imgr);
        
        return $imgr;
      
    }

    private function getMod10($s28) {
        preg_match("/^(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d$/", $s28, $nones);
        $snones = 0;
        foreach ($nones as $ka => $non) {
            if ($ka)
                $snones += $non;
        }
        $snones *= 3;
        preg_match("/^\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)\d(\d)$/", $s28, $pares);
        $spares = 0;
        foreach ($pares as $ka => $par) {
            if ($ka)
                $spares += $par;
        }
        $tiatota = $snones + $spares;
        $ii = 1;
        while ($tiatota > (10 * $ii)) {
            $ii++;
        }
        $ret = 10 * $ii - $tiatota;
        //dshow(array($ii,$ret,$snones,$spares,$tiatota));
        return $ret;
    }

}
