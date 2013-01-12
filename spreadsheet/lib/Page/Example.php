<?php
namespace spreadsheet;

class Page_Example extends Page {
    function initMainPage(){
        $this->add("Hint")
            ->set("When you create new spreadsheet dimensions, template will be created and cached in session - initial lag can be experienced");
        $c=$this->add("CRUD", array("allow_del" => false, "allow_edit" => false, "allow_add" => false));
        try {
            $c->setModel("spreadsheet/SpreadSheet");
            if ($c->grid){
                $c->grid->addColumn("expander", "cells");
            }
        } catch (Exception_ValidityCheck $e){
            $c->form->displayError($e->getField(), $e->getMessage());
        }
    }
    function page_cells(){
        $this->api->stickyGET("id");
        $m = $this->add("spreadsheet/Model_SpreadSheet")->load($_GET["id"]);
        $v = $this->add("spreadsheet/View_SpreadSheet");
        $v->setModel($m);
    }
}
