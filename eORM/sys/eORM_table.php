<?php
abstract class eORM_table  {
    //public static $tablename in inheriting classes needed 

    public static function query(Array $param,$offset = 0,$limit = 100) {
        $selectArr = array();
        if (isset($param['sql'])) {
            $param['sql'] .= " OFFSET $offset LIMIT $limit";
            return $param;
        }
        $sqlSelect = 'SELECT * FROM '.static::$tablename;
        if (is_array($param) && count($param) > 0) {
            $sqlSelect .= ' WHERE ';
            $i = 0;
            $ORindexes = array();
            foreach($param as $value){
                if($value == 'OR'){
                        array_push($ORindexes,$i);
                }
                $i++;
            }
            $i = 0;
            foreach ($param as $key=>$value) {
                if ($i > 0 && $i != count($param)) {
                    foreach($ORindexes as $ORindex) {
                        if ($ORindex == $i) {
                            $sqlSelect .= ' OR ';
                            continue;
                        }
                    }
                    if (substr($sqlSelect,strlen($sqlSelect)-4,4) != ' OR ') { $sqlSelect .= ' AND '; }
                }
                if(is_array($value)) {
                    if (array_key_exists('col',$value)) {
                        $col = $value['col'];
                    } else { $col = $key; }
                    if(array_key_exists('contains',$value)) {
                        $sqlSelect .= "$col LIKE :$key".$i;
                        $selectArr[":$key".$i] = '%'.$value['contains'].'%';
                    } elseif(array_key_exists('start',$value)) {
                        $sqlSelect .= "$col LIKE :$key".$i;
                        $selectArr[":$key".$i] = $value['start'].'%';
                    } elseif(array_key_exists('end',$value)) {
                        $sqlSelect .= "$col LIKE :$key".$i;
                        $selectArr[":$key".$i] = '%'.$value['end'];
                    } elseif(array_key_exists('is',$value)) {
                        $sqlSelect .= "$col=:$key".$i;
                        $selectArr[":$key".$i] = $value['is'];
                    }
                } elseif($value == 'OR') {} 
                else {
                    $sqlSelect .= "$key=:$key".$i;
                    $selectArr[":$key".$i] = $value;
                }
                $i++;
            }

        }
        $selectArr['sql'] = $sqlSelect." LIMIT $limit OFFSET $offset";
        
        global $eORM;
        $queryResult = $eORM->database->query($selectArr);
        if (count($queryResult) == 1){
            return $class::__set_state($queryResult);
        }
        $objs = array();
        foreach($queryResult as $objresult) {
            array_push($objs,static::__set_state($objresult));
        }
        return $objs;
    }   

    public function update() {
        $updateArr = array();
        $updateSQL = "UPDATE ".static::$tablename." SET ";
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != 'ID' && $key != 'tablename') {
                if ($i > 0 && $i != count($this)) { $updateSQL .= ','; }
                $updateSQL .= "$key=:$key ";
                $update[":$key"] = $value; 
            } 
            $i++;
        }
        $update['sql'] =  $updateSQL.'WHERE ID=:ID';
        $update[':ID'] = $this->ID;
        global $eORM;
        return $eORM->database->execute($updateArr);
    }

    public function insert() {
        $sqlInsert = 'INSERT INTO '.static::$tablename.'(ID,';
        $insertArr = array();
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != 'tablename' && $key != 'ID') {
                if ($i > 0 && $i != count($this) ) { $sqlInsert .= ','; }
                $sqlInsert .= "$key";
            } 
            $i++;
        } 
        $sqlInsert .= ') VALUES (NULL,';
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != 'tablename' && $key != 'ID') {
                if ($i > 0 && $i != count($this) ) { $sqlInsert .= ","; }
                $sqlInsert .= ":$key";
                $insertArr[":$key"] = $value;
            } 
            $i++;
        }
        $insertArr['sql'] = $sqlInsert.')';
        global $eORM;
        $this->ID = $eORM->database->execute($insertArr);
    }

    public function delete() {
        global $eORM;
        return $eORM->database->execute(array('sql'=>'DELETE FROM '.static::$tablename.' WHERE ID=:ID',':ID'=>$this->ID));
        $this->__destruct();
    }

    public function save(){
        $serverObj = $eORM->database->execute(array('sql'=>'SELECT * FROM '.static::$tablename.' WHERE ID=:ID',':ID'=>$this->ID)); 
        if (is_array($serverObj)){
            if (!$this == $serverObj) {
                return $this->update();
            } else {
                return true;
            }
        } else {
            return $this->insert();
        }
    }

    public function check(){
        global $eORM;
        $serverObj = static::set_state(
            $eORM->database->query(
               array('sql'=>'SELECT * FROM '.static::$tablename.' WHERE ID=:ID',':ID'=>$this->ID) 
            )[0]
        ); 
        if($svobj == $this){
            return true;
        } else {
            return false;
        }
    }

    public function __destruct(){}

    public static function __set_state($state) {
        $newObj = new static();
        if(is_array($state)) {
            foreach($state as $key=>$value){
                if(property_exists($newObj,$key)){
                    $newObj->$key = $value;
                }
            }
        } else { trigger_error('wrong parameter supplied in magic __set_state'); }
        return $newObj;
    }
}
?>