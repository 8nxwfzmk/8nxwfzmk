<?php
// connect server
function connectServer($host, $username, $password, $charset = 'utf8mb4') 
{
    $dsn = "mysql:host=$host;charset=$charset";
    try {
        // new PDO to connect server
        $pdo = new PDO($dsn, $username, $password);
        // error handling mode
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected to MySQL server successfully!<br>";
        return $pdo;
    } 
    catch (PDOException $e) {
        // if connect failed
        echo "Connection failed: " . $e->getMessage() . "<br>";
        return null;
    }
}

function createDatabase($pdo, $databaseName) 
{
    try {
        $sql = "CREATE DATABASE IF NOT EXISTS `$databaseName`";  // Use backticks to prevent special characters from causing errors
        $pdo->exec($sql);

        echo "Database '$databaseName' created or already exists.<br>";

    } 
    catch (PDOException $e) {
        echo "Database creation error: " . $e->getMessage() . "<br>";
    }
}

// connect database
function connectDatabase($host, $username, $password, $databaseName, $charset = 'utf8mb4') {
    $dsn = "mysql:host=$host;dbname=$databaseName;charset=$charset";  //assigned database

    try {
        $pdo = new PDO($dsn, $username, $password);  // new PDO to connect database
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "Connected to database '$databaseName' successfully!<br>";
        return $pdo;  //
    } catch (PDOException $e) {
        echo "Connection to database failed: " . $e->getMessage() . "<br>";
        return null;  // if connect fail return null
    }
}

// create table
function createTable($pdo, $tableName) {
    if ($pdo) {  // check connect is fine
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            age INT
        )";

        try {
            $pdo->exec($sql);  // create table
            echo "Table '$tableName' created or already exists.<br>";
        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Invalid PDO connection. Cannot create table.<br>";
    }
}



// insert function
function insertTable($pdo, $tableName, $name, $email, $age) {
    echo "Inserting into table: $tableName<br>";  // check current table
    try {
        $sql = "INSERT INTO `$tableName` (name, email, age) VALUES (:name, :email, :age)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'age' => $age,
        ]);

        echo "User created with ID: " . $pdo->lastInsertId() . "<br>";
    } catch (PDOException $e) {
        echo "Insert error: " . $e->getMessage() . "<br>";
    }
}

// read function
function readTable($pdo, $tableName) {
    echo "Reading from table: $tableName<br>";  // check current table
    try {
        $sql = "SELECT * FROM `$tableName`";
        $stmt = $pdo->query($sql);

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            echo "ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Age: {$user['age']}<br>";
        }
    } catch (PDOException $e) {
        echo "Read error: " . $e->getMessage() . "<br>";
    }
}

// update function
function updateTable($pdo, $tableName, $id, $name, $age) {
    echo "Update from table: $tableName<br>";  // check current table
    try {
        $sql = "UPDATE `$tableName` SET name = :name, age = :age WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'age' => $age,
            'id' => $id,
        ]);

        echo "User updated.<br>";
    } catch (PDOException $e) {
        echo "Update error: " . $e->getMessage() . "<br>";
    }
}

// delete function
function deleteTable($pdo, $tableName, $criteria) {
    echo "Deleting from table: $tableName<br>";  // check current table
    $whereClauses = [];
    $params = [];
    $outputConditions = [];

    foreach ($criteria as $key => $value) {
        if (!empty($value)) {  // only non-null value put in  WHERE clause
            $whereClauses[] = "$key = :$key";
            $params[$key] = $value;  //
            $outputConditions[] = "$key = '$value'";  // save actual value for output
        }
    }

    if (empty($whereClauses)) {
        echo "At least one valid condition must be provided to delete records.<br>";
        return;
    }

    $whereClause = implode(" AND ", $whereClauses);  // build WHERE clause

    // check if match or not
    $checkSql = "SELECT COUNT(*) FROM `$tableName` WHERE $whereClause";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($params);  // search
    $count = $checkStmt->fetchColumn();  // count successfully match number

    if ($count == 0) {  // if no match
        echo "No matching records found with the given conditions: " . implode(" AND ", $outputConditions) . ".<br>";
        return;
    }

    // if match ,then delete
    $sql = "DELETE FROM `$tableName` WHERE $whereClause";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo "User deleted " . implode(" AND ", $outputConditions) . ".<br>";
    } catch (PDOException $e) {
        echo "Delete error: " . $e->getMessage() . "<br>";
    }
}




$serverConnection = connectServer('localhost', 'user_test', 'user_password');  // connect server
createDatabase($serverConnection,'user_database');  // create database: 'user_database'
$databaseConnection = connectDatabase('localhost', 'user_test', 'user_password', 'user_database');  // connect database
createTable($databaseConnection, 'user_table');  // create tabel: 'user_table'

if ($databaseConnection) {
    $tableName = 'user_table';  // assign table
    //check current connected database
    $currentDatabase = $databaseConnection->query("SELECT DATABASE()")->fetchColumn();
    if ($currentDatabase) {
        echo "Currently connected to database: $currentDatabase<br>";
    } else {
        echo "No database selected. Ensure the connection is valid.<br>";
    }
    //CRUD
    // create
    insertTable($databaseConnection, $tableName, 'Sam Huang', 'sam.corgi@example.com', 30);

    // read
    readTable($databaseConnection, $tableName);

    // update
    updateTable($databaseConnection, $tableName, 41, 'Sam Huang', 28);

    // delete
    deleteTable($databaseConnection, $tableName, [//can't all null
        'id' =>'',//can null
        'name' => '',  // can null
        'email' => '',  // can null
        'age' => '28'  // can null
    ]);
}
