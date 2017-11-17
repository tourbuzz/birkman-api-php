<?php

// dev run
// CREATE DATABSE birkman;
$devDbUrl = 'pgsql://localhost:5432/birkman';
$dbopts = parse_url((false === getenv('DATABASE_URL')) ? $devDbUrl : getenv('DATABASE_URL'));

$conn = new PDO(
    'pgsql:dbname='.ltrim($dbopts["path"], '/').';host='.$dbopts["host"] . ';port=' . $dbopts["port"],
    $dbopts["user"] ?? null ,
    $dbopts["pass"] ?? null
);

$schemaStmt = $conn->prepare(file_get_contents(__DIR__.'/schema.sql'));
$schemaStmt->execute();

return $conn;
