<?php

abstract class Cms extends AbstractController{
    function init(){
        parent::init();
    }
    function showConfigureForm($target){
        $f = $target->add("MVCForm", "configureForm");
        $f->setModel($this->m);
        $f->addSubmit("Save Config");
        if ($f->isSubmitted()){
            $this->component->update(array("config" => base64_encode(serialize($f->getAllData()))));
            if ($_GET["configure"] == "component"){
                $this->owner->reload();
            } else {
                $f->js($f->js()->univ()->reloadParent())->univ()->closeDialog()->execute();
            }
        }
        return $f;
    }
    function loadData(){
        $data = unserialize(base64_decode($this->component->get("config")));
        $fields = $this->m->getField();
        foreach ($fields as $name => $aux){
            $this->m->set($name, $data[$name]);
        }
    }
    function useComponent($component){
        $this->component = $component;
        $this->m = $this->add("Model_Cms_Config");
        $this->configureFields();
        $this->loadData();
    }
    abstract function configure($dest, $tag);
}
