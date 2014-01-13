<?php
namespace cps;

class Controller_XML extends \AbstractController {
    public $limit = null;
    public $offset = null;
    public $debug = false;
    function open($source){
        $this->source = $source;
        if (file_exists($this->source)){
            $this->xml = simplexml_load_file($this->source, "SimpleXMLIterator");
        } else {
            $this->xml = simplexml_load_string('<?'.'xml version="1.0" encoding="UTF-8"'.'?><documents></documents>', 'SimpleXMLIterator');
        }
        return $this;
    }
    function insert($iterator, $enclosure, $data){
        $child_iterator = $this->addNew($iterator, $enclosure, $data);
        $this->save();
        return $child_iterator;
    }
    function update($iterator, $data){
        $children = $iterator->getChildren();
        array_walk($data, function($v,$k) use ($iterator){
            if (isset($iterator->$k)){
                $iterator->$k = $v;
            } else {
                $iterator->addChild($k,$v);
            }
        });
        $this->save();
    }
    function find($iterator, $conditions){
        $iterator->rewind();
        foreach ($iterator as $elem){
            if ($this->match($elem, $conditions)){
                return $elem;
            }
        }
    }
    function match($xml, $cond){
        if (!empty($cond)){
            foreach ($cond as $k => $v){
                $child = (string)$xml->$k;
                if ((string)$v != $child){
                    return false;
                }
            }
        }
        return true;
    }
    function save(){
        $fid = fopen($this->source, "w");
        fputs($fid, $this->xml->asXML());
        fclose($fid);
    }
    function addNew($iterator, $enclosure, $data){
        $xml = $iterator->addChild($enclosure);
        array_walk($data, function($v,$k) use ($xml){
            $xml->addChild($k,$v);
        });
        return $xml;
    }
    function getNextId($iterator, $path){
        $obj = $iterator->xpath($p = $path . "[last()]");
        if ($obj && isset($obj[0]->id)){
            return $obj[0]->id + 1;
        } else {
            return 1;
        }
    }
    function getByXpath($iterator, $xpath, $reversal){
        $obj = $iterator->xpath($xpath);
        if ($obj){
            $obj = $obj[0]->xpath($reversal);
        }
        return $obj[0];
    }
    function rewind($iterator, $conditions=[]){
        $this->conditions = $conditions;
        if ($this->debug){
            var_dump($conditions);
        }
        $iterator->rewind();
        $this->counter = 0;
        if ($this->offset > 0){
            while ($this->counter < $this->offset){
                if (!($c=$iterator->current())){
                    return false;
                }
                if ($this->match($c, $conditions)){
                    $this->counter++;
                }
                $iterator->next();
            }
        } else {
            while (true){
                if (!($c = $iterator->current())){
                    return false;
                }
                if ($this->match($c, $conditions)){
                    break;
                }
                $iterator->next();
            }
        }
        return $c;
    }
    function next($iterator){
        $this->counter++;
        if ($this->limit && $this->counter > $this->limit - 1){
            return null;
        }
        while (true){
            $c = $iterator->next();
            $c = $iterator->current();
            if (!$c){
                return null;
            }
            if ($this->match($c, $this->conditions)){
                return $c;
            }
        }
    }
    function count($iterator, $conditions){
        /** todo user proper search **/
        $iterator->rewind();
        $counter = 0;
        foreach ($iterator as $elem){
            if ($this->match($elem, $conditions)){
                $counter++;
            }
        }
        return $counter;
    }
    function delete($read,$write, $id_field, $value){
        $read->rewind();
        $conditions = [$id_field =>$value];
        $counter = 0;
        foreach ($read as $k => $elem){
            if ($this->match($elem, $conditions)){
                if (count($write->{$k}) > 1){
                    unset($write->{$k}[$counter]);
                } else {
                    unset($write->{$k});
                }
                $this->save();
                return true;
            }
            $counter++;
        }
        throw $this->exception("Could not delete record");
    }
    function setLimit($a){
        list($limit, $offset) = $a;
        $this->limit = $limit;
        $this->offset =(int)$offset;
    }
    function getRootIterator(){
        return $this->xml;
    }
    function getNode($iterator, $field){
        if (isset($iterator->{$field})){
            return $iterator->{$field};
        } else {
            return $iterator->addChild($field);
        }
    }
}
