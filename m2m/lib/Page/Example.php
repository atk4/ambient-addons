<?php
namespace m2m;

class Page_Example extends \Page {
    function initMainPage(){
        $t=$this->add("Tabs");
        $t->addTabURL($this->api->url("./users"), "Users");
        $t->addTabURL($this->api->url("./items"), "Items");
    }
    function page_users(){
        $g=$this->add("Grid");
        $g->setModel("User", array("name"));
        $g->addColumn("expander", "items1", array("descr" => "Demo 1"));
        $g->addColumn("expander", "items2", array("descr" => "Demo 2"));
    }
    function page_users_items1(){
        $this->api->stickyGET("user_id");
        $m=$this->add("Model_User")->load($_GET["user_id"]);
        $l=$this->add("m2m/View_List");
        $l->setModels($m, "Model_Item", "UserItem");
    }
    function page_users_items2(){
        $this->api->stickyGET("user_id");
        $m=$this->add("Model_User")->load($_GET["user_id"]);
        $l=$this->add("m2m/View_List");
        $l->setModels($m, "Model_Item", "UserItem");
        $l->toggleAutoComplete(true);
    }

    function page_items(){
        $this->add("CRUD")->setModel("Item");
    }
}
