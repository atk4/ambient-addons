<?php
namespace cms;
class Page_CmsAdmin extends \Page {
    function initMainPage(){
        $t = $this->add("Tabs");
        $t->addTabURL($this->api->getDestinationURL("./route"), "Routing");
        $t->addTabURL($this->api->getDestinationURL("./component"), "Component");
        $t->addTabURL($this->api->getDestinationURL("./componenttype"), "Component types");
        $t->addTabURL($this->api->getDestinationURL("./page"), "Pages");
        $t->addTabURL($this->api->getDestinationURL("./pagecomponent"), "Page components");
        $t->addTabURL($this->api->getDestinationURL("./tag"), "Tags");
        $t->addTabURL($this->api->getDestinationURL("./cmson"), "Go CMS");
    }
    function page_component(){
        $c = $this->add("CRUD");
        $c->setModel("cms/Cms_Component");
    }
    function page_tag(){
        $c = $this->add("CRUD");
        $c->setModel("cms/Cms_Tag");
    }
    function page_componenttype(){
        $this->add("CRUD")->setModel("cms/Cms_Componenttype");
    }
    function page_page(){
        $c=$this->add("CRUD");
        $c->setModel("cms/Cms_Page");
        if ($c->grid){
            $c->grid->addColumn("expander", "components");
        }
    }
    function page_page_components(){
        $this->api->stickyGET("cms_page_id");
        $id = $_GET["cms_page_id"];
        $c = $this->add("CRUD");
        $m = $this->add("cms/Model_Cms_Pagecomponent")->setMasterField("cms_page_id", $id);
        $c->setModel($m);

    }
    function page_pagecomponent(){
        $this->add("CRUD")->setModel("cms/Cms_Pagecomponent");
    }
    function page_route(){
        $this->add("CRUD")->setModel("cms/Cms_Route");
    }
    function page_cmson(){
        $this->add("Text")->set("To access CMS mode, click Go!");
        $this->add("Button")->set("Go!")->js("click")->univ()->newWindow($this->api->url("./launch"), "_new");
    }
    function page_cmson_launch(){
        $this->level = "dev";
        session_destroy();
        $a=new \ApiWeb($this->api->getConfig("frontend/token"));
        $a->initializeSession(true);
        $a->memorize('cmsediting',true);
        $a->memorize('cmslevel',$this->level); // switch to true to have plain cms mode
        header('Location: '.$this->api->pm->base_path.'..');
    }
}