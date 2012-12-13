<?php
namespace cms;
class Model_Cms_Route extends Model_Cms_Route_Core {
    function init(){
        parent::init();
        $this->getField("rule")->datatype("string");
        $this->getField("target")->datatype("string");
        $this->getField("params")->datatype("string");
        $this->setOrder("ord");
    }
}
