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
        "destinationTableName:",
        "campos::"
    );

    //Cogemos los parametros y los revisamos
    $data = getopt(" ",$values);
    checkParams($data);
    if(count($data)==12){
        $data["campos"] = explode(",",$data["campos"]);
    }
    //Funcion que revisa si el numero de parametros es correcto
    //Si es correcto intenta conectar a las BD.
    function checkParams($params){
        if(count($params)>12 || count($params)<11){
            echo "Error en la ejecucion del script: Numero de parametros incorrectos";
        }else{
            echo "Iniciando el proceso:" . $params["process"];
            checkConnection($params);
        }
    }

    //Se prueba la conexion de BD origen como BD destino
    function checkConnection($data){
        print_r(count($data));
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
            if(count($data)==12){
                $camposAnonimizar = explode(",",$data["campos"]);
                copyTable($conn1["pdo"],$conn2["pdo"],$data["process"],$data["destinationDB"],$data["originDB"],$data["originTableName"],$data["destinationTableName"],$camposAnonimizar);
            }else{
                copyTable($conn1["pdo"],$conn2["pdo"],$data["process"],$data["destinationDB"],$data["originDB"],$data["originTableName"],$data["destinationTableName"],null);                
            }
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
    function copyTable($pdoOrigen,$pdoDestino,$process,$dbDestino,$dbOrigen,$tablaOrigen, $tablaDestino,$camposAnonimizar){
        if($process=="create"){
            createTable($pdoOrigen,$pdoDestino,$dbOrigen,$dbDestino,$tablaOrigen, $tablaDestino,$camposAnonimizar);
        }
        elseif($process=="update"){
            updateTable($pdoOrigen,$pdoDestino,$dbOrigen,$dbDestino,$tablaOrigen,$tablaDestino,$camposAnonimizar);
        }
        
    }


    // Crea una tabla nueva en base a la tabla origen.
    // Y copia los datos de dicha tabla.
    function createTable($pdoOrigen,$pdoDestino,$dbOrigen,$dbDestino,$tablaOrigen,$tablaDestino,$camposAnonimizar){

        $existeTabla = checkTableExist($pdoOrigen,$tablaOrigen);
        print_r($existeTabla);
        if($existeTabla==true){
            $query = $pdoDestino->prepare("CREATE TABLE $dbDestino.$tablaDestino LIKE $dbOrigen.$tablaOrigen");
            if($query->execute()){
                $queryCopy = $pdoDestino->prepare("INSERT $dbDestino.$tablaDestino SELECT * FROM $dbOrigen.$tablaOrigen");
                if($queryCopy->execute()){
                    print_r("Se creo y se copiaron los datos con existo");
                    if($camposAnonimizar!=null){
                        anonFields($pdoOrigen,$camposAnonimizar,$dbDestino,$tablaDestino,$dbOrigen,$tablaOrigen);
                    }
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
    function updateTable($pdoOrigen,$pdoDestino,$dbOrigen,$dbDestino,$tablaOrigen, $tablaDestino,$camposAnonimizar){
        $existeTabla = checkTableExist($pdoOrigen,$tablaOrigen);
        if($existeTabla==true){
            $queryTruncate = $pdoDestino->prepare("TRUNCATE TABLE $dbDestino.$tablaDestino");
            if($queryTruncate->execute()){
                $queryCopy = $pdoDestino->prepare("INSERT INTO $dbDestino.$tablaDestino SELECT * FROM $dbOrigen.$tablaOrigen");
                if($queryCopy->execute()){
                    print_r("COPIA EXITOSA");
                    if($camposAnonimizar!=null){
                        anonFields($pdoOrigen,$camposAnonimizar,$dbDestino,$tablaDestino,$dbOrigen,$tablaOrigen);
                    }
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


    // Funcion que se encarga de hacer la consulta de anonimizar los campos especificados.
    function anonFields($pdo,$camposAnonimizar,$bdDestino,$tableDestino,$bdOrigen,$tablaOrigen){
        if($camposAnonimizar!=null){
            $stringAnon = checkFields($pdo,$camposAnonimizar,$bdOrigen,$tablaOrigen);
            $stringAnon = substr_replace($stringAnon,"",-1);
            print_r($stringAnon);
            $queryAnon = $pdo->prepare("UPDATE $bdDestino.$tableDestino SET {$stringAnon}");
            print_r($queryAnon);
            if($queryAnon->execute()){
                echo "Se anonimizaron algunos campos";
            }else{
                print_r($pdo->errorInfo());
                echo "Error de anonimazion";
            }
        }

    }


    // Funcion que verifica la existencia de los campos que se quieren anonimizar
    function checkFields($pdo,$camposAnonimizar,$bdOrigen,$tablaOrigen){
        //$testArr = array("campo1","nombre","email");
        $camposArr = array();
        $columnsQuery = $pdo->prepare("SHOW COLUMNS FROM $bdOrigen.$tablaOrigen");
        $columnsQuery->execute();
        $campos = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
        foreach($campos as $campo){
            if(in_array($campo["Field"],$camposAnonimizar)){
                array_push($camposArr,$campo["Field"]);
            }
        }
        return generateAnonQuery($camposArr);
        
    }


    // Funcion que genera el string a pasar a la consulta
    function generateAnonQuery($camposArr){
        $queryString="";
        foreach($camposArr as $campo){
            $anonField = md5($campo);
            $queryString = "{$queryString}{$campo}='{$anonField}',";
        }

        return $queryString;
    }

    
?>