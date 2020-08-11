<?php

require_once PATH_PUBLIC . 'dompdf/lib/html5lib/Parser.php';
require_once PATH_PUBLIC . 'dompdf/src/Autoloader.php';
//        require_once  PATH_PUBLIC.'dompdf/include/autoload.inc.php';
require_once PATH_PUBLIC . 'dompdf/src/Dompdf.php';
require_once PATH_PUBLIC . 'php-shellcommand/src/Command.php';
require_once PATH_PUBLIC . 'php-tmpfile/src/File.php';
require_once PATH_PUBLIC . 'phpwkhtmltopdf/src/Pdf.php';
require_once PATH_PUBLIC . 'phpwkhtmltopdf/src/Command.php';
require_once PATH_PUBLIC . 'phpwkhtmltopdf/src/Pdf.php';

//        require_once  PATH_PUBLIC.'dompdf/dompdf_config.custom.inc.php';
//        require_once  PATH_PUBLIC.'dompdf/dompdf.php';
class Gestor_de_pdf {

    const PAPEL_A4 = "A4";
    const PAPEL_A3 = "A3";
    const PAPEL_A5 = "A5";
    const PAPEL_OFICIO = "Legal";
    const MODO_LANDSCAPE = "landscape";
    const MODO_LANDSCAPE_wkhtmltopsf = "Landscape";
    const MODO_PORTRAIT = "portrait";
    public static $margin_top=10;
    public static $margin_bottom=10;
    public static $margin_left=10;
    public static $margin_right=10;

    public static $papel;
    public static $modo;
    private $pdf;

    public function __construct($modo, $papel) {
        $this->pdf = new \mikehaertl\wkhtmlto\Pdf(array(
            'binary' => PATH_WKPDF,
            'ignoreWarnings' => true,
            'commandOptions' => array(
                'useExec' => true, // Can help if generation fails without a useful error message
                'procEnv' => array(
                    // Check the output of 'locale' on your system to find supported languages
                    'LANG' => 'en_US.utf-8',
                ),
            ),
            'orientation' => $modo,
            'page-size' => $papel,
            'no-outline', // Make Chrome not complain
            'margin-top' => 10,
            'margin-right' => 10,
            'margin-bottom' => 10,
            'margin-left' => 10,
        ));
    }
    //utiliza dompdf solo disponible estaticamente
    public static function crear_pdf($html, $path = false) {
        Dompdf\Autoloader::register();
//        \Dompdf\Autoloader::register();
        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        developer_log("Generando pdf");
        $dompdf = new Dompdf\Dompdf($options);
//        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper(self::$papel, self::$modo);
        $dompdf->render();
        if ($path === false) {
            $dompdf->stream();
//            $html= $dompdf->get_dom()->saveHTML();
//            print_r($html);
//            $dompdf->stream('my.pdf',array('Attachment'=>0));
//            print_r($dompdf->outputHtml());
//            print_r($dompdf->());
//            $dompdf->output();
        } else {
            $name = basename($path);
            $path = pathinfo($path, PATHINFO_DIRNAME);
            $name = $name;
//            $dompdf->stream($path.$name,array('Attachment'=>0));
            $out = $dompdf->output();
            file_put_contents($path . "/" . $name, $out);
            return $path;
        }
    }
    
    //utiliza wkhtmltopdf disponible de forma estatica o con la instancia
    public static function crear_pdf_bis($html, $path = false,$retorna=null) {
        $view = new View();
//        developer_log($html);
        $view->loadHTML($html);
        $elementos = $view->getElementById("html");
        $error=false;
//        print_r($elementos);
        if($elementos==null)
            $elementos = $view->getElementsByTagName("html");
        $array_direcciones=array();
        foreach ($elementos as $elemento) {
//            developer_log("en el foreach");
            $newView = new View();
            $newView->appendChild($newView->importNode($elemento, true));
            $path1=$path.rand(0,1000).".pdf";
            if($retorna=="array")
                $array_direcciones[]=$path1;
//            Gestor_de_disco::crear_archivo($path, rand(0,1000).".html", $newView->saveHTML());
            $pdf = new \mikehaertl\wkhtmlto\Pdf($newView->saveHTML());
            $pdf->commandOptions;
            $pdf->setOptions(array(
                'binary' => PATH_WKPDF,
                'ignoreWarnings' => true,
                'commandOptions' => array(
                    'useExec' => true, // Can help if generation fails without a useful error message
                    'procEnv' => array(
                        // Check the output of 'locale' on your system to find supported languages
                        'LANG' => 'en_US.utf-8',
                    ),
                ),
                'orientation' => self::$modo,
                'page-size' => self::$papel,
                'no-outline', // Make Chrome not complain
                'margin-top' => self::$margin_top,
                'margin-right' => self::$margin_right,
                'margin-bottom' => self::$margin_bottom,
                'margin-left' => self::$margin_left,
            ));
//            developer_log("antes de if");
            if ($path1 != false) {
                if (!$pdf->saveAs($path1)) {
                    developer_log("Salio con errores");
                    developer_log($pdf->getError());
                    $error=true;
                } else {
                    developer_log("en gestor pdf : ".$path1);
                    if($retorna==null)
                        return $path1;
                }
            } else
                $pdf->send();
        }
        if(!$error and $retorna=="array"){
            return $array_direcciones;
        }
        else{
            return $path1;
        }
    }
    
    public static function generar_pdf($html, $path = false) {
            $path1=$path.rand(0,1000).".pdf";
            $newView=new View();
            $newView->loadHTMLFile($path);
            
            $elementos = $newView->getElementsByTagName("html");
            $pdf = new \mikehaertl\wkhtmlto\Pdf();
            $pdf->commandOptions;
            $pdf->setOptions(array(
                'binary' => PATH_WKPDF,
                'ignoreWarnings' => true,
                'commandOptions' => array(
                    'useExec' => true, // Can help if generation fails without a useful error message
                    'procEnv' => array(
                        // Check the output of 'locale' on your system to find supported languages
                        'LANG' => 'en_US.utf-8',
                    ),
                ),
                'orientation' => self::$modo,
                'page-size' => self::$papel,
//                'no-outline', // Make Chrome not complain
                'margin-top' => self::$margin_top,
                'margin-right' => self::$margin_right,
                'margin-bottom' => self::$margin_bottom,
                'margin-left' => self::$margin_left,
            ));
            foreach ($elementos as $elemento){
                $view=new View();
                $view->appendChild($view->importNode($elemento,true));
                $pdf->addPage($view->saveHTML());
                
            }
//            developer_log("antes de if");
            if ($path1 != false) {
                if (!$pdf->saveAs($path1)) {
                    developer_log("Salio con errores");
                    developer_log($pdf->getError());
                    $error=true;
                } else {
                    developer_log("en gestor pdf : ".$path1);
                    return array($path1);
                }
            } else
                $pdf->send();
    }
    
    public function crear_pagina($html) {
        $this->pdf->addPage($html);
    }

    public function guardar_pdf($path) {
        if (!$this->pdf->saveAs($path)) {
            developer_log($this->pdf->getError());
            return false;
        } else {
            return $path;
        }
    }

}