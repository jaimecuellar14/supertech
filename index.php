<?php

    $values = array(
        "originDB:",
        "originHost:",
        "originUser:",
        "originPass::",
        "destinationDB:",
        "destinationHost:",
        "destinationUser:",
        "destinationPass::",
        "process:",
        "tableName:"
    );
    $data = getopt(" ",$values);
    checkParams($data);
  
    function checkParams($params){
        if(count($params)!=10){
            echo "Error en la ejecucion del script: Numero de parametros incorrectos";
        }else{
            echo "Iniciando el proceso:" . $params["process"];
            checkConnection($params);
        }
    }

    function checkConnection($data){
        $origenHost = "mysql:host={$data["originHost"]};dbname={$data["originDB"]}";
        $data["originPass"] != " " ? $originUser = array("username"=>$data["originUser"],"pass"=>$data["originPass"]) :
         $originUser = array("username"=>$data["originUser"],"pass"=>$data["originPass"]);

         $conn1 = checkConnectionOrigen($origenHost,$originUser,$data["originDB"]);

         $destinoHost = "mysql:host={$data["destinationHost"]};dbname={$data["destinationDB"]}";
        $data["destinationPass"] != " " ? $destinoUser = array("username"=>$data["destinationUser"],"pass"=>$data["destinationPass"]) :
         $destinoUser = array("username"=>$data["destinationUser"],"pass"=>$data["destinationPass"]);

         $conn2 = checkConnectionDestination($destinoHost,$destinoUser,$data["destinationDB"]);

         if($conn1["result"] AND $conn2["result"]){
             print_r("buena conexion ambos");

             copyTable($conn2["pdo"],$data["process"],$data["destinationDB"],$data["tableName"]);
         }
         else{
             print_r("ERROR CONEXION");
         }
    }

    function checkConnectionOrigen($host,$user, $dbname){
        if($user["pass"]!=" "){
            $pdo = new PDO($host,$user["username"],$user["pass"]);
            return checkExistDB($pdo, $dbname);
        }else{
            $pdo = new PDO($host,$user["username"]);
            return checkExistDB($pdo, $dbname);
   
        }
    }

    function checkConnectionDestination($host,$user,$dbname){
        if($user["pass"]!=" "){
            $pdo = new PDO($host,$user["username"],$user["pass"]);
            return checkExistDB($pdo, $dbname);

        }else{
            try{
                $pdo = new PDO($host,$user["username"]);
                return checkExistDB($pdo, $dbname);
            }
            catch(PDOException $e){
                echo "Error de conexion";    
            }
        }
    }

    function checkExistDB($pdo,$db){
        $query = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=:dbname");
        $query->execute(["dbname"=>$db]);
        //print_r((bool)$query->fetchColumn());
        return array("result"=>((bool)$query->fetchColumn()), "pdo"=>$pdo);
    }

    function copyTable($pdo,$process,$db,$tabla){
        if($process=="create"){
            createTable($pdo,$db,$tabla);
        }
        elseif($process=="update"){
            updateTable($pdo,$db,$tabla);
        }
        
    }

    function createTable($pdo,$db,$tabla){
        $query = $pdo->prepare("CREATE TABLE $db.$tabla LIKE supertech.$tabla");
        if($query->execute()){
            print_r("works");
            $queryCopy = $pdo->prepare("INSERT $db.$tabla SELECT * FROM supertech.$tabla");
            if($queryCopy->execute()){
                print_r("COPIO DATOS DE TABLA");
            }else{
                print_r($pdo->errorInfor());
                print_r("NO SE PUDO HACER COPIA");
            }
        }else{
            print_r($pdo->errorInfo());
            print_r("ERROR COPIA DE TABLA");
        }
    }
    function updateTable($pdo,$db,$tabla){
        $queryCopy = $pdo->prepare("INSERT INTO $db.$table SELECT * FROM supertech.$table");
        if($queryCopy->execute()){
            print_r("COPIO DATOS DE TABLA");
        }else{
            print_r($pdo->errorInfo());
            print_r("NO SE PUDO HACER COPIA");
        }
    }
?>