<?php

// namespace Classes;
class Filters extends View {
    # Retorna los filtros del util. Recibe un recordset y arma los filtros para cada columna

    public function __construct($registros, $variables, $link) {

        parent::__construct();
        $div = $this->createElement('div');
        $div->setAttribute('class', 'filter');
        $this->appendChild($div);
        if ($registros) {
            $i = 0;
            while ($campo = $registros->FetchField($i) AND $campo->name) {

                $recorrer[$campo->name] = $i;
                $i++;
            }
            foreach ($recorrer as $columna => $indice):
                $meta = $registros->FetchField($indice); # Todo quedo dado vuelta 

                $input = $this->createElement('input');
                $input->setAttribute('class', 'control');
                $tipo = $registros->MetaType($meta->type);
                switch ($tipo) {
                    case 'C':
                        $input->setAttribute('type', 'text');
                        break;
                    case 'I':
                        $input->setAttribute('type', 'number');
                        break;
                    case 'N':
                        $input->setAttribute('type', 'number');
                        $input->setAttribute('step', '0.01');
                        break;
                    case 'D':
                    case 'T':
                        $input->setAttribute('type', 'date');
                        break;
                    default:
                        $input->setAttribute('type', 'text');
                        break;
                }

                $input->setAttribute('name', $meta->name);
                $input->setAttribute('id', $meta->name);
                $input->setAttribute('placeholder', ucfirst($meta->name));
                if (isset($variables[$meta->name]))
                    $input->setAttribute('value', $variables[$meta->name]);
                $div->appendChild($input);
            endforeach;
        }
        $div1 = $this->createElement('div');
        $div1->setAttribute('class', 'sub_menu');
        $div->appendChild($div1);

        $button = $this->createElement('input');
        $button->setAttribute('type', 'submit');
        $button->setAttribute('name', $link);
        $button->setAttribute('Value', 'Filtrar');
        $button->setAttribute('class', 'btn outline btn-primary');

        $div->appendChild($button);

        return $div;
    }

    public function eliminar($posicion) {

        $inputs = $this->getElementsByTagName("input");
        
        $va = $inputs->item($posicion);
        $va->parentNode->removeChild($va);
    }
    public static function crear_filtros_array($array, $variables, $link){
        $view=new View();
        $encabezados=$array[0];
        unset($array[0]);
        $div=$view->createElement("div");
        $div->setAttribute("class", "filter");
        foreach ($encabezados as $key=>$value){
            var_dump($variables);
            $input = $view->createElement("input");
            $input ->setAttribute("class", "control");
            $input ->setAttribute("name", $key);
            $input ->setAttribute("placeholder", $key);
            $input ->setAttribute("value", $variables[$key]);
            $input ->setAttribute("type", "text");
//            $input ->setAttribute("type", "text");
            $div->appendChild($input);
        }
        $submenu= $view->createElement("div");
        $submenu->setAttribute("class", "submenu");
        $button=$view->createElement("input");
        $button->setAttribute("type", "submit");
        $button->setAttribute("name", $link);
        $button->setAttribute("value", "Filrar");
        $button->setAttribute("class", "btn outline btn-primary");
        $div->appendChild($submenu);
        $div->appendChild($button);
        $view->appendChild($div);
        return $view;
    }

}
