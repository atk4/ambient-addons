<?php
namespace stickynote;

class StickyNote extends \AbstractController {
    public $can_edit = true;
    public $can_add = true;
    public $can_delete = true;
    public $can_resize = true;
    public $can_move = true;
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
        if ($this->can_add){
            $this->owner->add("Button")->set("Add Sticky")->addClass("sticky-add")->js("click")
                ->univ()->frameURL("Add Note", $vp->getURL(), array("width" => "400", "dialogClass" => "sticky-note-form"));
        }
        $owner = $this->owner;
        $self = $this;
        /* existing */
        $m = $this->add("stickynote/Model_StickyNote");
        $base = $this->api->url();
        $m->_dsql()->where($m->dsql()->expr("url = '[1]' or is_global = 'Y'")->setCustom("1", (string) $base));
        $ref = array();
        foreach ($m as $note){

            $v=$this->owner->add("View", null, null, array("view/stickynote"));

            $edit = $v->js()->univ()->frameURL("Edit Note", $vp->getURL($note["id"]), array("dialogClass" => "sticky-note-form", "width" => "400"))->_enclose();
            $del = $v->js()->univ()->dialogConfirm("Confirm", "Do you really want to delete?",
                $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"], "delete" => true)))->_enclose());
            $content = nl2br(htmlspecialchars($note["content"]));
            $v->template->trySetHTML("content", $content);
            $v->template->trySet("created_dts", $note["created_dts"]);
            if ($this->can_edit){
                $v->js(true)->on("dblclick", $edit);
            }
            $v->js(true)->dialog(
                array(
                    "resizable" => $this->can_resize,
                    "dialogClass" => "sticky-note " . $note["color"],
                    "closeOnEscape" => false,
                    "closeText" => "Delete?",
                    "position" => array((int)$note["x"], (int)$note["y"]),
                    "draggable" => $this->can_drag,
                    "dragStop" => $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"])), array("pos" => $v->js()->parent()->position()))->_enclose(),
                    "resizeStop" => $v->js()->univ()->ajaxec($this->api->url(null, array("note" => $note["id"])), array(
                        "width" => $v->js()->dialog("option", "width"), 
                        "height" => $v->js()->dialog("option", "height")
                    ))->_enclose(),
                    "width" => $note["width"]?:250,
                    "height" => $note["height"]?:150,
                    "beforeClose" => $v->js(null, array($this->can_del?$del:'', "return false;"))->_enclose()
                )
            );
            $ref[$note["id"]] = $v->js()->reload();
            $refd[$note["id"]] = $v->js()->parent()->detach();
            $v->js("click", array($v->js()->_selector(".sticky-note")->removeClass("top"), $v->js()->addClass("top")));
        }


        $vp->set(function($p) use ($vp,$owner, $self, $ref, $base){
            $m = $this->add("stickynote/Model_StickyNote");
            $id = $_GET[$vp->name];
            if ((int)$id){
                $m->load($id);
            }
            $f=$p->add("Form");
            $f->setModel($m, array("content", "is_global", "color"));
            $f->addSubmit();
            if ($f->isSubmitted()){
                if ((int)$id && !$self->can_edit){
                    $f->displayError("content", "Sorry, editing is not allowed");
                } else if (!(int)$id && !$self->can_add){
                    $f->displayError("content", "Sorry, adding is not allowed");
                }
                $f->update();
                $m=$f->getModel();
                if (!$m["url"]){
                    $m->set("url", (string)$base)->save();
                }
                if ((int)$id){
                    $p->js(null, $ref[$id])->univ()->closeDialog()->execute();
                }
                $owner->js(null, $p->js()->univ()->closeDialog())->univ()->location()->execute();
            }
        });

        if (isset($_GET["note"])){
            $m = $this->add("stickynote/Model_StickyNote");
            $m->load($_GET["note"]);
            if (isset($_GET["delete"]) && $this->can_del){
                $v=$refd[$m["id"]];
                $m->delete();
                $v->execute();
            }
            if (isset($_POST["pos"]) && $this->can_drag){
                $m->set("x", (int)$_POST["pos"]["left"])->set("y", (int)$_POST["pos"]["top"])->save();
            }
            if ($this->can_resize){
                if (isset($_POST["width"])){
                    $m->set("width", (int)$_POST["width"])->save();
                }
                if (isset($_POST["height"])){
                    $m->set("height", (int)$_POST["height"])->save();
                }
            }
            $owner->js()->execute();
        }
    }
}