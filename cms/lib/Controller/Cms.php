<?php
namespace cms;
class Controller_Cms extends \AbstractController {
    function init(){
        parent::init();

        $l=$this->api->locate('addons',__NAMESPACE__,'location');
        $lp=$this->api->locate('addons',__NAMESPACE__);

        $this->api->addLocation($lp,array(
                    'template'=>'templates/default',
                    'css'=>'templates/default/css',
                    'js'=>'templates/js'
                    )
                )
                ->setParent($l);

        $this->owner->cms = $this;
        $this->owner->addMethod("canConfigureCms", array($this, "canConfigure"));
        // 
        /*if($this->api->page=='cmsframe'){
            $this->api->page_object = $this->api->add('cms/Page_CmsFrame');
            return;
        }*/

        $r = $this->api->add("router/Controller_PatternRouter");
        $r->setModel($this->add("cms/Model_Cms_Route"));
        $r->addRule("\/img\/(.*)", "cms", array("img"));
        $r->addRule("\/file\/(.*)", "cms", array("file"));
        $r->route();
        if (isset($this->api->auth)){
            $this->api->auth->allowPage("img");
        }
        if (($this->api->page == "cms")){
            $f=null;
            if ($_GET["img"]){
                /* pass through images */
                $f = $this->add("filestore/Model_Image")->tryLoad($_GET["img"]);
            } else if ($_GET["file"]){
                $f = $this->add("filestore/Model_File")->tryLoad($_GET["file"]);
            }
            if ($f){
                if ($f->loaded()){
                    session_write_close();
                    header("Content-type: " . $f->ref("filestore_type_id")->get("mime_type"));
                    print file_get_contents($f->getPath());
                    exit;
                } else {
                    var_duMP($_GET);
                    echo "could not load requested file";
                    exit;
                }
            }
        }
        /* set tags */
        $t = $this->add("cms/Model_Cms_Tag")->getRows();
        if ($t){
            foreach ($t as $v){
                $this->api->template->trySet($v["name"], $v["value"]);
            }
            $obj = $this;
            $this->api->addHook("pre-render", function() use ($t,$obj){
                $obj->api->hook("cms-tags", array($t));
            });
        }
        // register new method for checking if configuration is accessible
    }
    function canConfigure(){
        /* should be redefined in custom cms if necessary controller */
        $r = $this->api->recall('cmsediting',false);
        return $r;
    }
}
