c<?php
include_once 'Analizer.php';

$firstTable = true;

function writeTableCodes(Table $t, Analizer $a = null, $proceduresToDb = false)
{
	global $firstTable;
	try{
		echo 'Writing code for table ' . $t->getName();
		$fSql = fopen('gen/sql/' . ucfirst($t->getName()) . '.sql', 'a+');
		$fPhp = fopen('gen/model/' . $t->getCamelCaseName() . '.php', 'a+');
		$fSqlTodos = fopen('gen/sql/todos.sql', 'a+');
		$fSqlTodosDrop = fopen('gen/sql/todos_drop.sql', 'a+');
		
		$selectProcedure = $t->getSelectProcedure();
		$insertProcedure = $t->getInsertProcedure();
		$updateProcedure = $t->getUpdateProcedure();
		fwrite($fSql     , $selectProcedure . "\n");
		fwrite($fSqlTodos, $selectProcedure . "\n");
		fwrite($fSqlTodosDrop, 'DROP PROCEDURE ' . $a->getDbName() . '.' . $t->getSelectProcedureName() . ";\n");
		echo '.';
		fwrite($fSql     , $insertProcedure . "\n");
		fwrite($fSqlTodos, $insertProcedure . "\n");
		fwrite($fSqlTodosDrop, 'DROP PROCEDURE ' . $a->getDbName() . '.' . $t->getInsertProcedureName() . ";\n");
		echo '.';
		fwrite($fSql     , $updateProcedure . "\n");
		fwrite($fSqlTodos, $updateProcedure . "\n");
		fwrite($fSqlTodosDrop, 'DROP PROCEDURE ' . $a->getDbName() . '.' . $t->getUpdateProcedureName() . ";\n");
		echo '.';
		fwrite($fPhp, $t->getMapper() . "\n");
		echo '.';
		fclose($fSql);
		fclose($fPhp);
		fclose($fSqlTodos);
		fclose($fSqlTodosDrop);
		if ($proceduresToDb && $a !== null){
			echo ' In DB... ';
			$a->dropTableProcedures($t);
			$a->createTableProcedures($t);
		}
		echo "OK\n";
	}
	catch(Exception $e){
		echo 'Error generating files for ' . $t->getName() . ': ' . $e->getMessage() . "\n";
	}
}

function deleteFiles($root){
	echo 'deleteFiles(' . $root . ")\n";
	if (!is_dir($root))
		return;
	$files = scandir($root);
	if ($files === false)
		return;
	foreach($files as $file){
		if ($file != '.' && $file != '..'){
			$name = $root . '/' . $file;
			if (is_dir($name))
				deleteFiles($name);
			else
				unlink($name);
		}
	}
}

function showUsage($error = null){
	if ($error !== null){
		echo "\nError: " . $error . "\n";
	}
	echo "Usage:\n";
	echo "php Gen.php -h <server> -u <user> -p <password> -s <schema> [-df <dateFormat>] [-b] [-a | -t <tables>]\n\n";
	echo "\t-h : Indicates server. Default is localhost\n";
	echo "\t-u : Username to connect to database\n";
	echo "\t-p : Password for user to connect\n";
	echo "\t-s : Schema to use on the server, when connection is stablished\n";
	echo "\t-df: Specify date format. Default is %d/%m/%Y\n";
	echo "\t-b : Write generated procedures to DB\n";
	echo "\t-a : Generate models and scripts for ALL tables\n";
	echo "\t-t : Specify list of tables to parse (space separated)\n\n";
}


try
{
	$dateFormat    = '%d/%m/%Y';
	$procedureInDb = false;
	$allTables     = false;
	$specifyTables = false;
	
	if (!is_dir('gen'))
		mkdir('gen');
	if (!is_dir('gen/sql'))
		mkdir('gen/sql');
	if (!is_dir('gen/model'))
		mkdir('gen/model');
	
	$paramCount = count($argv);
	$tables = array();

	$tablesNames = array();
	$host        = 'localhost';
	$user        = null;
	$password    = null;
	$schema      = null;

	
	for($i = 1; $i < $paramCount; ++$i){
		$ok = false;
		if ($argv[$i] == '-h'){
			++$i;
			$server = $argv[$i];
			$ok = true;
		}
		if ($argv[$i] == '-u'){
			++$i;
			$user = $argv[$i];
			$ok = true;
		}
		if ($argv[$i] == '-p'){
			++$i;
			$password = $argv[$i];
			$ok = true;
		}
		if ($argv[$i] == '-s'){
			++$i;
			$schema = $argv[$i];
			$ok = true;
		}
		if ($argv[$i] == '-df'){
			//Date format specification
			++$i;
			$dateFormat = $argv[$i];
			$ok = true;
		}
		else if ($argv[$i] == '-b'){
			//Store in BD
			$procedureInDb = true;
			$ok = true;
		}
		else if ($argv[$i] == '-a'){
			$allTables = true;
			$ok = true;
		}
		else if ($argv[$i] == '-t'){
			//Specify tables
			$specifyTables = true;
			++$i;
			$tablesNames = explode(',', str_replace(' ', '', $argv[$i]));
			$ok = true;
		}
		else{
			if (!$specifyTables && !$ok ){
				showUsage();
			}
		}
	}
	
	if ($user === null || $user == ''){
		showUsage('User must be specified (-u option)');
		exit;
	}
	if ($password === null || $password == ''){
		showUsage('Password must be specified (-p option)');
		exit;
	}
	if ($schema === null || $schema == ''){
		showUsage('Schema must be specified (-s option)');
		exit;
	}
	
	$a = new MySqlAnalizer($host, $user, $password, $schema/*'plas_8y1b5'*/);
	
	deleteFiles('gen');
	$fSqlTodos = fopen('gen/sql/todos.sql', 'a+');
	fwrite($fSqlTodos, "DELIMITER \$\$;\n");
	fclose($fSqlTodos);
	
	if (!$allTables){
		echo "\n";
		foreach($tables as $tbl){
			writeTableCodes($tbl, $a, $procedureInDb);
		}
	}
	else{
		$tables = $a->getTables();
		echo "\n";
		foreach($tables as $table){
			writeTableCodes($table, $a, $procedureInDb);
		}
	}
	echo "Done!\n";
}
catch(Exception $e){
	echo 'Error: ' . $e->getMessage() . "\n";
}
?>
