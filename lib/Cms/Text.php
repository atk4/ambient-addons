<?php

class Cms_Text extends Cms {
    function configureFields(){
        $this->m->addField("text")->datatype("text")->allowHtml(true);
    }
    function configure(){
        $this->add("Text")->set($this->m->get("text"));
    }
}
