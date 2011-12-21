<?php

class Controller_Cms extends AbstractController {
    function init(){
        parent::init();

        $this->owner->addMethod("canConfigureCms", array($this, "canConfigure"));
        // 
        if($this->api->page=='cmsframe'){
            $this->api->page_object = $this->api->add('Page_CmsFrame');
            return;
        }
        $r = $this->api->add("Controller_PatternRouter");
        $r->setModel("Cms_Route");
        $r->route();
        // register new method for checking if configuration is accessible
    }
    function canConfigure(){
        /* should be redefined in custom cms if necessary controller */
        $r = $this->api->recall('cmsediting',false);
        return $r;
    }
}
