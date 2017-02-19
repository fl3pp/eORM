# elemental ORM for PHP
Simple Object relational mapper for PHP

currently only supporting SQLite Databases 

## usage rules
1. never change a dynamicaly created file
1. always add an 'ID' column in all tables

## configuration
There is a configuration file called config.ini inside the ORM Folder.
``` ini
; INFO: All path are written without a last slash

[folders]
; Folder where dynamicaly created classes are saved
models="C:\xampp\htdocs\model"

[database]
; database location (full OS Path recommended)
db="C:\xampp\htdocs\database\db.db"
; database script location
dbscript="C:\xampp\htdocs\database\sql\database.sql"

[admin_password]
; aministratorPassword for direct database operations
admin_password=testPW

[requisites]
; only needed for DB dump and import.  
; leave emtpy if in system variables
sqlite_path="C:\Program Files (x86)\sqlite\sqlite3.exe"
```
you can change the file so it fits your needs. 
After the configuring the file, create a new php script and
call `DBinstallation` on a eORM object.
``` php
require('libs/eORM/eORM.php');
$data = new eORM();
$data->DBinstallation();
```
The function will ask you for the eORM password and 
will automaticly generate all table classes in the desired
folder.
## manage table objects
lets assume you generated a database using following script
``` SQL
CREATE TABLE IF NOT EXISTS project(
    ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR(30)
);
```
you can now create objects using the generated classes
``` php
$project = new project();
```
### insert new object
to insert a new object use the `ìnsert()` method of eORM
``` php
$eORM->insert($project);
```
The object will automaticly obtain a new ID
### delete object
Call `delete` for delete operations
```php
$eORM->delete($project);
```
the object will automaticly be set to null if the operation was
successfull.
### update object
simply call `update` 
``` php
$eORM->update($project)
```

### query for objects
to query objects use the `query` function. Pass an array
and an instance object of the desired class.
query syntax:
``` php
// search after an ID
$eORM->query(new project(),array('ID'=>3));

// search for all objects containing the keyword 'test' in the name
$eORM->query($project(),array(
    'name'=> array(
        'contains'=>'test'
    )
));

// search for all objects starting with 'tes'
$eORM->query(new project(),array(
    'name'=> array(
        'start'=>'tes'
    )
));

// search for all objects ending with 'est'
$eORM->query(new project(),array(
    'name'=> array(
        'end'=>'est'
    )
));

//You can also combine several attributes
$eORM->query(new project(),array(
    'name'=> array(
        'contains'=>'e'
    ),
    'foo'=> 'bar'
));
```
There are also two optional offset and limit parameters.
The default values are
- offset: 0
- limit: 100
``` php
$eORM->query(new object(),array(),0,100);
```

The query returns only one object if there is only one result
and an array of objects if there are several results.
### consisteny check
you can also check if an object is consistent
with the database by using the `cons_check` function
``` php
$project = new project();
$project->name = "project";
$eORM->insert($project);

var_dump($eORM->cons_check($project)); // returns true
$project->name = "new name";
var_dump($eORM->cons_check($project)); // returns false
$eORM->update($project);
var_dump($eORM->cons_check($project)); // returns true
```