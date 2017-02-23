<?php
use PHPUnit\Framework\TestCase;

class basic extends TestCase
{
    public function test_initialize_eORM() {
        require('../eORM/eORM.php');
        $eORM = new eORM();

        $this->assertInstanceOf('eORM',$eORM);
        return $eORM;
    }

    /**
    * @depends test_initialize_eORM
    */
    public function test_dbInstallation($eORM)
    {
        $eORM->DBinstallation('
        CREATE TABLE project(
            ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR(30)
        );
        ');
        $eORM->connect();
        $this->assertTrue($eORM->ConnectionStatus());
        $this->assertFileExists($eORM->config['db']);
        $this->assertTrue(class_exists('project'));

        return $eORM;
    }

    /**
    * @depends test_dbInstallation
    */
    public function test_insertations($eORM){
        $projects = array(); 
        for ($i = 0; $i < 5; $i++) {
            $projects[$i] = new project;
            $projects[$i]->name = "name$i";
            $eORM->insert($projects[$i]);
            $this->assertInternalType('int',$projects[$i]->ID);
        }
        $oldPro = new project();
        $oldPro->name = "oldname";
        $eORM->insert($oldPro);
        return $projects;
    }

    /**
    * @depends test_dbInstallation
    * @depends test_insertations
    */
    public function test_consCheck($eORM,$projects) {
        foreach ($projects as $project) {
            $this->assertTrue($eORM->cons_check($project));
        }
        $projects[1]->name = 'new name';
        $this->assertFalse($eORM->cons_check($projects[1]));
    } 

    /**
    * @depends test_dbInstallation
    */
    public function test_update($eORM) {
        $uproject = new project();
        $uproject->name = 'old name';
        $eORM->insert($uproject);
        $this->assertInternalType('int',$uproject->ID);
        
        $this->assertTrue($eORM->cons_check($uproject)); // returns true
        $uproject->name = "new name";
        $this->assertFalse($eORM->cons_check($uproject)); // returns false
        $eORM->update($uproject);
        $this->assertTrue($eORM->cons_check($uproject)); // returns true

        return $uproject;
    }

    /**
    * @depends test_dbInstallation
    * @depends test_update
    */
    public function test_delete($eORM,$uproject){
        $this->assertTrue($eORM->cons_check($uproject)); 
        $eORM->delete($uproject);
        $this->assertEquals(null,$uproject); 
    }

    /**
    * @depends test_dbInstallation
    * @depends test_insertations
    */
    public function test_query1($eORM){
        $query = $eORM->query(new project(),array());
        $this->assertInternalType('array',$query);
    }

    /**
    * @depends test_dbInstallation
    * @depends test_insertations
    */
    public function test_query2($eORM) {
        $query = $eORM->query(new project(),array(
            'crit1'=>array(
                'col'=>'ID',
                'is'=>2
            ), 'OR',
            'crit2'=>array(
                'col'=>'name',
                'contains'=>'4'
            )
        ));
        $expResult =  array (
            0 =>
            project::__set_state(array(
                'ID' => 2,
                'name' => 'name1',
            )),
            1 =>
            project::__set_state(array(
                'ID' => 5,
                'name' => 'name4',
            )),
        );
        $this->assertEquals($expResult,$query);
    }

    /**
    * @depends test_dbInstallation
    * @depends test_insertations
    */
    public function test_query3($eORM) {
        $expected = array (
        0 =>
        project::__set_state(array(
            'ID' => 2,
            'name' => 'name1',
        )),
        1 =>
        project::__set_state(array(
            'ID' => 4,
            'name' => 'name3',
        )),
        2 =>
        project::__set_state(array(
            'ID' => 5,
            'name' => 'name4',
        )),
        );
        $query = $eORM->query(new project(),array(
            'crit1'=>array(
                'col'=>'ID',
                'is'=>2
            ), 'OR',
            'crit2'=>array(
                'col'=>'ID',
                'is'=>4
            ), 'OR',
            'crit3'=>array(
                'col'=>'ID',
                'is'=>5
            )
        ));
        $this->assertEquals($expected,$query);
    }
}
?>