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
        $r->addRule("img\/(.*)", "cms", array("img"));
        $r->route();
        $this->api->auth->allowPage("img");
        if (($this->api->page == "cms") && $_GET["img"]){
            /* pass through files */
            $f = $this->add("Model_Filestore_File")->loadData($_GET["img"]);
            if ($f->isInstanceLoaded()){
                session_write_close();
                header("Content-type: image/jpeg");
                print file_get_contents($f->getPath());
                exit;
            }
        }
        // register new method for checking if configuration is accessible
    }
    function canConfigure(){
        /* should be redefined in custom cms if necessary controller */
        $r = $this->api->recall('cmsediting',false);
        return $r;
    }
}
