<?php
namespace cms;
class Cms_Text extends Cms {
    function configureFields(){
        $this->m->addField("text")->datatype("text")->allowHtml(true);
    }
    function configure($dest, $tag){
        $dest->add("Text", null, $tag)->set($this->m->get("text"));
    }
}
