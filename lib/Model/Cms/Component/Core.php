<?php
 /*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
class Model_Cms_Component_Core extends Model_Table {
    public $entity_code = "cms_component";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->addField("name")
        ->datatype("string");
        $this->addField("cms_componenttype_id")
        ->datatype("int")->refModel("Model_Cms_Componenttype");
        $this->addField("config")
        ->datatype("text");
        $this->addField("is_enabled")
        ->datatype("boolean");
        
    }
}
