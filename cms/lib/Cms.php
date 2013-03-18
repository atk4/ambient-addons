<?php
namespace cms;
abstract class Cms extends \AbstractController{
    function init(){
        parent::init();
    }
    function showConfigureForm($target){
        $f = $target->add("Form", "configureForm");
        $f->setModel($this->m);
        $this->f = $f;
        $this->submit_btn = $f->addButton("Save Config");
        $this->submit_btn->js("click", $f->js()->submit());
        if ($f->isSubmitted()){
            $this->component->update(array("config" => base64_encode(serialize($f->get()))));
            if ($_GET["configure"] == "component"){
                $this->owner->reload();
            } else {
                $f->js(null, $f->js()->univ()->reloadParent())->univ()->closeDialog()->execute();
            }
        }
        return $f;
    }
    function loadData(){
        $data = unserialize(base64_decode($this->component->get("config")));
        $fields = @$this->m->fields?$this->m->fields:$this->m->elements;
        foreach ($fields as $name => $aux){
            $this->m->set($name, $data[$name]);
        }
    }
    function useComponent($component){
        $this->component = $component;
        $this->m = $this->add("cms/Model_Cms_Config");
        $this->configureFields();
        $this->loadData();
    }
    abstract function configure($dest, $tag);
}
