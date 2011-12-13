<?php

class Cms_YouTube extends Cms {
    function configureFields(){
        $this->m->addField("embed")->datatype("text")->allowHtml(true);
    }
    function configure($dest, $tag){
        return $dest->add("HtmlElement", null, $tag)->set($this->m->get("embed"));
    }
}
