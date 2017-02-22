<?php 
require('../eORM/eORM.php');
$eORM = new eORM();
?>
<html>
<body>
<pre>
require('eORM/eORM.php');
$eORM = new eORM();
</pre>

<h3>database creation</h3>
<?php 
$eORM->DBinstallation('
CREATE TABLE project(
    ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR(30)
);
');
?>
<pre>
$eORM->DBinstallation('
CREATE TABLE project(
    ID  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR(30)
);
');
</pre>
Connection Status: <?php $eORM->connect(); var_dump($eORM->ConnectionStatus()) ?>

<h3>create objects</h3>
<?php
$projects = array(); 
for ($i = 0; $i < 5; $i++) {
    $projects[$i] = new project;
    $projects[$i]->name = "name$i";
}
?>
<pre>
$projects = array(); 
for ($i = 0; $i < 5; $i++) {
    $projects[$i] = new project;
    $projects[$i]->name = "name$i";
}
</pre>
$projects dump<?php var_dump($projects) ?>

<h3>save objects</h3>
<?php
foreach ($projects as $project){
    $eORM->insert($project);
}
?>
<pre>
foreach ($projects as $project){
    $eORM->insert($project);
} 
</pre>
$projects dump<?php var_dump($projects) ?>

<h3>consistency check</h3>
<pre>
    $project = new project();
    $project->name = 'old name';
    $eORM->insert($project);
    var_dump($eORM->cons_check($project)); // returns true
    $project->name = "new name";
    var_dump($eORM->cons_check($project)); // returns false
    $eORM->update($project);
    var_dump($eORM->cons_check($project)); // returns true
</pre>
<?php
    $project = new project();
    $project->name = 'old name';
    $eORM->insert($project);
    var_dump($eORM->cons_check($project)); // returns true
    $project->name = "new name";
    var_dump($eORM->cons_check($project)); // returns false
    $eORM->update($project);
    var_dump($eORM->cons_check($project)); // returns true
?>

<h3>query objects</h3>
<h4>all projects</h4>
<?php 
$query = $eORM->query(new project(),array());
?>
<pre>
$query = $eORM->query(new project(),array());
</pre>
$query dump<?php var_dump($query) ?>

<h4>ID 2 OR 4 OR 5</h4>
<?php 
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
?>
<pre>
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
</pre>
$query dump<?php var_dump($query) ?>

<h4>ID 2 OR name contains 4</h4>
<?php 
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
?>
<pre>
$query = $eORM->query(new project(),array(
    'crit1'=>array(
        'col'=>'ID',
        'is'=>2
    ), 'OR',
    'crit2'=>array(
        'col'=>'name',
        'contains'=>'4'
    )
));</pre>
$query dump<?php var_dump($query) ?>




</body>
</html>