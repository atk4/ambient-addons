<?php
namespace cms;
 /*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
class Model_Cms_Componenttype_Core extends \Model_Table {
    public $table = "cms_componenttype";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->addField("name")
        ->datatype("string");
        $this->addField("class")
        ->datatype("string");
        
    }
}
