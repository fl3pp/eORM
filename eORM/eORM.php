<?php

//use for magic functions


class eORM {
    public $database;
    public $config; //Configuration loaded in the Constructor



    //Object Operations
    public function eORM_table_objcheck($testObj) {
        if(get_parent_class($testObj) == 'eORM_table') {
            return true;
        } else {
            throw new Exception('Use eORM functions available to eORM objects only');
        }
    }

    public function insert(&$insertObj){
        $this->eORM_table_objcheck($insertObj);
        $insertObj->ID = $this->database->execute($insertObj->insertSQL());
    }

    public function delete(&$deleteObj) {
        $this->eORM_table_objcheck($deleteObj);
        $suc = $this->database->execute($deleteObj->deleteSQL());
        $deleteObj = null;
        return $suc;
    }

    public function update($updateObj) {
        $this->eORM_table_objcheck($updateObj);
        return $this->database->execute($updateObj->updateSQL());
    }

    public function query($classObj, $parameters,$offset = 0,$limit = 100){
        $this->eORM_table_objcheck($classObj);
        $class = get_class($classObj);
        
        $queryResult = $this->database->query($class::selectSQL($parameters,$offset,$limit));

        if (count($queryResult) == 1){
            return $class::__set_state($queryResult);
        }
        $resultArr = array();
        foreach($queryResult as $objresult) {
            array_push($resultArr,$class::__set_state($objresult));
        }
        return $resultArr;
    }

    public function check($obj){
        if (! $this->eORM_table_objcheck($obj));
        $class = get_class($obj);
        $svobj = $class::__set_state(
            $this->database->query(
                $obj->selfquerySQL()
            )[0]
        ); 
        if($svobj == $obj) {
            return true;
        } else {
            return false;
        }
    }

    //internal operations

    public function loadMap(){
        if (!file_exists($this->models."/map.ini")) { 
            throw new Exception('install DB before using eORM'); 
        }
        try {
            @$map = parse_ini_file($this->models."/map.ini");
        } catch(Exception $e) { throw new Exception('install DB before using eORM'); }
        foreach ($map['classfiles'] as $classFile) {
            try {
                require($this->models."/$classFile");
            } catch(Excpetion $e){
                throw new Exception('Error in generated class '.$classFile); 
            }
        }
    }

    public function map(){
        @unlink($this->models.'/map.ini');
        if(file_exists($this->models.'/map.ini')) { 
            throw new Exception('cannot delete map.ini');        
        }
        foreach($this->database->tables() as $table=>$tablecontent) {        
            $classFile = '<?php class '.$table.' extends eORM_table {';
            foreach($tablecontent as $tableinfo){
                $classFile .= 'public $'.$tableinfo.';';
            }
            $classFile .= 'public static $tablename = \''.$table.'\'; } ?>';
            try {
                file_put_contents($this->models.'/'.$table.'.php',$classFile);
                file_put_contents($this->models.'/map.ini','classfiles[]="'.$table.".php\"\n",FILE_APPEND);
            } catch (Exception $e) {
                throw new Excpetion('cannot load table: '.$table);
                throw $e;
                return false;
            }
        }
        return true;
    }

    public function install($newDatabase = false,$sqlscript = '') { 
        if($newDatabase) {
            if (!$this->database->destroy()) {
                throw new Exception('cannot destroy database');
            } 
        }
        if (! $this->database->connect()) {
            throw new Exception('cannot connect to new database');
        } 
        
        if($newDatabase) {
            if ($sqlscript==''){
                try {
                    $sqlscript = file_get_contents($this->config['model']['script']);
                } catch (Exception $e) {
                    throw new Exception('cannot load database script: '.$this->config['model']['script']);
                }
            }
            $this->database->script($sqlscript);
        }
        $this->map();
    }

    public function start(){
        $this->loadMap();
        if(!$this->database->conStatus()){
            $this->database->connect();
        }
    }

    public function status(){
        return $this->database->conStatus();
    }


    //constructor
    public function __construct($configpath = '') {
        require('sys/eORM_db.php');
        require('sys/eORM_table.php');
        if ($configpath == '') {
            $configpath = 'config.ini';
        }
        try {
            $this->config = parse_ini_file($configpath,true);
        } catch(Exception $e) { 
            throw new Exception("configure config.ini before using eORM");
            throw $e; 
            exit;
        }
        if (array_key_exists('db',$this->config)){
            require('drivers/'.$this->config['db']['driver'].'.php');
            $db_class = (string)$this->config['db']['driver'];
            $this->database = new $db_class($this->config['db']);   
        }
        if(! isset($this->config['model']['folder'])){
            throw new Exception('specify models folder in config');
        } 
        $this->models = $this->config['model']['folder'];
        if(!is_dir($this->models)){
            throw new Exception('models folder not existing: '.$this->models);        
        } 
        
     }


}

?>