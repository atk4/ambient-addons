<?php
namespace cps;

class Controller_Data_XML extends \Controller_Data {
    function setSource($model,$table=null){
        if(!$table)$table=$model->table;
        /* TODO */
        if(@!$this->api->xml[$table]){
            $source=$this->api->getConfig('xml/source/'.$table);
            $this->api->xml[$table] = $this->add("cps/Controller_XML")
                ->open($source);
        }
        /* TODO */
        parent::setSource($model,array(
            'conditions'=>array(),
            'db'=>$this->api->xml[$table],
            'root_iterator'=>$this->api->xml[$table]->getRootIterator(),
            'enclosure' => $model->enclosure
        ));
    }
    /** Implements access to our private storage inside model */
    public function _get($model,$key){
        return $model->_table[$this->short_name][$key];
    }
    public function _set($model,$key,$val){
        $model->_table[$this->short_name][$key]=$val;
    }

    function save($model,$id=null){
        $data=array();
        foreach($model->elements as $name=>$f)if($f instanceof \Field){
            if(!$f->editable() && !$f->system())continue;
            if(!isset($model->dirty[$name]) && $f->defaultValue()===null)continue;

            $value=$f->get();

            if($f->type()=='boolean' && is_bool($value)) {
                $value=(bool)$value;
            }
            if($f->type()=='int'){
                $value=(int)$value;
            }
            if($f->type()=='money' || $f->type()=='float'){
                $value=(float)$value;
            }

            $data[$name]=$value;

        }
        unset($data[$model->id_field]);
        $this->doSave($model, $data);
    }
    function doSave($model, $data){
        $db = $this->_get($model,'db');
        if($model->loaded()){
            $iterator = $this->_get($model,'iterator');
            // save
            if (!$data){
                return $model->id;
            }
            $db->update($iterator, $data);
            return $model->id;
        }
        $iterator = $this->getWriteIterator($model);
        $enclosure = $this->_get($model, "enclosure");
        $data[$model->id_field] = $db->getNextID($iterator, $path = $this->getChildPath($model));

        $iterator = $db->insert($iterator, $enclosure, $data);

        $model->id=(string)$data[$model->id_field]?:null;
        $model->data=$data;  // will grab defaults here
        $model->dirty=array();
        $this->_set($model, "iterator", $iterator);
        return $model->id;
    }
    function getChildPath($model){
        if ($model->sub){
            return $model->enclosure;
        } else {
            return "/" . $model->enclosure ."s/" . $model->enclosure;
        }
    }
    function load($model,$id){
        $this->tryLoadBy($model,$model->id_field,$id);
        if(!$model->loaded())throw $this->exception('Record not found')
            ->addMoreInfo('id',$id);
    }
    function tryLoadBy($model,$field,$cond=undefined,$value=undefined){

        $condition_freeze = $model->_table[$this->short_name]['conditions'];

        $this->addCondition($model,$field,$cond,$value);

        $this->tryLoadAny($model);

        $model->_table[$this->short_name]['conditions']=$condition_freeze;
        return $model->id;

    }
    function tryLoadAny($model){
        $db = $this->_get($model,'db');
        $root_iterator = $this->_get($model,'root_iterator');
        $enclosure = $this->_get($model,'enclosure');
        $iterator = $db->find(
                $root_iterator->{$enclosure}, $model->_table[$this->short_name]['conditions']
            );
        return $this->loadFromIterator($model, $iterator);
    } 
    function loadFromIterator($model, $iterator){
        if (!$iterator){
            $model->unload();
            return false;
        }
        $this->_set($model, "iterator", $iterator);
        foreach ($model->elements as $e){
            if ($e instanceof \Field){
                $model->data[$e->short_name] = (string)$iterator->{$e->short_name};
            }
        }
        $model->id=(string)$model->data[$model->id_field]?:null;
        return $model->id;
    }
    function addCondition($model,$field,$value){
        if($model->_table[$this->short_name]['conditions'][$field]){
            throw $this->exception('Multiple conditions on same field not supported yet');
        }
        if ($f=$model->hasElement($field)) {
            if($f->type()=='boolean' && is_bool($value)) {
                $value=(bool)$value;
            }
            if($f->type()=='int'){
                $value=(int)$value;
            }
            if($f->type()=='money' || $f->type()=='float'){
                $value=(float)$value;
            }

            $f->defaultValue($value)->system(true);
        }else{
            if($field[0]!='$'){
                throw $this->exception('Condition on undefined field. Does not '.
                    'look like expression either')
                    ->addMoreInfo('model',$model)
                    ->addMoreInfo('field',$field);
            }
        }
        $model->_table[$this->short_name]['conditions'][$field]=$value;
    }
    /* todo implement */
    function delete($model,$id){
        $db = $this->_get($model, "db");
        $read = $this->getReadIterator($model);
        $write = $this->getWriteIterator($model);
        $db->delete($read,$write,$model->id_field, $id);
    }
    function tryLoad($model,$id){
        throw $this->exception("TODO");
    }
    function loadBy($model,$field,$cond,$value){
        throw $this->exception("TODO");
    }
    function deleteAll($model){
        throw $this->exception("TODO");
    }
    function getRows($model){
        throw $this->exception("TODO");
    }
    function getBy($model,$field,$cond,$value){
        throw $this->exception("TODO");
    }
    function getByXpath($model, $xpath, $reversal){
        $db = $this->_get($model, "db");
        $iterator = $this->_get($model, "root_iterator");
        if ($iterator = $db->getByXpath($iterator, $xpath, $reversal)){
            if ($iterator->{$model->id_field}){
                $this->load($model, $iterator->{$model->id_field});
            }
            return true;
        } else {
            return false;
        }
    }
    function rewind($model){
        $db = $this->_get($model, "db");
        $db->setLimit($this->_get($model, "limit"));
        $this->traverse_ptr = $this->getReadIterator($model);
        $this->loadFromIterator($model, $db->rewind($this->traverse_ptr, $this->_get($model, "conditions")));
        return $this;
    }
    function next($model){
        $db = $this->_get($model, "db");
        $this->loadFromIterator($model, $db->next($this->traverse_ptr));
        return $this;
    }
    function count($model){
        return $this->_get($model, "db")
            ->count($this->_get($model, "root_iterator"), $this->_get($model, "conditions"));
    }
    function getReadIterator($model){
        $root_iterator = $this->_get($model, "root_iterator");
        $enclosure = $this->_get($model, "enclosure");
        if ($model->sub){
            return $root_iterator->{$enclosure};
        } else {
            return $root_iterator;
        }
    }
    function getWriteIterator($model){
        $root_iterator = $this->_get($model, "root_iterator");
        return $root_iterator;
    }

    function setLimit($model, $limit, $offset=null){
        $this->_set($model, "limit", [$limit, $offset]);
    }
    function getNode($model, $field){
        if (!$model->loaded()){
            throw $this->exception("Object must be loaded before calling getNode");
        }
        $db = $this->_get($model, "db");
        $i = $this->_get($model, "iterator");
        if (!$i){
            throw $this->exception("Iterator is not set - object is not properly referenced");
        }
        return $db->getNode($i, $field);
    }
}
