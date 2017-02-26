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
    public function test_install($eORM) {
        $eORM->install(true,'
            CREATE TABLE autor(
                ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(30)
            );

            CREATE TABLE tag(
                ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                begriff VARCHAR(30)
            );

            CREATE TABLE doku(
                ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                titel VARCHAR(30) NOT NULL,
                kurzbeschreibung VARCHAR(30),
                inhalt TEXT,
                erstellungsdatum DATE,
                aenderungsdatum DATE,
                autor_ID INT,
                FOREIGN KEY(autor_ID) REFERENCES autor(ID)
            );

            CREATE TABLE doku_tag(
                ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                tag_ID INTEGER NOT NULL,
                doku_ID INTEGER NOT NULL,
                FOREIGN KEY(tag_ID) REFERENCES tag(ID),
                FOREIGN KEY(doku_ID) REFERENCES doku(ID)
            );

            -- Indexing
            CREATE INDEX i_dt_tag ON doku_tag(tag_ID);
            CREATE INDEX i_dt_doku ON doku_tag(doku_ID);
            CREATE INDEX i_doku ON doku(autor_ID);
        ');
        $eORM->start();
        $this->assertTrue($eORM->status());
        $this->assertFileExists($eORM->database->path);
        $this->assertTrue(class_exists('autor'));
        $this->assertTrue(class_exists('doku_tag'));
        $this->assertTrue(class_exists('doku'));
        $this->assertTrue(class_exists('tag'));
        return $eORM;
    }

    /**
    * @depends test_initialize_eORM 
    */
    public function test_inserts($eORM){
        $objs = array();
        $objs['autor'] = array();
        for($i=0;$i<5;$i++){
            $objs['autor'][$i] = autor::__set_state(array(
                'name'=>'object'.$i
            ));
            $eORM->insert($objs['autor'][$i]);
            $this->assertInternalType('int',$objs['autor'][$i]->ID);
        }
        $objs['tag'] = array();
        for($i=0;$i<5;$i++){
            $objs['tag'][$i] = tag::__set_state(array(
                'begriff'=>'object'.$i
            ));
            $eORM->insert($objs['tag'][$i]);
            $this->assertInternalType('int',$objs['tag'][$i]->ID);
        }
        $objs['doku'] = array();
        for($i=0;$i<5;$i++){
            $objs['doku'][$i] = doku::__set_state(array(
                'titel'=>'object'.$i,
                'inhalt'=>'<h1>titel</h1>testinhalt',
                'autor_ID'=>1
            ));
            $eORM->insert($objs['doku'][$i]);
            $this->assertInternalType('int',$objs['doku'][$i]->ID);
        }
        for($i=0;$i<5;$i++){
            $objs['doku_tag'][$i] = doku_tag::__set_state(array(
                'tag_ID'=>$i,
                'doku_ID'=>$i
            ));
            $eORM->insert($objs['doku_tag'][$i]);
            $this->assertInternalType('int',$objs['doku_tag'][$i]->ID);
        }
        return $objs;
    }

    /**
    * @depends test_initialize_eORM 
    * @depends test_inserts
    */
    public function test_update($eORM,$objs) {
        foreach ($objs['autor'] as $autor) {
            $this->assertTrue($eORM->check($autor));
        }
        $objs['autor'][1]->name = 'new name';
        $this->assertFalse($eORM->check($objs['autor'][1]));
        $eORM->update($objs['autor'][1]);
        $this->assertTrue($eORM->check($objs['autor'][1]));
        $objs['autor'][1]->name = 'object1';
        $eORM->update($objs['autor'][1]);
    } 


    /**
    * @depends test_initialize_eORM 
    * @depends test_inserts
    */
    public function test_query($eORM){
        $expResult = array (
        0 =>
        autor::__set_state(array(
            'ID' => '1',
            'name' => 'object0',
        )),
        1 =>
        autor::__set_state(array(
            'ID' => '2',
            'name' => 'object1',
        )),
        2 =>
        autor::__set_state(array(
            'ID' => '3',
            'name' => 'object2',
        )),
        3 =>
        autor::__set_state(array(
            'ID' => '4',
            'name' => 'object3',
        )),
        4 =>
        autor::__set_state(array(
            'ID' => '5',
            'name' => 'object4',
        )),
        );
        $this->assertEquals($expResult,$eORM->query(new autor(),array()));
    }

}
?>