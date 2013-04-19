<?php
namespace trz;

class Page_Admin extends \Page {
    public $m = "trz/Transaction";
    function initMainPage(){
        $tabs = $this->tabs = $this->add("Tabs");
        $tabs->addTabURL("./all", "All Tranzactions");
        $tabs->addTabURL("./incomplete", "Incomplete");
        $tabs->addTabURL("./unverified", "Unverified");
    }
    function page_all(){
        $c=$this->add("CRUD",array('allow_del'=>false,'allow_add'=>false));
        $c->setModel($this->m);
        if ($c->grid){
            $c->grid->addPaginator(50);
            $c->grid->addQuickSearch(array("user", "name", "aux", "info", "method"));
        }
    }
    function page_incomplete(){
        $c=$this->add("CRUD",array('allow_del'=>false,'allow_add'=>false));
        $c->setModel($this->m)
            ->addCondition("is_completed", false);
        if ($c->grid){
            $c->grid->addPaginator(50);
            $c->grid->addQuickSearch(array("user", "name", "aux", "info", "method"));
        }
    }
    function page_unverified(){
        $c=$this->add("CRUD");
        $c->allow_add = false;
        $m=$c
            ->setModel($this->m)
            ->addCondition("is_verified", false);
        if ($c->grid){
            $c->grid->addColumn("button", "verified");
            if (isset($_GET["verified"])){
                if ($id = $_GET["verified"]){
                    $m->load($id)->verify();
                    $c->grid->js(null, $c->grid->js()->reload())->univ()->successMessage("Marked as verified")->execute();
                }
            }
            $c->grid->addPaginator(50);
            $c->grid->addQuickSearch(array("user", "name", "aux", "info", "method"));
        }
    }
}
