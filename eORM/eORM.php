<?php

class eORM {
    private $pdo;
    public $config; //Configuration loaded in the Constructor


    //SQL Operations
    public function SQLexecute($param) {
        if ($this->ConnectionStatus()) {
            if (is_array($param)){
                $sql = $param['sql'];
                unset($param['sql']);
            } elseif (is_string($param)) {
                $sql = $param;
            } else { trigger_error('wrong parameter supplied in SQLexecute');exit; }
        }
        $statement = $this->pdo->prepare($sql);
        if(is_array($param) && count($param) > 0) {
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
        } catch (Exception $e) { throw $e; }
        if ($result > 0) {
            if (strpos($sql,'INSERT') !== false ) { 
                return intval($this->pdo->lastInsertId());
            } else { return true; }
        } else {
            return false;
        }
    }
    
    public function SQLquery($param) {
        if ($this->ConnectionStatus()) {
            if (is_array($param)){
                $sql = $param['sql'];
                unset($param['sql']);
            } elseif (is_string($param)) {
                $sql = $param;
            } else { trigger_error('wrong parameter supplied in SQLexecute');exit; }
            $statement = $this->pdo->prepare($sql);
            if(is_array($param) && count($param) > 0) {
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
                throw $e;
            }
        }
    }

    //Object SQL Operations
    public function tableObj_check($testObj) {
        if(get_parent_class($testObj) == 'eORM_table') {
            return true;
        } else {
            trigger_error('eORM CRUD functions only available on eORM objects'); 
            exit;
        }
    }

    public function insert(&$insertObj){
        $this->tableObj_check($insertObj);
        $insertObj->ID = $this->SQLexecute($insertObj->insertSQL());
    }

    public function delete(&$deleteObj) {
        $this->tableObj_check($deleteObj);
        if($this->SQLexecute($deleteObj->deleteSQL())) {
            $deleteObj = null;
            return true;
        } else { return false; }
    }

    public function update($updateObj) {
        $this->tableObj_check($updateObj);
        return $this->SQLexecute($updateObj->updateSQL());
    }

    public function query($classObj, $parameters,$offset = 0,$limit = 100){
        $this->tableObj_check($classObj);
        $class = get_class($classObj);
        try {
            $queryResult = $this->SQLquery($class::selectSQL($parameters,$offset,$limit));
        } catch (Exception $e) { throw $e; }

        if (count($queryResult) == 1){
            $returnObj = new $class();
            $returnObj->fill($queryResult[0]);
            return $returnObj;
        }
        $resultArr = array();
        foreach($queryResult as $returnObj) {
            $obj = new $class();

            $obj->fill($returnObj);
            array_push($resultArr,$obj);
        }
        return $resultArr;
    }

    public function cons_check($obj){
        if (! $this->tableObj_check($obj)) { trigger_error('call eORM only available on eORM objects'); exit; }
        $class = get_class($obj);
        $svobj = new $class(); 
        $svobj->fill(
            $this->SQLquery(
                $obj->selfquerySQL()
            )[0]
        );
        if($svobj == $obj) {
            return true;
        } else {
            return false;
        }

    }


    //class functions
    public function destroy() {
        @unlink($this->config['db']);
        if(file_exists($this->config['db'])) {
            return false;
        } else {
            return true;
        }
    }

    public function ConnectionStatus() {
        if ($this->config != null) {
            if ($this->pdo != null){
                return true; 
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function PDOconnect() {
        if (!$this->ConnectionStatus()){
            if(array_key_exists('db', $this->config)) {
                try {
                    $this->pdo = new \PDO('sqlite:'.$this->config['db']);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch(\PDOException $e) {
                    throw($e);
                    return false;
                }
            }
        }    
        return $this->ConnectionStatus();
    }

    public function connect(){
        if(is_dir($this->config['models'])) {
        if (!file_exists($this->config['models']."/map.ini")) { trigger_error('configure database before using eORM');exit; }
            try {
                @$map = parse_ini_file($this->config['models']."/map.ini");
            } catch(Exception $e) { trigger_error('configure database before using eORM'); exit; }
        } else { trigger_error('configure config.ini before using eORM'); exit; }
        
        require('eORM_table.php');
        foreach ($map['classfiles'] as $classFile) {
            require($this->config['models']."/$classFile");
        }
        return $this->PDOconnect();
    }

    public function createObjects(){
        $htmlResponse = '<h3>Dynamical Class Generation</h3>';
        @unlink($this->config['models'].'/map.ini');
        if(!file_exists($this->config['models'].'/map.ini')) { 
            $htmlResponse .= 'old map deleted';
        } else {
            $htmlResponse .= 'cannot delete old map';
        } $htmlResponse = '<br>';
        foreach($this->SQLquery('SELECT name FROM sqlite_master WHERE type="table";') as $table) {
            if ($table['name'] == 'sqlite_sequence') { continue; }
            $htmlResponse .= 'class: '.$table['name'].'<br>';
            
            $classFile = '<?php class '.$table['name'].' extends eORM_table {';
            foreach($this->SQLquery('PRAGMA table_info('.$table['name'].');') as $tableinfo){
                $classFile .= 'public $'.$tableinfo['name'].';';
            }
            $classFile .= 'public static $tablename = \''.$table['name'].'\'; } ?>';

            file_put_contents($this->config['models'].'/'.$table['name'].'.php',$classFile);
            file_put_contents($this->config['models'].'/map.ini','classfiles[]="'.$table['name'].".php\"\n",FILE_APPEND);
        }
        return $htmlResponse;
    }

    //these Functions will output HTML
    public function admin_check($recreateDB=false) {
        if(!isset($_POST['eORM_adminpassword']) || $_POST['eORM_adminpassword'] != $this->config['admin_password']) {
            echo('
            <p>please enter correct password</p>
            <form action="?" method="post">
            <input name="eORM_adminpassword" type="password"></input><br>');
            if ($recreateDB) {
            echo('Recreate Database<input type="checkbox" name="eORM_newDatabase" value="recreate Database"><br>');
            }
            echo('<input type="submit" value="GO"/>
            </form>
            <p>Warning: database will be deleted before recreation</p> 
                    
            </body></html>
            ');
            exit();
        }
    }

    public function DBinstallation($sqlscript = '') {
        if($sqlscript != '') {
            $this->destroy();
            $this->PDOconnect();
            $this->SQLexecute($sqlscript);
            $this->createObjects();
        } else {
            $this->admin_check(true);
            if(isset($_POST['eORM_newDatabase'])) {
                $newDatabase = boolval($_POST['eORM_newDatabase']);
            } else { $newDatabase = false; }
            if($newDatabase) {
                echo('<h3>destroy Database</h3>');
                if ($this->destroy()) {
                    echo('database deleted');
                } else {
                    echo('cannot delete database');
                    echo('<br>this might cause some errors during the sql execution');
                }
            }
            echo('<h3>Database Connection</h3>');
            if ($this->PDOconnect()) {
                echo('connection successfully established');
            } else {
                echo('error: cannot connect to database');
            }
            if($newDatabase) {
                echo('<h3>Database Script</h3>');
                try {
                    $sqlscript = file_get_contents($this->config['dbscript']);
                } catch(Exception $e) {
                    trigger_error('Cannot read database script');
                    throw $e;
                    exit;
                }
                echo(str_replace("\n",'<br>',$sqlscript));
                echo('<h3>Script execution</h3>');
                try {
                    if($this->SQLexecute($sqlscript)) {
                        echo ("script executed successfully");
                    } else {
                        echo ("script could not be executed");
                    }
                } catch (Exception $e) {
                    echo ("error in script: $e");
                    exit();
                }
            }
            echo('<h3>Database Tables</h3>');
            foreach($this->SQLquery('SELECT name FROM sqlite_master WHERE type="table";') as $table) {
                echo($table['name']."<br>");
            }
            echo($this->createObjects());
        }    
    }

    public function DBdump(){
        $this->admin_check();
        if (array_key_exists('sqlite_path',$this->config)) {
            $command = "\"".$this->config['sqlite_path']."\"";
        } else { $command = 'sqlite3'; }
        $command .= ' '.$this->config['db'].' .dump';
        $result = str_replace("\n",'<br>',shell_exec($command));
        if ($result == '') {
            echo("error in sqlite3 configuration. Check config.ini and sqlite installation");
        } else { echo($result); }
    }

    //constructor
    public function __construct() {
        try {
            $this->config = parse_ini_file('config.ini');
        } catch(Exception $e) { 
            trigger_error("configure config.ini before using eORM");
            throw $e; 
            exit;
        }
     }

    public static function __set_state($state) {
        $newObj = new static();
        if(is_array($state)) {
            foreach($state as $key=>$value){
                $newObj->$key = $value;
            }
        } else { trigger_error('wrong parameter supplied in magic __set_state'); }
        return $newObj;
    }
}

?>