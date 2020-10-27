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
            updateTable($pdo,$db,$tabla,$tablaOrigen,$tablaDestino);
        }
        
    }


    // Crea una tabla nueva en base a la tabla origen.
    // Y copia los datos de dicha tabla.
    function createTable($pdoOrigen,$pdoDestino,$db,$tablaOrigen,$tablaDestino){

        $existeTabla = checkTableExist($pdoOrigen,$tablaOrigen);
        print_r($existeTabla);
        if($existeTabla==1){
            $query = $pdoDestino->prepare("CREATE TABLE $db.$tablaDestino LIKE supertech.$tablaOrigen");
            if($query->execute()){
                print_r("works");
                $queryCopy = $pdoDestino->prepare("INSERT $db.$tablaDestino SELECT * FROM supertech.$tablaOrigen");
                if($queryCopy->execute()){
                    print_r("COPIO DATOS DE TABLA");
                }else{
                    print_r($pdoDestino->errorInfor());
                    print_r("NO SE PUDO HACER COPIA");
                }
            }else{
                print_r($pdoDestino->errorInfo());
                print_r("ERROR COPIA DE TABLA");
            }
        }else{
            $pdoOrigen=null;
            $pdoDestino=null;
            echo "No existe la tabla que se quiere copiar";
        }
    }
    function updateTable($pdo,$db,$tablaOrigen, $tablaDestino){
        $queryCopy = $pdo->prepare("INSERT INTO $db.$tablaDestino SELECT * FROM supertech.$tablaOrigen");
        if($queryCopy->execute()){
            print_r("COPIO DATOS DE TABLA");
        }else{
            print_r($pdo->errorInfo());
            print_r("NO SE PUDO HACER COPIA");
        }
    }

    function checkTableExist($pdo,$tableName){
        $query = $pdo->prepare("SHOW TABLES");
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        $exists = 0;
        foreach($data as $table){
            foreach($table as $name){
                if($name==$tableName){
                    $exists =1;
                    break 2;
                }else{
                    $exists = 0;
                }
            }
        }
        return $exists;

    }
?>