<?php

class Cms_Menu extends Cms {
    function configureFields(){
        $this->m->addField("items")->datatype("text")->caption("Menu points separate with nl")->allowHtml(true);
    }
    function configure(){
        if ($i = $this->m->get("items")){
            $m = $this->add("Menu");
            $i = preg_replace("/([\n\r]+)/", ";", $i);
            $i = explode(";", $i);
            foreach ($i as $row){
                $row = explode("=", $row);
                $m->addMenuItem($row[1], $this->api->getDestinationURL($row[0], array("cms_page" => null)));
            }
        }
    }
}
