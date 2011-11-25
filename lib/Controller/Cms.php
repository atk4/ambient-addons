<?php

class Controller_Cms extends AbstractController {
    function init(){
        parent::init();
        $r = $this->api->add("Controller_PatternRouter");
        $r->setModel("Cms_Route");
        $r->route();
    }
}
