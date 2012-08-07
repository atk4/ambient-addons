<?php
namespace cms;
class Cms_CRUD extends Cms {
    function configureFields(){
        $l=$this->api->locatePath('php','Model');
        $this->addPath($l);
        $models = array();
        foreach ($this->paths as $path){
            $this->findModels($path, $models);
            $models=array_combine($models,$models);
        }
        $this->m->addField("model")->datatype("list")->listData($models);
        $this->m->addField("paginate");
        $this->m->addField("grid_fields");
        $this->m->addField("form_fields");
        $this->m->addField("can_add")->datatype("boolean");
        $this->m->addField("can_edit")->datatype("boolean");
        $this->m->addField("can_delete")->datatype("boolean");
    }
    function configure($dest, $tag){
        if ($this->m->get("model")){
            $c = $dest->add("CRUD", null, $tag);
            if ($this->m->get("grid_fields")){
                $a = explode(",", $this->m->get("grid_fields"));
            }
            if ($this->m->get("form_fields")){
                $b = explode(",", $this->m->get("form_fields"));
            }
            if ($c->grid){
                if ($x = $this->m->get("paginate")){
                    $c->grid->addPaginator($x);
                }
                if (!$this->m->get("can_add")){
                    unset($c->grid->elements[$c->add_button->short_name]);
                }
                if (!$this->m->get("can_edit")){
                    $c->allow_edit = false;
                }
                if (!$this->m->get("can_delete")){
                    $c->allow_del = false;
                }
            }
            $c->setModel($this->m->get("model"), empty($b)?null:$b, empty($a)?null:$a);

        } else {
            $this->add("Text")->set("Configure CRUD before using");
        }
    }
    function findModels($dir, &$models, $prefix = null){
        $d=dir($dir);
        $fetch = array();
        while(false !== ($entry=$d->read())){
            if (in_array($entry, array(".", ".."))){
                continue;
            }
            if (is_dir($dir . DIRECTORY_SEPARATOR . $entry)){
                $fetch[] = $entry;
                continue;
            }
            $m=str_replace('.php','',$entry);
            if($m[0]=='.')continue;
            $models[]=$prefix . $m;
        }
        $d->close();
        if ($fetch){
            foreach ($fetch as $entry){
                $this->findModels($dir . DIRECTORY_SEPARATOR .  $entry, $models, $entry . "_");
            }
        }
    }
    function addPath($path){
        $this->paths[] = $path;
    }
}
