<?php
abstract class eORM_table {
    //public static $tablename in inheriting classes needed 

    public static function selectSQL(Array $paramArray,$offset,$limit) {
        $sqlSelect = "SELECT * FROM ".static::$tablename;
        $returnArr = array();
        if (count($paramArray) > 0) {
            $sqlSelect .= " WHERE ";
            $i = 0;
            foreach ($paramArray as $key=>$value) {
                if ($i > 0 && $i != count($paramArray) ) { $sqlSelect .= " AND "; }
                if(is_numeric($value)) {
                    $sqlSelect .= "$key"."=".$value;
                } elseif(is_array($value)) {
                    if(array_key_exists('contains',$value)) {
                        $sqlSelect .= "$key LIKE '%".$value['contains']."%'";
                    } elseif(array_key_exists('start',$value)) {
                        $selectSQL .= "$key LIKE '".$value['start']."%'";
                    } elseif(array_key_exists('end',$value)) {
                        $sqlSelect .= "$key LIKE '%".$value['end']."'";
                    }
                } 
                else {
                    $sqlSelect .= "$key"."='".$value."'";
                }
                $i++;
            }
        }
        return $sqlSelect." LIMIT $limit OFFSET $offset";
    }

    public function updateSQL() {
        $updateSQL = "UPDATE ".static::$tablename." SET ";
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != "ID" && $key != "tablename") {
                if ($i > 0 && $i != count($this) ) { $updateSQL .= ","; }
                if (is_numeric($value)) {
                    $updateSQL .= "$key=$value ";
                } else {
                    $updateSQL .= "$key='$value' ";
                }
            } 
            $i++;
        }
        return $updateSQL.= "WHERE ID=$this->ID;";
    }

    public function insertSQL() {
        $sqlInsert = "INSERT INTO ".static::$tablename."(ID,";
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != "tablename" && $key != "ID") {
                if ($i > 0 && $i != count($this) ) { $sqlInsert .= ","; }
                $sqlInsert .= "$key";
            } 
            $i++;
        } 
        $sqlInsert .= ") VALUES (NULL,";
        $i = 0;
        foreach($this as $key=>$value) {
            if($key != "tablename" && $key != "ID") {
                if ($i > 0 && $i != count($this) ) { $sqlInsert .= ","; }
                if (is_numeric($value)) {
                    $sqlInsert .= "$value";
                } else {
                    $sqlInsert .= "'$value'";
                }
            } 
            $i++;
        }
        return $sqlInsert.= ")";
    }

    public function deleteSQL() {
        return "DELETE FROM ".static::$tablename." WHERE ID=$this->ID";
    }

    public function selfquerySQL(){
        return 'SELECT * FROM '.static::$tablename." WHERE ID=$this->ID;";
    }

    public function fill($paramArray) {
        
        foreach($paramArray as $key=>$value) {
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