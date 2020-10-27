<?php

    // Los parametros que puede tener el script
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
        "originTableName:",
        "destinationTableName:"
    );

    //Cogemos los parametros y los revisamos
    $data = getopt(" ",$values);
    checkParams($data);
  

    //Funcion que revisa si el numero de parametros es correcto
    //Si es correcto intenta conectar a las BD.
    function checkParams($params){
        if(count($params)!=11){
            echo "Error en la ejecucion del script: Numero de parametros incorrectos";
        }else{
            echo "Iniciando el proceso:" . $params["process"];
            checkConnection($params);
        }
    }

    //Se prueba la conexion de BD origen como BD destino
    function checkConnection($data){
        $origenHost = "mysql:host={$data["originHost"]};dbname={$data["originDB"]}";
        $data["originPass"] != " " ? $originUser = array("username"=>$data["originUser"],"pass"=>$data["originPass"]) :
         $originUser = array("username"=>$data["originUser"],"pass"=>$data["originPass"]);

        $conn1 = checkConnectionOrigen($origenHost,$originUser,$data["originDB"]);

        $destinoHost = "mysql:host={$data["destinationHost"]};dbname={$data["destinationDB"]}";
        $data["destinationPass"] != " " ? $destinoUser = array("username"=>$data["destinationUser"],"pass"=>$data["destinationPass"]) :
         $destinoUser = array("username"=>$data["destinationUser"],"pass"=>$data["destinationPass"]);

        $conn2 = checkConnectionDestination($destinoHost,$destinoUser,$data["destinationDB"]);

        //Si se da lugar la conexion se copia la tabla que viene dada como parametro.
        //Si no mostramos un mensaje
        if($conn1["result"]==1 AND $conn2["result"]==1){
            print_r("buena conexion ambos");

            copyTable($conn1["pdo"],$conn2["pdo"],$data["process"],$data["destinationDB"],$data["originTableName"],$data["destinationTableName"]);
        }
        else{
            print_r("ERROR CONEXION EN ALGUNA DE LAS BD");
        }
    }


    // Funcion que hace la conexion a BD origen
    function checkConnectionOrigen($host,$user, $dbname){
        if($user["pass"]!=" "){
            $pdo = new PDO($host,$user["username"],$user["pass"]);
            return checkExistDB($pdo, $dbname);
        }else{  
            try{
                $pdo = new PDO($host,$user["username"]);
                return checkExistDB($pdo, $dbname);
            }
            catch(PDOException $ex){
                echo $ex->getMessage();
                return array("result"=>0);
            }
        }
    }

    // Funcion que hace la conexion a BD destino.
    function checkConnectionDestination($host,$user,$dbname){
        if($user["pass"]!=" "){
            $pdo = new PDO($host,$user["username"],$user["pass"]);
            return checkExistDB($pdo, $dbname);

        }else{
            try{
                $pdo = new PDO($host,$user["username"]);
                return checkExistDB($pdo, $dbname);
            }
            catch(PDOException $ex){
                echo $ex->getMessage();
                return array("result"=>0);    
            }
        }
    }


    // Funcion que se encarga de revisar que la BD exista.
    function checkExistDB($pdo,$db){
        $query = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=:dbname");
        $query->execute(["dbname"=>$db]);
        return array("result"=>((bool)$query->fetchColumn()), "pdo"=>$pdo);
    }

    // Funcion que se encarga de copiar la tabla especificada en los parametros
    // Toma asi mismo el proceso a realizar, crear o actualizar una tabla.
    function copyTable($pdoOrigen,$pdoDestino,$process,$db,$tablaOrigen, $tablaDestino){
        if($process=="create"){
            createTable($pdoOrigen,$pdoDestino,$db,$tablaOrigen, $tablaDestino);
        }
        elseif($process=="update"){
            updateTable($pdoOrigen,$pdoDestino,$db,$tablaOrigen,$tablaDestino);
        }
        
    }


    // Crea una tabla nueva en base a la tabla origen.
    // Y copia los datos de dicha tabla.
    function createTable($pdoOrigen,$pdoDestino,$db,$tablaOrigen,$tablaDestino){

        $existeTabla = checkTableExist($pdoOrigen,$tablaOrigen);
        print_r($existeTabla);
        if($existeTabla==true){
            $query = $pdoDestino->prepare("CREATE TABLE $db.$tablaDestino LIKE supertech.$tablaOrigen");
            if($query->execute()){
                $queryCopy = $pdoDestino->prepare("INSERT $db.$tablaDestino SELECT * FROM supertech.$tablaOrigen");
                if($queryCopy->execute()){
                    print_r("Se creo y se copiaron los datos con existo");
                    $pdoOrigen = null;
                    $pdoDestino = null;
                }else{
                    print_r($pdoDestino->errorInfor());
                    print_r("Error en copiado de datos");
                    $pdoOrigen = null;
                    $pdoDestino = null;
                }
            }else{
                print_r($pdoDestino->errorInfo());
                print_r("La tabla ya existe. Intente una actualizacion de la tabla.");
                $pdoOrigen=null;
                $pdoDestino=null;
            }
        }else{
            $pdoOrigen=null;
            $pdoDestino=null;
            echo "No existe la tabla que se quiere copiar";
        }
    }

    // Funcion para actualizar una tabla
    // Primero se trunca la tabla y se eliminan sus datos
    // Luego se copia de la tabla origen
    function updateTable($pdoOrigen,$pdoDestino,$db,$tablaOrigen, $tablaDestino){
        $existeTabla = checkTableExist($pdoOrigen,$tablaOrigen);
        if($existeTabla==true){
            $queryTruncate = $pdoDestino->prepare("TRUNCATE TABLE $db.$tablaDestino");
            if($queryTruncate->execute()){
                $queryCopy = $pdoDestino->prepare("INSERT INTO $db.$tablaDestino SELECT * FROM supertech.$tablaOrigen");
                if($queryCopy->execute()){
                    print_r("COPIA EXITOSA");
                    $pdoOrigen=null;
                    $pdoDestino=null;
                }else{
                    print_r("No se pudo actualizar");
                    $pdoOrigen=null;
                    $pdoDestino=null;
                }
            }else{
                print_r("No se pudo truncar la tabla");
                $pdoOrigen=null;
                    $pdoDestino=null;
            }
        }else{  
            $pdoOrigen=null;
            $pdoDestino=null;
            echo "No existe la tabla que se quiere copiar";
        }
    }

    // Funcion que verifica la existencia de la tabla a copiar.
    function checkTableExist($pdo,$tableName){
        $query = $pdo->prepare("SELECT COUNT(*) FROM $tableName");
        if($query->execute()){
            if($query->fetch()>0){
                return true;
            }else{
                return false;
            }
        }

    }
?>