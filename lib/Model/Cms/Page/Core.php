<?php
 /*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
namespace cms;
class Model_Cms_Page_Core extends \Model_Table {
    public $entity_code = "cms_page";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->addField("name")
        ->datatype("string");
        $this->addField("api_layout")
        ->datatype("string");
        $this->addField("page_layout")
        ->datatype("string");
        
    }
}
