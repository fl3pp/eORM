<?php

class eORM {
    public $database;
    public $config; //Configuration loaded in the Constructor



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
        if(file_exists($this->models.'/map.ini')){
            return $this->database->conStatus();
        } else {
            return false;
        }
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

$eORM = new eORM();
if(!$eORM->status()){
    $eORM->start();
}

?>