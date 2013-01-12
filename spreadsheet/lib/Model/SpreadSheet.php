<?php
namespace spreadsheet;

class Model_SpreadSheet extends \Model_Table {
    public $table = "spreadsheet";
    public $limit = 250;
    function init(){
        parent::init();
        $this->addField("name");
        $this->addField("cols");
        $this->addField("rows");
        $this->hasMany("spreadsheet/SpreadSheet_Cell");
        $this->addHook("beforeSave", array($this, "beforeSave"));
    }
    function beforeSave(){
        if ($this["cols"] * $this["rows"] > $this->limit){
            throw $this->exception("Please, rows * cols < " . $this->limit, "ValidityCheck")->setField("cols");
        }
    }
}
