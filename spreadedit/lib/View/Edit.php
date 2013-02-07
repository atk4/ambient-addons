<?php
namespace spreadedit;

class View_Edit extends \AbstractView {
    public $cols = 0;
    public $rows = 0;
    public $limit = 10; // this is due to memory limits. increase depending on your setupp
    function init(){
        parent::init();
        $this->f = $this->add("Form", null, null, array("form_empty"));
        $this->api->addHook("pre-render", array($this, "initComponents"));
    }
    function setDimensions($cols, $rows){
        $this->cols = $cols;
        $this->rows = $rows;
    }
    function initComponents(){
        $m = $this->getModel();
        foreach ($m->elements as $name => $e){
            if ($e instanceof \Field){
                if ($e->editable()){
                    $this->fields[] = $name;
                    $this->cols++;
                }
            }
        }
        $this->createFields();
        $template = $this->createTemplate();
        if ($this->f->isSubmitted()){
            $this->saveData($this->f->get());
            $this->f->js(null, $this->js()->univ()->successMessage("Fine"))->reload()->execute();
        }
        $s = $this->add("SMLite")->loadTemplateFromString($template);
        $this->f->setLayout($s);
        $this->add("Button")->set("Save")->js("click", $this->f->js()->submit());
        return $m;
    }
    function saveData($data){
        $m = $this->getModel();
        reset($m);
        $y = 0;
        foreach ($m as $tmp){
            $this->ids[] = $tmp["id"];
            $x = 0;
            foreach ($this->fields as $field){
                $fx = "f$y" . "_$x";
                $m->set($field, $data[$fx]);
                $x++;
            }
            $m->save();
            $y++;
            if ($y > $this->limit){
                break;
            }
        }
    }
    function createFields(){
        /* generate fields */
        $m = $this->getModel();
        $c=$this->f->add("Controller_MVCForm");
        $c->model = $m;
        $x = $y = 0;
        foreach ($this->getModel() as $tmp){
            $x = 0;
            $this->ids[] = $tmp["id"];
            foreach ($this->fields as $field){
                $fx = "f$y" . "_$x";
                $c->importField($field, $fx);
                $x++;
            }
            $y++;
            if ($y > $this->limit){
                /* limit */
                break;
            }
        }
        $this->rows = $y;
    }
    function createTemplate(){
        /* hint, use filesystem cache for improved performance */
        $template = $this->recall($t="t".$this->cols . "_" . $this->rows);
        /* generate template */
        if (!$template){
            $o = "<div class=\"atk-grid atk4_grid\"><table>";
            $o .= "<thead class=\"ui-widget-header\"><tr class=\"\">";
            for ($x = -1; $x < $this->cols; $x++){
                $o .= "<th>";
                if ($x >= 0){
                    $o .= $this->getModel()->getField($this->fields[$x])->caption();
                } else {
                    $o .= "Id";
                }   
                $o .= "</th>";
            }
            $o .= "</tr></thead><tbody class=\"grid_body\">";
            for ($y = 0; $y < $this->rows; $y++){
                $o .= "<tr><td class=\"row_name\">" . $this->ids[$y]. "</td>";
                for ($x = 0; $x < $this->cols; $x++){
                    $id = "f$y"."_$x";
                    $o .= "<td><?$" . "f$y"."_$x" ."?></td>";
                }
                $o .= "</tr>";
            }
            $o .= "</tbody></table></div>";
            $template = $o;
            $this->memorize($t, $template);
        }
        $this->f->js(true)->find("table td")->css("vertical-align", "top");
        return $template;
    }
    function defaultTemplate(){
        $l=$this->api->locate('addons',__NAMESPACE__,'location');
        $lp=$this->api->locate('addons',__NAMESPACE__);
        $this->api->addLocation($lp,array(
                    'template'=>'templates/default',
                    )
                )
                ->setParent($l);
        return array("view/edit");
    }
}

