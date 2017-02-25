<?php
class eORM_db_sqlite extends eORM_db {
    private $pdoCon;
    private $path;

    //Implementations
    public function conStatus(){
        if ($this->pdoCon != null){
            return true; 
        } else {
            return false;
        }
    }
    
    public function destroy(){
        if ($this->pdoCon != null) {
            $this->pdoCon = null;
        }
        @unlink($this->path);
        if(file_exists($this->path)) {
            return false;
        } else {
            return true;
        }
    }

    public function connect(){
        if($this->conStatus()){
            return false;
        }
        if (isset($this->path)){
            try {
                $this->pdoCon = new \PDO('sqlite:'.$this->path);
                $this->pdoCon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(Exception $e) {
                throw new Exception('cannot connecto to database: '.$this->path);
                throw $e;
                return false;
            }
        }
        if(!$this->conStatus()) {
            throw new Exception('db connection failed');
        }
        return true;
    }

    public function tables(){
        $tables = array();
        foreach($this->query(array('sql'=>'SELECT name FROM sqlite_master WHERE type="table";')) as $table) {
            if ($table['name'] == 'sqlite_sequence') { continue; }
            $tables[$table['name']] = array();
            foreach($this->query(array('sql'=>'PRAGMA table_info('.$table['name'].');')) as $tableinfo){
                $tables[$table['name']][$tableinfo['name']] = $tableinfo['name'];
            }
        }
        return $tables;
    }
    
    public function query($param){
        if(!$this->conStatus()){
            throw new Excpetion('db connection not established');
        } elseif (!is_array($param)) {
            throw new Exception('wrong parameter supplied: array needed in query()');
        } elseif (!isset($param['sql'])) {
            throw new Exception('no sql provided in query parameter');
        } else {
            $sql = $param['sql'];
            unset($param['sql']);
        }
        $statement = $this->pdoCon->prepare($sql);
        if(count($param) > 0) {
            foreach($param as $key=>$value){
                if(is_numeric($value)){
                    $statement->bindValue($key,$value,PDO::PARAM_INT);
                } else {
                    $statement->bindValue($key,$value,PDO::PARAM_STR);
                }
            }
        }
        try {
            $statement->execute();
            return $statement->fetchAll();
        } catch (Exception $e) {
            throw new Expeption('cannot execute query: '.$sql);
            throw $e;
        }

    }

    public function execute($param){
        if(!$this->conStatus()){
            throw new Excpetion('db connection not established');
        } elseif (!is_array($param)) {
            throw new Exception('wrong parameter supplied: array needed in execute()');
        } elseif (!isset($param['sql'])) {
            throw new Exception('no sql provided in execute parameter');
        } else {
            $sql = $param['sql'];
            unset($param['sql']);
        }
        $statement = $this->pdoCon->prepare($sql);
        if(count($param) > 0) {
            foreach($param as $key=>$value){
                if(is_numeric($value)){
                    $statement->bindValue($key,$value,PDO::PARAM_INT);
                } else {
                    $statement->bindValue($key,$value,PDO::PARAM_STR);
                }
            }
        }
        try {
            $result = $statement->execute();
        } catch (Exception $e) { throw new Exception('cannot execute statement');throw $e; }
        if ($result > 0) {
            if (strpos($sql,'INSERT') !== false ) { 
                return intval($this->pdoCon->lastInsertId());
            } else { return true; }
        } else {
            return false;
        }
    }

    public function script($script){
        if(!$this->conStatus()){
            throw new Excpetion('db connection not established');
        } elseif (!is_string($script)) {
            throw new Exception('wrong parameter supplied: string needed in script()');
        }
        $commands = explode(';',$script);
        foreach($commands as $command){
            if($command == ''){continue;}
            try {
                $this->pdoCon->exec($command);
            } catch (Exception $e) {
                throw new Exception('cannot execute command: '.$command);
                throw($e);
                return false;
            }
        }
    }


    public function __construct($config) {
        parent::__construct($config);
        if(isset($config['path'])){
            $this->path = $config['path'];
        }
    }

}

?>