<?php
try {
    $dbh = new PDO('mysql:host=rm-bp1bkhjm2unm61hq9.mysql.rds.aliyuncs.com;dbname=erp', 'tk', 'Tk123456');
    foreach($dbh->query('SELECT * from sys_user') as $row) {
        print_r($row);
    }
    $dbh = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>