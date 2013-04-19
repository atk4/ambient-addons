<?php
/**
  * Implements rendering capabilities of CMS
  *
  * Editing capabilities are moved into CmsFrame.php
  */
namespace cms;
class Page_CmsCore extends Page_CmsAbstract {
    protected $protected_tags = array(); // non-configurable
    protected $allowed_tags= array(); // which tags to allow for editing in shared
    
    private $cms_page;
    protected $m;
    private $active;
    private $warning;
    public $stop_render = false;

    public $cms_page_model_class="cms/Model_Cms_Page";

    function getCmsAdminPage(){
        return $this->api->url('/cmsframe',array('cms_page'=>$this->cms_page));//api->page));
    }
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
            $this->warning("If you extends Page_CmsCore, move initialization to page_index from init");
        }
        $this->canConfigure();
    }
    function preInit(){
        $this->api->stickyGET("cms_page");
        $this->cms_page = $_GET["cms_page"];
        if (!$this->cms_page){
            $this->cms_page = $this->api->page;
        }
        $this->m = $this->add($this->cms_page_model_class);
        $this->active = $this->m->tryLoadBy("name", $this->cms_page);
        if ($this->active->loaded()){
            $this->m->tryLoad($this->active["id"]);
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
                $this->add("Text")->setHTML("Page <b>" . $this->cms_page . "</b> does not exist. Create now?");
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
                $this->m->getField('name')->system(true);
                $f->setModel($this->m);
                $f->addSubmit("Save");
                if ($f->isSubmitted()){
                    $f->update();
                    $this->reload();
                }
            } else if ($cid = $_GET["component_id"]){
                /* here? */

                $this->api->stickyGET("component_id");
                $m = $this->add("cms/Model_Cms_Component")->loadData($_GET["component_id"]);
                $m2 = $this->add($m->getRef("cms_componenttype_id")->get("class"));
                $m2->useComponent($m);
                $f = $m2->showConfigureForm($this);
                $b = $this->add("Button", "close".$cid)->set("Close");
                $b->js("click", $f->js()->univ()->closeDialog());
            
            } else {
                /* configuring tag */
                $m = $this->add("cms/Model_Cms_Pagecomponent");
                $m->addCondition("cms_page_id", $this->m->get("id"));
                $m->addCondition("template_spot", $c);
                $g = $this->add("MVCGrid");
                /* ordering should be done here */
                $g->setModel($m, array("id", "cms_component"));
                $g->add('cms/Controller_GridOrder');
                $g->addColumn("button", "setup");
                $g->addColumn("delete", "delete");
                if ($page_component_id = $_GET[$g->name . "_setup"]){
                    $m = $this->add("cms/Model_Cms_Pagecomponent");
                    $m->load($page_component_id);
                    if ($m->loaded()){
                        $component_id = $m->get("cms_component_id");

                        $g->js()->univ()->frameURL("Configure", $this->api->url($this->getCmsAdminPage()
                                    , array("component_id" => $component_id)))->execute();
                    } else {
                        $g->js()->univ()->alert("error - could not load $page_component_id?")->execute();
                    }
                }
                $this->add("Text")->set("Create new component");
                $f =$this->add("MVCForm");
                $f->setModel($mc=$this->add("cms/Model_Cms_Component"), array("name", "cms_componenttype_id"));
                $f->addSubmit("Create");
                if ($f->isSubmitted()){
                    $f->update();
                    $mc->update(array("is_enabled" => true));
                    $m->update(array("cms_component_id" => $mc->get("id")));
                    $f->js(null, $g->js(null, $f->js()->reload())->reload()->execute())->univ()->successMessage("Component has been created");
                }
                $this->add("Text")->set("Attach existing component");
                $f =$this->add("Form");
                $f->addField("Dropdown", "component")->setModel("cms/Cms_Component");
                $f->addSubmit("Attach");
                if ($f->isSubmitted()){
                    $m->update(array("cms_component_id" => $f->get("component")));
                    $f->js(null, $g->js(null, $f->js()->reload())->reload()->execute())->univ()->successMessage("Component has been attached");
                }

                
                $this->add("Button", "close")->set("Close")->js("click")->univ()->location($this->stripUrl($this->cms_page));
            }
        } else {
            
            if ($this->showConfigure('dev')){
                $this->conf->add("Button")->set("Page settings")->js("click")
                    ->univ()->frameURL("Page settings", $this->api->url($this->getCmsAdminPage()
                                , array("configure" => "page")));
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
            $mc = $this->add("cms/Model_Cms_Component");
            foreach ($tags as $tag){
                if (in_array($tag, $this->protected_tags)){
                    continue;
                }
                if (preg_match("/^_.*/", $tag)){
                    continue;
                }
                if (!preg_match("/#[0-9]+$/", $tag) && !in_array($tag, array("_page", "_name"))){
                    if ($this->showConfigure('dev')){
                        $this->conf->add("Button")->set("Spot: $tag")->js("click")
                            ->univ()->frameURL("Mangage content of tag $tag",
                                    $this->api->url($this->getCmsAdminPage()
                                        , array("configure" => $tag)));
                    }
                    $m = $this->add("cms/Model_Cms_Pagecomponent")->addCondition("cms_page_id", $this->m->get("id"));
                    $elems = $m->addCondition("template_spot", $tag)->setOrder("ord")->getRows();
                    if (($tag != "Content") && in_array($tag, $api_tags)){
                        $dest = $this->api;
                    } else {
                        $dest = $this;
                    }
                    if ($elems){
                        foreach ($elems as $e){
                            $component = $m->loadData($e["id"])->getRef("cms_component_id");
                            $driver = $component->getRef("cms_componenttype_id");
                            $obj=null;

                            if ($this->showConfigure()){
                                $button = $dest->add("Button", null, $tag);
                                //if($obj)$button->js('mouseover',$obj->js()->fadeOut()->fadeIn());
                                $this->api->stickyGET("cms_page");
                                $button->set("Edit '" . $component->get("name")."'")->js("click")
                                    ->univ()->frameURL("Configure " . $component->get("name"),
                                            $this->api->url($this->getCmsAdminPage()
                                                , array("configure" => "component", "component_id" => $component->get("id"))));
                            }

                            if ($component->get("is_enabled")){
                                $element = $this->add($driver->get("class"), null, $tag);
                                $element->useComponent($component);
                                try {
                                    $obj = $element->configure($dest, $tag);
                                } catch (Exception $e){
                                    //$this->api->caughtException($e);
                                    if($this->api->logger->log_output){
                                        // a bit of hacing
                                        $this->api->logger->logCaughtException($e);
                                    }
                                    $dest->add('View_Error')->set('Problem with this widget: '.$e->getMessage());
                                }
                            }
                        }
                    }
                    /*
                    $dest->add('Button',null,$tag)->set('Add Text')
                        ->js('click');
                        */
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
        return $this->js()->univ()->location($this->stripUrl($this->cms_page));
    }
    function redirect(){
        $this->api->redirect($this->stripUrl($this->cms_page));
    }
    function initializeTemplate($template_spot=null,$template_branch=null){
        $this->preInit();
        if($_GET['configure']){
            $template_branch=array('page');
        }
        return parent::initializeTemplate($template_spot,$template_branch);
    }
    function defaultTemplate(){
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
        if (!$this->api->cms){
            throw $this->exception("CMS has not been added to your api. Consult documentation");
        }
        if (!$this->api->canConfigureCms()){
            return;
        }
        if ($status = $_GET["showConfigure"]){
            $this->api->memorize("showConfigure", $status);
            if ($status == "off"){
                $this->api->forget("cmsediting");
                $this->redirect();
                //header('Location: /admin/');
                exit;
            }
        }
        $this->api->jui->addStylesheet("cms");
        $this->conf = $this->api->add("View", null, null, array("view/configure-panel"));
        $this->conf->add("Button")->set("Exit CMS")->js("click")->univ()->location($this->api->url(null, array("showConfigure" => "off")));
    }
    function showConfigure($level=null){
        if ($level && ($this->getCmsLevel() !== $level)){
            return;
        }
        return $this->api->recall('cmsediting',false);
    }
    function getCmsLevel(){
        return $this->api->recall('cmslevel', false);
    }
    function stripUrl($page){
        $page = preg_replace("/.html/", "", $page);
        $s = $this->add('cms/NoArgURL')->setPage($page);
    }

}
class NoArgURL extends \URL {
    function addStickyArguments(){
    }
}
