<?php

class Cms_Redirect extends Cms {
    function configureFields(){
        $this->m->addField("url");
    }
    function configure($dest, $tag){
        if (!$this->owner->showConfigure()){
            header("Location: " . $this->m->get("url"));
        }
    }
}
