<?php

class Landing_cpm {

    private $post;
    private $get;
    private $panel;

    public function __construct($post, $get) {
        $this->post = $post;
        $this->get = $get;
        if (!isset($this->post["panel"]) and ! isset($this->get["panel"]))
            $this->panel = "config";
        elseif (isset($this->post["panel"]))
            $this->panel = $this->post["panel"];
        elseif (isset($this->get["panel"]))
            $this->panel = $this->get["panel"];
    }

    public function mostrar_panel() {
        switch ($this->panel) {
            case "config":
                return $this->mostrar_config();
                break;
            case "home":
                return $this->mostrar_home();
                break;
            case "servicio":
                return $this->mostrar_servicio();
                break;
            case "ws":
                return $this->websocket();
                break;
        }
    }

    public function mostrar_config() {
        $view = new View();
        $view->cargar("views/config_cpm.html");
        $modulos = Elemento_cpm::select();
        $content = $view->getElementById("content");
        $i = 0;
        foreach ($modulos as $modulo) {
            $div_elemento = $view->createElement('div');
            $div_cuadrado = $view->createElement('div');
            $div_cuadrado->setAttribute('class', 'cuadrado');
            $div_elemento->setAttribute('class', 'elemento');
            $div_elemento->appendChild($div_cuadrado);

            $div_titulo = $view->createElement('div');
            $span_titulo = $view->createElement('span', $modulo["titulo"]);
            $div_titulo->setAttribute("class", "spantitulo");

            $div_titulo->appendChild($span_titulo);
            $div_cuadrado->appendChild($div_titulo);

            $div_estados = $view->createElement('div');
            $div_estados->setAttribute('class', 'estados');
            $div = $view->createElement('div');
            $div->setAttribute("padre", $i);
            $i++;
            $span = $view->createElement('span', "verde");
            $checkverde = $view->createElement('input');
            $checkverde->setAttribute('type', 'checkbox');
            $checkverde->setAttribute('name', 'checkbox1');
            $checkverde->setAttribute('value', $modulo["id_elemento_cpm"]);

            $div->appendChild($span);
            $div->appendChild($checkverde);

            $span = $view->createElement('span', "amarillo");
            $checkamarillo = $view->createElement('input');
            $checkamarillo->setAttribute('type', 'checkbox');
            $checkamarillo->setAttribute('name', 'checkbox2');
            $checkamarillo->setAttribute('value', $modulo["id_elemento_cpm"]);

            $div->appendChild($span);
            $div->appendChild($checkamarillo);

            $span_rojo = $view->createElement('span', "rojo");
            $checkrojo = $view->createElement('input');
            $checkrojo->setAttribute('type', 'checkbox');
            $checkrojo->setAttribute('name', 'checkbox3');
            $checkrojo->setAttribute('value', $modulo["id_elemento_cpm"]);
            $div->appendChild($span_rojo);
            $div->appendChild($checkrojo);
            $div_cuadrado->appendChild($div);
            $content->appendChild($div_elemento);
//            <div class="elemento" id="elemento">
//                <div class="cuadrado">
//                    <div class="titulo">
//                        <span clas="spantitulo">PCM</span>
//                    </div>
//                    <div  id="estados">
//                        <div><span>VERDE</span><input type="checkbox" name="1"></div>
//                            <div><span>AMARILLO</span><input type="checkbox" name="2"></div>
//                            <div><span>ROJO</span><input type="checkbox" name="3"></div>
//                            <input type="hidden" id="value" value="">
//                        </div>
//                    </div>
//                </div>
//            </div>
//            <div class="elemento" id="estado">
//
//            </div>
//                            
        }
        return $view;
    }

    public function mostrar_home() {
        $view = new View();
        $view->cargar("views/home_cpm.html");
        $modulos = Elemento_cpm::select();
        $content = $view->getElementById("content");
        foreach ($modulos as $modulo) {
            $div_elemento = $view->createElement('div');
            $div_elemento->setAttribute('who', $modulo["id_elemento_cpm"]);
            $div_cuadrado = $view->createElement('div');
            $div_cuadrado->setAttribute('class', 'cuadrado');
            $div_elemento->setAttribute('class', 'elemento');
            $div_elemento->appendChild($div_cuadrado);

            $div_titulo = $view->createElement('div');
            $span_titulo = $view->createElement('span', $modulo["titulo"]);
            $div_titulo->setAttribute("class", "spantitulo");

            $div_titulo->appendChild($span_titulo);
            $div_cuadrado->appendChild($div_titulo);

            $div_estados = $view->createElement('div');
            $div_estados->setAttribute('class', 'estados');

            $div = $view->createElement('div');
            $div->setAttribute('id', 'estados');
            $div_verde = $view->createElement('div');
            var_dump($modulo["estado"]);
            if ($modulo["estado"] != 3)
                $div_verde->setAttribute("class", "bola gris");
            else
                $div_verde->setAttribute("class", "bola verde");
            $div_verde->setAttribute("who", 3);
            $div->appendChild($div_verde);


            $div_amarillo = $view->createElement('div');
            if ($modulo["estado"] != 2)
                $div_amarillo->setAttribute("class", "bola gris");
            else
                $div_amarillo->setAttribute("class", "bola amarillo");
            $div_amarillo->setAttribute("who", 2);

            $div->appendChild($div_amarillo);

            $div_rojo = $view->createElement('div');
            if ($modulo["estado"] != 1)
                $div_rojo->setAttribute("class", "bola gris");
            else
                $div_rojo->setAttribute("class", "bola rojo");
            $div_rojo->setAttribute("who", 1);
            $div->appendChild($div_rojo);
            $div_cuadrado->appendChild($div);
            $content->appendChild($div_elemento);
        }
        return $view;
    }

    public function mostrar_servicio() {
        if (isset($this->post["servicio"]))
            $servicio = $this->post["servicio"];
        else
            return false;
        return $this->$servicio();
    }

    public function cambiar_estado() {
        developer_log(json_encode($this->post));
        switch ($this->post["nombre"]) {
            case "checkbox1":
                $clase = "verde";
                $estado = 3;
                break;
            case "checkbox2":
                $clase = "amarillo";
                $estado = 2;
                break;
            case "checkbox3":
                $clase = "rojo";
                $estado = 1;
                break;
        }
        if (!isset($this->post["valor"]))
            return false;
        $elemento_cpm = new Elemento_cpm();
        $elemento_cpm->get($this->post["valor"]);
        $elemento_cpm->set_estado($estado);
        if (!$elemento_cpm->set()) {
            developer_log("Error al actualizar el elemento.");
            return false;
        }
        return new View();
    }

    public function consultar_estado() {
        developer_log(json_encode($this->post));
        $elemento_cpm = new Elemento_cpm();
        $elemento_cpm->get($this->post["id"]);
        return $elemento_cpm->get_estado();
    }

   
}
