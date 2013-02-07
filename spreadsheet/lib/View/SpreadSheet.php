<?php
namespace spreadsheet;

class View_SpreadSheet extends \AbstractView {
    public $cols = 8;
    public $rows = 25;
    function init(){
        parent::init();
        $this->f = $this->add("Form", null, null, array("form_empty"));
    }
    function setDimensions($cols, $rows){
        $this->cols = $cols;
        $this->rows = $rows;
    }
    function setModel($a,$b=null,$c=null){
        $m=parent::setModel($a,$b,$c);
        /* load data */
        $this->loadData();
        $this->setDimensions($m["cols"], $m["rows"]);
        $template = $this->createTemplate();
        $this->createFields();
        if ($this->f->isSubmitted()){
            $this->saveData($this->f->get());
            $this->f->js(null, $this->js()->univ()->successMessage("Fine"))->reload()->execute();
        }
        $s = $this->add("SMLite")->loadTemplateFromString($template);
        $this->f->setLayout($s);
        $this->add("Button")->set("Save")->js("click", $this->f->js()->submit());
        return $m;
    }
    function loadData(){
        $m2 = $this->getModel()->ref('spreadsheet/SpreadSheet_Cell');
        foreach ($m2 as $t){
            $this->data[$t["name"]] = $t["content"];
        }
    }
    function saveData($data){
        $m2 = $this->getModel()->ref('spreadsheet/SpreadSheet_Cell');
        foreach ($data as $k => $v){
            $m2->tryloadBy("name", $k);
            $m2->set("content", $v)->saveAndUnload();
        }
    }
    function createFields(){
        /* generate fields */
        for ($y = 0; $y < $this->rows; $y++){
            for ($x = 0; $x < $this->cols; $x++){
                $field = $this->f->addField("Line", $fx="f$y"."_$x");
                if (isset($this->data[$fx])){
                    $field->set($this->data[$fx]);
                }
            }
        }
    }
    function createTemplate(){
        /* hint, use filesystem cache for improved performance */
        $template = $this->recall($t="t".$this->cols . "_" . $this->rows);
        /* generate template */
        if (!$template){
            $o = "<table>";
            $o .= "<tr>";
            for ($x = -1; $x < $this->cols; $x++){
                $o .= "<th>";
                if ($x >= 0){
                    $o .= "$x";
                }
                $o .= "</th>";
            }
            $o .= "</tr>";
            for ($y = 0; $y < $this->rows; $y++){
                $o .= "<tr><td class=\"row_name\">$y</td>";
                for ($x = 0; $x < $this->cols; $x++){
                    $id = "f$y"."_$x";
                    $o .= "<td><?$" . "f$y"."_$x" ."?></td>";
                }
                $o .= "</tr>";
            }
            $o .= "</table>";
            $template = $o;
            $this->memorize($t, $template);
        }
        return $template;
    }
}


