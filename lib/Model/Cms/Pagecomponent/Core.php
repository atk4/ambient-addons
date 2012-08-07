<?php
namespace cms;
 /*
 * DO NOT MODIFY THIS FILE. THIS FILE WAS AUTOMATICALLY CREATED BY MODEL GENERATOR.
 * ANY CHANGES TO THIS FILE WILL BE LOST. PLEASE, EDIT CORE MODEL WHICH EXTENDS THIS FILE
 * OR ADJUST DATABASE IF YOU NEED CHANGES TO THE FIELDS BELOW
 **/
class Model_Cms_Pagecomponent_Core extends \Model_Table {
    public $table = "cms_pagecomponent";
    public $table_alias = "al_cm";
    function init(){
        parent::init();
        $this->hasOne("cms/Cms_Page", "cms_page_id");
        $this->hasOne("cms/Cms_Component", "cms_component_id");
        $this->addField("template_spot")
        ->datatype("text");
        $this->addField("ord")
        ->datatype("int");
        
    }
}
