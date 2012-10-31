<?php
namespace cms;
class Controller_GridOrder extends \AbstractController {
    public $model;

    function init(){
        parent::init();

        $this->owner->addButton('Re-order records')
            ->js('click')->univ()->frameURL('Re-order records',
                    $this->api->url(null,
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


        $lister=$v->add('CompleteLister',null,null,array('view/gridorder'));
        $this->model=$m=$this->owner->getModel();

        if(!$m->hasField('ord')){
            $m->addField('ord')->system(true);
        }

        $lister->setModel($m);
        $lister->dq->order('ord');

        $lister->js(true)->sortable();

        $v->add('Button')->set('Save')->js('click')->univ()->ajaxec(
                array($this->api->URL(),
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
        $q->set('ord', $q->expr('id', 'id'));
        $q->where('ord is null or ord = 0');
        $q->do_update();

        $q=$this->model->dsql()->field('id')->field('ord');
        $seq=$q->do_getAllHash();

        // extract ORDs
        $ord=array();
        foreach($seq as $key=>$val){
            $ord[]=$val['ord'];
        }
        sort($ord);
        $list = array();
        foreach(explode(',',$id_order) as $id){
            $q=$this->model->dsql();
            $q->set('ord',array_shift($ord));
            $q->where('id',$id);
            $q->do_update();
            $list[] = $id;
        }
        foreach ($list as $id){
            $this->model->load($id)->hook("post-order");
        }
    }
}
