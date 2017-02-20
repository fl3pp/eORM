<?php
abstract class eORM_table {
    //public static $tablename in inheriting classes needed 

    public static function selectSQL(Array $param,$offset,$limit) {
        $returnArr = array();
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
            $returnArr = array();
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
                        $returnArr[":$key".$i] = '%'.$value['contains'].'%';
                    } elseif(array_key_exists('start',$value)) {
                        $sqlSelect .= "$col LIKE :$key".$i;
                        $returnArr[":$key".$i] = $value['start'].'%';
                    } elseif(array_key_exists('end',$value)) {
                        $sqlSelect .= "$col LIKE :$key".$i;
                        $returnArr[":$key".$i] = '%'.$value['end'];
                    }
                } elseif($value == 'OR') {} 
                else {
                    $sqlSelect .= "$key=:$key".$i;
                    $returnArr[":$key".$i] = $value;
                }
                $i++;
            }

        }
        $returnArr['sql'] = $sqlSelect." LIMIT $limit OFFSET $offset";
        return $returnArr;
    }   

    public function updateSQL() {
        $returnArr = array();
        $updateSQL = "UPDATE ".static::$tablename." SET ";
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != 'ID' && $key != 'tablename') {
                if ($i > 0 && $i != count($this)) { $updateSQL .= ','; }
                $updateSQL .= "$key=:$key ";
                $returnArr[":$key"] = $value; 
            } 
            $i++;
        }
        $returnArr['sql'] =  $updateSQL.'WHERE ID=:ID';
        $returnArr[':ID'] = $this->ID;
        return $returnArr;
    }

    public function insertSQL() {
        $sqlInsert = 'INSERT INTO '.static::$tablename.'(ID,';
        $statement = array();
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
                $statement[":$key"] = $value;
            } 
            $i++;
        }
        $statement['sql'] = $sqlInsert.')';
        return $statement;
    }

    public function deleteSQL() {
        return array('sql'=>'DELETE FROM '.static::$tablename.' WHERE ID=:ID',':ID'=>$this->ID);
    }

    public function selfquerySQL(){
        return array('sql'=>'SELECT * FROM '.static::$tablename.' WHERE ID=:ID',':ID'=>$this->ID);
    }

    public function fill($param) {
        foreach($param as $key=>$value) {
            if (property_exists($this,$key)){
                if(is_numeric($value)){
                    $this->$key = intval($value);
                } else {
                    $this->$key = $value;   
                }
            }
        }
        return $this;
    }


}
?>