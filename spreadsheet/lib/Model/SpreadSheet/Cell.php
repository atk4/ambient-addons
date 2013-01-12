<?php
namespace spreadsheet;

class Model_SpreadSheet_Cell extends \Model_Table {
    public $table = "spreadsheet_cell";
    function init(){
        parent::init();
        $this->addField("name");
        $this->addField("content");
        $this->hasOne('spreadsheet/SpreadSheet');
    }
}
