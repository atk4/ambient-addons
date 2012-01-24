<?php

class Model_Cms_Tag extends Model_Table {
    public $entity_code = "cms_tag";
    function init(){
        parent::init();
        $this->addField("name");
        $this->addField("value")->datatype("text")->allowhtml(true);
    }
}
