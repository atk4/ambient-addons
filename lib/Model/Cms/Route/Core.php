<?php
namespace cms;
/*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
class Model_Cms_Route_Core extends \Model_Table {
    public $entity_code = "cms_route";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->addField("rule")
        ->datatype("text");
        $this->addField("target")
        ->datatype("text");
        $this->addField("params")
        ->datatype("text");
        $this->addField("ord")
        ->datatype("int");
        
    }
}
