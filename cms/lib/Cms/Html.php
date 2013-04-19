<?php
namespace cms;
class Cms_Html extends Cms {
    protected $elem_template = null;
    function init(){
        parent::init();
    }
    function configureFields(){
        $this->m->addField('content')->type('text')->allowHtml(true);
        $this->m->add('filestore/Field_Image', 'images');
    }
    function showConfigureForm($target){
        //$this->js(true)->jquery->addStylesheet('elrte/css/elrte.full','.css',true);

        $f=parent::showConfigureForm($target);
        $f->template->appendHTML('Content',
                '<link type="text/css" href="'.$this->api->locateURL('css','elrte/css/elrte.full.css').'" rel="stylesheet" />'."\n");
        $f->setFormClass("empty");
        $f->getElement("images")->allowMultiple(10)
            ->setFormatFilesTemplate("view/cms_files");

        $f->getElement('content')->js(true)->_selectorRegion()->show();
        $f->getElement('content')
            ->setCaption('')
            ->js(true)
            ->_load('elrte/js/jquery.1.9.compat')
            ->_load('elrte/js/elrte.min')
            ->addClass("elrte_editor")
            ->elrte(array('width'=>900,
                'height'=>300,
                'cssfiles'=>array($this->api->locateURL('css', 'editor.css')),
                'toolbar'=> 'maxi')
            );
        $this->submit_btn->js(true)->unbind("click")->bind("click",
            $this->submit_btn->js(null, array(
                $this->submit_btn->js()->_selector(".elrte_editor")->elrte('updateSource'),
                $this->f->js()->submit()
            ))->_enclose());
        return $f;
    }
    function configure($dest, $tag){
        $o = $dest->add('View',null,$tag,$this->elem_template);
        $o->template->setHTML("Content", $this->m->get('content'));
        return $o;
    }
}
