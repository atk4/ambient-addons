<?php
namespace cms;
class Model_Cms_Tag extends \Model_Table {
    public $table = "cms_tag";
    function init(){
        parent::init();
        $this->addField("name");
        $this->addField("value")->datatype("text")->allowhtml(true);
    }
}
