<?php

namespace m2m;

class View_List extends \AbstractView {
    function init(){
        parent::init();
        $this->api->addHook("post-init", array($this, "initComponents"));
        $this->setOption("limit", 10);
        $this->setOption("columns", 1);
        $this->setOption("can_add", true);
        $this->setOption("grid_reload_event", "reload" . md5($this->name));
        $this->setOption("memorize_base", md5($this->name));
    }
    function initComponents(){
        $trigger = $this->name . "_edit";
        if (isset($_GET[$trigger])){
            $this->api->stickyGET($trigger);
            $this->initEditView();
        } else {
            $this->grid = $this->add("Grid");
            $this->grid->setModel($this->o["m3"], array($this->o["m2_title_field"]));
            $this->grid->addFormatter($this->o["m2_title_field"], "wraptext");
            $g = $this->grid;
            $this->grid->addMethod("format_wraptext", function($o,$f,$options=null) use($g){
                $what = $g->current_row[$f];
                $ret = wordwrap($what, isset($options["len"])?$options["len"]:60, "<br />", true);
                $g->current_row_html[$f] = $ret;
            });

            $this->grid->addColumn("delete", "delete");
            $this->js(true)->_selector("body")->unbind($this->o["grid_reload_event"])->bind($this->o["grid_reload_event"], $this->grid->js()->reload()->_enclose());

            $this->add("Button")->set("Edit List")
                ->js("click",
                    $this->js()
                        ->univ()->frameURL("Edit List",
                    $this->api->url(null, array($this->name . "_edit" => 1, "cut_object"=>$this->name)))
                ); 
        }
    }
    function setModels($m1,$m2,$m3){
        /* we require m1 to be object and loaded */
        if (!($m1 instanceof \Model_Table)){
            throw $this->exception("Sorry, we know how to work only with Model_Table");
        }
        if (!$m1->loaded()){
            throw $this->exception("Sorry, at this time M1 must be existing and loaded");
        }
        $this->setOption("m1", $m1);
        $this->setOption("m3", $m1->ref($m3));
        if (strstr($m2, "Model")){
            $m2 = $this->add($m2);
        }
        $total = $m2->count()->getOne();

        $this->setOption("m2_field", strtr(strtolower(get_class($m2)), array("model_"=>"")) . "_id"); // core assumption
        $this->setOption("m2_title_field", strtr(strtolower(get_class($m2)), array("model_"=>""))); // core assumption
        $this->setOption("m2", $m2);
        $this->setOption("m2_total", $total);
    }
    function setCategory($field){
        $this->setOption("cat_field", $field);
    }
    function toggleAdd($mode=false){
        $this->setOption("can_add", $mode);
    }
    function toggleAutoComplete($mode=false){
        $this->setOption("autocomplete", $mode);
    }
    function setOption($key, $value){
        $this->o[$key] = $value;
    }
    /* init edit view */
    function initEditView(){
        /* high level */
        $this->ui = $this->add("View");
        $this->addFilterForm();
        $this->addAutocompleteForm();
        $this->addM2Form();
        $this->addNewRecordForm();
        $this->ui->add("Button")->set("Close")
            ->js("click")->univ()->closeDialog();
    }
    function reloadGrid(){
        return $this->js()->_selector("body")->trigger($this->o["grid_reload_event"]);
    }
    /* implement those */
    function addFilterForm(){
        /* adds limiter to our M2 object */
        /* normally, this would require some more information about M2,
         * e.g. if it has category, we could render category selector.
         *
         * anyhow, it would apply limitations to m2 object.
         * */
        if (!$this->o["cat_field"]){
            return;
        }
        $current = $this->recall($key=$this->o["memorize_base"] . "_category_id");
        $f=$this->ui->add("Form");
        $ff = $f->addField("Dropdown", "category_id");
        $ff->setModel($this->o["m2"]->ref($this->o["cat_field"]));
        $ff->setCaption("Filter");
        $ff->js("change", $f->js()->submit());
        if ($current){
            $ff->set($current);
            $this->o["m2"]->addCondition($this->o["cat_field"], $current);
        }
        if ($f->isSubmitted()){
            $this->memorize($key, $f->get("category_id"));
            $this->ui->js()->reload()->execute();
        }
    }
    function addM2Form(){
        /* this would render M2 entries and join them */
        /* optionally add paginator */
        $total = $this->o["m2_total"];
        if($total == 0) {
            $this->ui->add('View_Error')->set('No options available');
        }
        $f=$this->ui->add("Form");
        $f->js(true)->addClass("ignore_changes");
        $f->setFormClass("atk-row");
        $span_width = 12/$this->o["columns"];
        $f->template->trySet("fieldset","span".$span_width);
        
        /* apply limit */

        $p=$f->add('Paginator', null, "Content", array("paginator","paginator2"));
        $p->ipp($this->o["limit"]);
        $in_col = round($this->o["limit"] / $this->o["columns"]);
        /* limit o["m2"], if we have auto complete */
        if ($autocomplete = $this->o["autocomplete"]){
            $p->setSource($this->o["m3"]);
        } else {
            $p->setSource($this->o["m2"]);
        }


        /* get exiting records */
        $ex = $this->o["m3"];
        $field = $this->o["m2_field"];
        
        $existing = array();

        foreach ($ex as $exf){
            /* field? this is the m3_id - and we get it how? */
            $existing[$exf[$field]] = true;
        }
 
        foreach ($this->o["m2"] as $key => $row){
            $id = $row["id"];
            if (!isset($existing[$id]) && $autocomplete){
                continue;
            }
            $name = $row[$this->o["m2"]->title_field];
            $features[$id] = $name;
            $ff = $f->addField("Checkbox", "feature" . $id)->setCaption($name);
            $ff->js("change", $f->js()->submit());
            if (isset($existing[$id])){
                $ff->set(1);
            }
            $counter++;
            if ($counter >= $in_col){
                /* divider ? */
                $f->addSeparator("span4");
                $counter = 0;
            }
        }
        if ($f->isSubmitted()){
            /* store new records */
            $new = array();
            $remove = array();
            foreach ($features as $id => $name){
                if ($f->get("feature" . $id)){
                    $ex->unload()
                        ->tryLoadBy($field, $id)
                        ->set($field, $id)
                        ->save();
                } else {
                    if (isset($existing[$id])){
                        $remove[$id] = true;
                    }
                }
            }
            if (!empty($remove)){
                foreach ($remove as $id => $tmp){
                    $ex->tryLoadBy($field, $id);
                    if ($ex->loaded()){
                        $ex->delete();
                    }
               }
            }
            $f->js(null, $this->reloadGrid())
                ->execute();

        }
        /* let's add paginator and see if it works */

    }
    function addNewRecordForm(){
        /* add new record */
        if (!$this->o["can_add"] || $this->o["autocomplete"]){
            return;
        }
        $this->ui->add("H3")->set("Add new entity");
        $f=$this->ui->add("Form");
        $f->setModel($this->o["m2"], array($this->o["m2"]->title_field));
        $f->addSubmit("Add");
        if ($f->isSubmitted()){
            /* link together */
            if (!$this->o["m2"]->tryLoadBy($this->o["m2"]->title_field, $f->get($this->o["m2"]->title_field))->loaded()){
                $f->update();
            }
            if (!$this->o["m3"]
                ->tryLoadBy($this->o["m2_field"], $f->getModel()->get("id"))
                ->loaded()){
                $this->o["m3"]
                    ->set($this->o["m2_field"], $f->getModel()->get("id"))
                    ->save();
            }
            $this->ui->js(null, $this->reloadGrid())->reload()->execute();
        }
    }
    function addAutocompleteForm(){
        if (!$this->o["autocomplete"]){
            return;
        }
        $f=$this->ui->add("Form");
        $ff = $f->addField("autocomplete/" . ($this->o["can_add"]?"plus":"basic"), "item")
            ->validateNotNull()
            ->setModel($this->o["m2"])
            ;
        $f->addSubmit("Add");
        if ($f->isSubmitted()){
            $id = $f->get("item");
            if (!$id){
                $f->displayError("item", "Please select an item");
            }
            $ex = $this->o["m3"];
            $field = $this->o["m2_field"];
            $ex->unload()
                ->tryLoadBy($field, $id)
                ->set($field, $id)
                ->save();
            $this->ui->js(null, $this->reloadGrid())->reload()->execute();
        }
    }

}
