<?php

class Page_CmsAdmin extends Page {
    function initMainPage(){
        $t = $this->add("Tabs");
        $t->addTabURL($this->api->getDestinationURL("./route"), "Routing");
        $t->addTabURL($this->api->getDestinationURL("./component"), "Component");
        $t->addTabURL($this->api->getDestinationURL("./componenttype"), "Component types");
        $t->addTabURL($this->api->getDestinationURL("./page"), "Pages");
        $t->addTabURL($this->api->getDestinationURL("./pagecomponent"), "Page components");
        $t->addTabURL($this->api->getDestinationURL("./tag"), "Tags");

    }
    function page_component(){
        $c = $this->add("CRUD");
        $c->setModel("Cms_Component");
    }
    function page_tag(){
        $c = $this->add("CRUD");
        $c->setModel("Cms_Tag");
    }
    function page_componenttype(){
        $this->add("CRUD")->setModel("Cms_Componenttype");
    }
    function page_page(){
        $c=$this->add("CRUD");
        $c->setModel("Cms_Page");
        if ($c->grid){
            $c->grid->addColumn("expander", "components");
        }
    }
    function page_page_components(){
        $this->api->stickyGET("cms_page_id");
        $id = $_GET["cms_page_id"];
        $c = $this->add("CRUD");
        $m = $this->add("Model_Cms_Pagecomponent")->setMasterField("cms_page_id", $id);
        $c->setModel($m);

    }
    function page_pagecomponent(){
        $this->add("CRUD")->setModel("Cms_Pagecomponent");
    }
    function page_route(){
        $this->add("CRUD")->setModel("Cms_Route");
    }
}
