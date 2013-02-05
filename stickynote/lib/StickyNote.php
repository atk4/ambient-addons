<?php
namespace stickynote;

class StickyNote extends \AbstractController {
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
        $this->api->template->appendHTML("js_include", "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->api->locateURL('css','stickynote.css') . "\"/>");


        $vp = $this->add("VirtualPage");
        $this->owner->add("Button")->set("Add Sticky")->addClass("sticky-add")->js("click")
            ->univ()->frameURL("Add Note", $vp->getURL());
        $self = $this->owner;
        /* existing */
        $m = $this->add("stickynote/Model_StickyNote");
        $base = $this->api->url();
        $m->_dsql()->where($m->dsql()->expr("url = '[1]' or is_global = 'Y'")->setCustom("1", (string) $base));
        $ref = array();
        foreach ($m as $note){
            $v=$this->owner->add("View", null, null, array("view/stickynote"));
            $v->template->trySet($note);
            $v->js(true)->draggable(array("stop" => $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"])), array("pos" => $v->js()->position()))->_enclose()));
            $v->js(true)->css("left", $note["x"] ."px");
            $v->js(true)->css("top", $note["y"] . "px");
            $v->js(true)->find(".edit")->one("click", $v->js()->univ()->frameURL("Edit Note", $vp->getURL($note["id"]))->_enclose());
            $v->js(true)->find(".del")->one("click", $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"], "delete" => true)))->_enclose());
            $ref[$note["id"]] = $v->js()->reload();
            $refd[$note["id"]] = $v->js()->detach();
        }


        $vp->set(function($p) use ($vp,$self, $ref, $base){
            $m = $this->add("stickynote/Model_StickyNote");
            $id = $_GET[$vp->name];
            if ((int)$id){
                $m->load($id);
            }
            $f=$p->add("Form");
            $f->setModel($m, array("content", "is_global", "color"));
            $f->addSubmit();
            if ($f->isSubmitted()){
                $f->update();
                $m=$f->getModel();
                if (!$m["url"]){
                    $m->set("url", (string)$base)->save();
                }
                if ((int)$id){
                    $p->js(null, $ref[$id])->univ()->closeDialog()->execute();
                }
                $self->js(null, $p->js()->univ()->closeDialog())->univ()->location()->execute();
            }
        });

        if (isset($_GET["note"])){
            $m = $this->add("stickynote/Model_StickyNote");
            $m->load($_GET["note"]);
            if (isset($_GET["delete"])){
                $v=$refd[$m["id"]];
                $m->delete();
                $v->execute();
            }
            $m->set("x", (int)$_POST["pos"]["left"])->set("y", (int)$_POST["pos"]["top"])->save();
            $self->js()->execute();
        }
    }
}
