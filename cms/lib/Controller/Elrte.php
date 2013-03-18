<?php
namespace cms;

/* Add this to text field to enable WYSIWYG editing / html */
class Controller_Elrte extends \AbstractController {
	function init(){
		parent::init();
		$f=$this->owner->form;		// form

        $f->template->appendHTML('Content',
                '<link type="text/css" href="'.$this->api->locateURL('css','elrte/css/elrte.full.css').'" rel="stylesheet" />'."\n");
        $f->setFormClass("empty");
        /*
        $f->getElement("images")->allowMultiple(10)
            ->setFormatFilesTemplate("view/cms_files");
            */

        // If we are in a pop-up, show ourselves quicker!
        $this->owner->js(true,'if(typeof region != "undefined")$(region).show();');

        $this->owner
            ->setCaption('')
            ->js(true)
            ->_load('elrte/js/jquery.1.9.compat')
            ->_load('elrte/js/elrte.min')
            ->addClass("elrte_editor")
            ->elrte(array('width'=>900,
                'height'=>300,
                //'cssfiles'=>array($this->api->locateURL('css','elrte/css/editor.css')),
                'toolbar'=> 'maxi')
            );
        $this->submit_btn=$f->addSubmit('Save');
        $this->submit_btn->js(true)->unbind("click")->bind("click", $this->submit_btn->js(null, '$(".elrte_editor").elrte()[0].save();return false')->_enclose());
    }
}
