<?php
abstract class eORM_db {
    abstract function destroy();
    abstract function execute($param);
    abstract function script($script);
    abstract function query($param);
    abstract function connect();
    abstract function tables();
    abstract function conStatus();


    public function __construct($config){
        if(! isset($config['driver'])){
            throw new Exception('specify driver in config');
        }
    }

}
?>