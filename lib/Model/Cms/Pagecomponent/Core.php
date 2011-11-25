<?php
 /*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
class Model_Cms_Pagecomponent_Core extends Model_Table {
    public $entity_code = "cms_pagecomponent";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->addField("cms_page_id")
        ->datatype("int")->refModel("Model_Cms_Page");
        $this->addField("cms_component_id")
        ->datatype("int")->refModel("Model_Cms_Component");
        $this->addField("template_spot")
        ->datatype("text");
        $this->addField("ord")
        ->datatype("int");
        
    }
}
