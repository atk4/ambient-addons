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
        $this->add("CRUD",array('allow_del'=>false,'allow_add'=>false))
            ->setModel($this->m);
    }
    function page_incomplete(){
        $this->add("CRUD",array('allow_del'=>false,'allow_add'=>false))
            ->setModel($this->m)
            ->setMasterField("is_completed", false);
    }
    function page_unverified(){
        $c=$this->add("CRUD");
        $c->allow_add = false;
        $m=$c
            ->setModel($this->m)
            ->setMasterField("is_verified", false);
        if ($c->grid){
            $c->grid->addColumn("button", "verified");
            if (isset($_GET["verified"])){
                if ($id = $_GET["verified"]){
                    $m->load($id)->verify();
                    $c->grid->js(null, $c->grid->js()->reload())->univ()->successMessage("Marked as verified")->execute();
                    //$c->grid->js(true)->reload();
                    /*$c->grid->js(true)
                        ->univ()
                        ->successMessage("Marked as verified")
                        ->execute();*/
                }
            }
        }
    }
}
