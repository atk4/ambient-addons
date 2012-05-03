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
        if (isset($this->api->auth)){
            $this->api->auth->allowPage("img");
        }
        if (($this->api->page == "cms") && $_GET["img"]){
            /* pass through files */
            $f = $this->add("Model_Filestore_File")->loadData($_GET["img"]);
            if ($f->isInstanceLoaded()){
                session_write_close();
                header("Content-type: " . $t->getRef("filestore_type_id")->get("mime_type"));
                print file_get_contents($f->getPath());
                exit;
            }
        }
        /* set tags */
        $t = $this->add("Model_Cms_Tag")->getRows();
        if ($t){
            foreach ($t as $v){
                $this->api->template->trySet($v["name"], $v["value"]);
            }
            $obj = $this;
            $this->api->addHook("pre-render", function() use ($t,$obj){
                $obj->api->hook("cms-tags", array($t));
            });
        }
        // register new method for checking if configuration is accessible
    }
    function canConfigure(){
        /* should be redefined in custom cms if necessary controller */
        $r = $this->api->recall('cmsediting',false);
        return $r;
    }
}
