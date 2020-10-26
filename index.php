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
        "process:"
    );
    $data = getopt(" ",$values);
    checkParams($data);
  
    function checkParams($params){
        if(count($params)!=9){
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

             copyTable($conn2["pdo"]);
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

    function copyTable($pdo){
        $query = $pdo->prepare("CREATE TABLE test.usuarios LIKE supertech.usuarios");
        if($query->execute()){
            print_r("works");
            $queryCopy = $pdo->prepare("INSERT test.usuarios SELECT * FROM supertech.usuarios");
            if($queryCopy->execute()){
                print_r("COPIO DATOS DE TABLA");
            }else{
                print_r("NO SE PUDO HACER COPIA");
            }
        }else{
            print_r("ERROR COPIA DE TABLA");
        }
    }
?>