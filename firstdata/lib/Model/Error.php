<?php
namespace firstdata;
class Model_Error extends \Model_Table {
    function init(){
        $this->table = $this->api->getConfig("firstdata/table/error");
        parent::init();
        $this->addField("error_time");
        $this->addField("action");
        $this->addField("response")
            ->type("text");
    }
}
