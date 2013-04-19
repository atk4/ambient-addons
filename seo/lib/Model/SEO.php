<?php
namespace seo;
class Model_SEO extends \Model_Table {
    public $table = "seo";
    function init(){
        parent::init();
        $this->addField("page");
        $this->addField("title");
        $this->addField("keywords");
        $this->addField("description");
    }
}
