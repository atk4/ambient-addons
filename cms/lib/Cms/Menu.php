<?php

class Cms_Menu extends Cms {
    function configureFields(){
        $this->m->addField("items")->datatype("text")->caption("Menu points separate with nl")->allowHtml(true);
    }
    function configure($dest, $tag){
        if ($i = $this->m->get("items")){
            $m = $dest->add("Menu", null, $tag);
            $i = preg_replace("/([\n\r]+)/", ";", $i);
            $i = explode(";", $i);
            foreach ($i as $row){
                $row = explode("=", $row);
                $m->addMenuItem($row[1], $this->api->url($row[0], array("cms_page" => null)));
            }
        }
    }
}
