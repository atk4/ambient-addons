<?php

class Page_CmsCore extends Page {
    protected $protected_tags = array(); // non-configurable
    protected $allowed_tags= array(); // which tags to allow for editing in shared
    
    private $cms_page;
    private $m;
    private $active;
    private $warning;
    public $stop_render = false;
    public function removeProtectedTag($tag){
        unset($this->protected_tags[$tag]);
    }
    public function addProtectedTag($tag){
        if (!in_array($tag, $this->protected_tags)){
            $this->protected_tags[] = $tag;
        }
    }
    public function removeAllowedTag($tag){
        unset($this->allowed_tags[$tag]);
    }
    public function addAllowedTag($tag){
        if (!in_array($tag, $this->allowed_tags)){
            $this->allowed_tags[] = $tag;
        }
    }
    function init(){
        parent::init();
        if ((count($this->elements) > 1) && (get_class($this) != "Page_CmsCore")){
            $this->warning("If you extends Page_CmsCore, move initialization to initMainPage from init");
        }
        $this->canConfigure();
    }
    function preInit(){
        $this->api->stickyGET("cms_page");
        $this->cms_page = $_GET["cms_page"];
        if (!$this->cms_page){
            $this->cms_page = $this->api->page;
        }
        $this->m = $this->add("Model_Cms_Page");
        $this->active = $this->m->getBy("name", $this->cms_page);
        if ($this->active){
            $this->m->loadData($this->active["id"]);
        } else {
            // page does not exist
        }
    }
    function initMainPage(){
        if ($this->m && $this->m->isInstanceLoaded()){
            /* page exists */
            $this->initPage();
        } else {
            /* based on config, should check if authorized, user offer to 
             * create new page */
            if ($this->showConfigure()){
                $this->add("Text")->set("Page <b>" . $this->cms_page . "</b> does not exist. Create now?");
                $f = $this->add("Form");
                $f->addField("Checkbox", "create")->setCaption("Yes, please");
                $f->addSubmit("Create");
                if ($f->isSubmitted()){
                    $this->m->update(array("name" => $this->cms_page));
                    $this->reload();
                }
            }
        }
    }
    function initPage(){
        /* load components, and add as necessary */
        if (($c = $_GET["configure"]) && $this->showConfigure()){
            $this->stop_render = true;
            $this->api->stickyGET("configure");
            if ($c == "page"){
                $f = $this->add("MVCForm");
                $f->add("Hint")->set("Leave blank unless you know what you do");
                $f->setModel($this->m->setActualFields(array("api_layout", "page_layout")));
                $f->addSubmit("Save");
                if ($f->isSubmitted()){
                    $f->update();
                    $this->reload();
                }
            } else if ($cid = $_GET["component_id"]){
                $this->api->stickyGET("component_id");
                $m = $this->add("Model_Cms_Component")->loadData($_GET["component_id"]);
                $m2 = $this->add($m->getRef("cms_componenttype_id")->get("class"));
                $m2->useComponent($m);
                $f = $m2->showConfigureForm($this);
                $b = $this->add("Button", "close".$cid)->set("Close");
                $b->js("click", $f->js()->univ()->closeDialog());
            
            } else {
                /* configuring tag */
                $m = $this->add("Model_Cms_Pagecomponent");
                $m->setMasterField("cms_page_id", $this->m->get("id"));
                $m->setMasterField("template_spot", $c);
                $g = $this->add("MVCGrid");
                /* ordering should be done here */
                $g->setModel($m, array("id", "cms_component"));
                $g->add('Controller_GridOrder');
                $g->addColumn("button", "setup");
                $g->addColumn("delete", "delete");
                if ($page_component_id = $_GET[$g->name . "_setup"]){
                    $m->loadData($page_component_id);
                    if ($m->isInstanceLoaded()){
                        $component_id = $m->get("cms_component_id");
                        $g->js()->univ()->frameURL("Configure", $this->api->getDestinationURL(null, array("component_id" => $component_id)))->execute();
                    } else {
                        $g->js()->univ()->alert("error - could not load $page_component_id?")->execute();
                    }
                }
                $this->add("Text")->set("Create new component");
                $f =$this->add("MVCForm");
                $f->setModel($mc=$this->add("Model_Cms_Component"), array("name", "cms_componenttype_id"));
                $f->addSubmit("Create");
                if ($f->isSubmitted()){
                    $f->update();
                    $mc->update(array("is_enabled" => true));
                    $m->update(array("cms_component_id" => $mc->get("id")));
                    $f->js(null, $g->js(null, $f->js()->reload())->reload()->execute())->univ()->successMessage("Component has been created");
                }
                $this->add("Text")->set("Attach existing component");
                $f =$this->add("Form");
                $f->addField("Dropdown", "component")->setModel("Cms_Component");
                $f->addSubmit("Attach");
                if ($f->isSubmitted()){
                    $m->update(array("cms_component_id" => $f->get("component")));
                    $f->js(null, $g->js(null, $f->js()->reload())->reload()->execute())->univ()->successMessage("Component has been attached");
                }

                
                $this->add("Button", "close")->set("Close")->js("click")->univ()->location("/" . $this->cms_page);
            }
        } else {
            
            if ($this->showConfigure()){
                $this->conf->add("Button")->set("Page settings")->js("click")
                    ->univ()->frameURL("Page settings", $this->api->getDestinationURL(null, array("configure" => "page")));
            }
            /* add configure buttons for each "tag" */
            $tags = array_keys($this->template->tags);
            $api_tags = array_keys($this->api->template->tags);
            foreach ($api_tags as $tag){
                if (in_array($tag, $this->allowed_tags)){
                    if (!in_array($tag, $tags)){
                        $tags[] = $tag;
                    }
                }
            }
            $mc = $this->add("Model_Cms_Component");
            foreach ($tags as $tag){
                if (in_array($tag, $this->protected_tags)){
                    continue;
                }
                if (preg_match("/^_.*/", $tag)){
                    continue;
                }
                if (!preg_match("/#[0-9]+$/", $tag) && !in_array($tag, array("_page", "_name"))){
                    if ($this->showConfigure()){
                        $this->conf->add("Button")->set("Spot: $tag")->js("click")
                            ->univ()->frameURL("Mangage content of tag $tag", $this->api->getDestinationURL(null, array("configure" => $tag)));
                    }
                    $m = $this->add("Model_Cms_Pagecomponent")->setMasterField("cms_page_id", $this->m->get("id"));
                    $elems = $m->addCondition("template_spot", $tag)->setOrder(null, "ord")->getRows();
                    if ($elems){
                        foreach ($elems as $e){
                            $component = $m->loadData($e["id"])->getRef("cms_component_id");
                            $driver = $component->getRef("cms_componenttype_id");
                            if ($component->get("is_enabled")){
                                if (($tag != "Content") && in_array($tag, $api_tags)){
                                    $dest = $this->api;
                                } else {
                                    $dest = $this;
                                }
                                $element = $this->add($driver->get("class"), null, $tag);
                                $element->useComponent($component);
                                $element->configure($dest, $tag);
                            }
                            if ($this->showConfigure()){
                                $this->conf->add("Button")->set("Edit '" . $component->get("name")."'")->js("click")
                                    ->univ()->frameURL("Configure " . $component->get("name"), $this->api->getDestinationURL(null, array("configure" => "component", "component_id" => $component->get("id"))));
                            }
                        }
                    }
                }
            }
            if ($this->showConfigure()){
                if ($this->warning){
                    $this->add("Text")->set("<div style=\"color:red; background: yellow\"><b>Warning:</b><br />" . implode("<br />", $this->warning) . "</div>");
                }
            }
        }
    }
    function reload(){
        $this->reloadJS()->execute();
    }
    function reloadJS(){
        return $this->js()->univ()->location("/" . $this->cms_page);
    }
    function redirect(){
        header("Location: " ."/" . $this->cms_page);
        exit;
    }
    function defaultTemplate(){
        $this->preInit();
        if ($this->active && (!$_GET["configure"] || !$this->showConfigure())){
            if ($l = $this->active["page_layout"]){
                try {
                    if ($this->api->locate("template", $l . ".html")){
                       return array($this->active["page_layout"]);
                    }
                } catch (Exception $e){
                    $this->warning("Specified page layout <b>$l</b> does not exist. Using default");
                }
            }
        }
        /* this might depend on the page */
        return parent::defaultTemplate();
    }
    function warning($msg){
        $this->warning[] = $msg;
    }
    function canConfigure(){
        if ($status = $_GET["showConfigure"]){
            $this->api->memorize("showConfigure", $status);
            $this->redirect();
        }
        $this->api->jui->addStylesheet("cms");
        $this->conf = $this->api->add("View", null, null, array("view/configure-panel"));
        if ($this->showConfigure()){
            $this->conf->add("Button")->set("Turn off editing")->js("click")->univ()->location($this->api->getDestinationURL(null, array("showConfigure" => "off")));
        } else {
            $this->conf->add("Button")->set("Turn on editing")->js("click")->univ()->location($this->api->getDestinationURL(null, array("showConfigure" => "on")));
        }
    }
    function showConfigure(){
        if ($this->api->recall("showConfigure") == "on"){
            return true;
        } else {
            return false;
        }
    }
}
class Controller_GridOrder extends AbstractController {
    public $model;

    function init(){
        parent::init();

        $this->owner->addButton('Re-order records')
            ->js('click')->univ()->frameURL('Re-order records',
                    $this->api->getDestinationURL(null,
                        array($this->name=>'activate')),array('width'=>'500px'));

        $this->owner->dq->order('ord');

        if($_GET[$this->name]=='activate'){
            $this->api->stickyGET($this->name);
            $this->initFrame();
        }
    }
    function initFrame(){
        $v=$this->owner->owner->add('View',null,$this->owner->spot);
        $_GET['cut_object']=$v->name;

        $v->add('H1')->set('Re-order records the way you like');


        $lister=$v->add('MVCLister',null,null,array('view/gridorder'));
        $this->model=$m=$this->owner->getModel();

        if(!$m->hasField('ord')){
            $m->addField('ord')->system(true);
        }

        $lister->setModel($m);
        $lister->dq->order('ord');

        $lister->js(true)->sortable();

        $v->add('Button')->set('Save')->js('click')->univ()->ajaxec(
                array($this->api->getDestinationURL(),
                $this->name.'_order'=>$v->js(null,"\$('#{$lister->name}').children().map(function(){ return $(this).attr('data-id'); }).get().join(',')")
                )
            );
        if(isset($_GET[$this->name.'_order'])){
            $this->processReorder($_GET[$this->name.'_order']);
            $v->js(null,$this->owner->js()->reload(array($this->name=>false)))->univ()->closeDialog()->successMessage('New order saved')->execute();
        }
    }
    function processReorder($id_order){
        // add missing "ord" fields
        $q=$this->model->dsql();
        $q->set('ord=id');
        $q->where('ord is null');
        $q->do_update();

        $q=$this->model->dsql()->field('id')->field('ord');
        $seq=$q->do_getAllHash();

        // extract ORDs
        $ord=array();
        foreach($seq as $key=>$val){
            $ord[]=$val['ord'];
        }

        sort($ord);
        
        foreach(explode(',',$id_order) as $id){
            $q=$this->model->dsql();
            $q->set('ord',array_shift($ord));
            $q->where('id',$id);
            $q->do_update();
        }
    }
}
