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
    print_r($data);
    checkParams($data);
    /*
    try{
        $pdo = new PDO($hostDB,$hostUser);
        $query = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=:dbname");
        $query->execute(['dbname'=>$dbName]);
        //$version = $query->fetch();

        echo json_encode((bool) $query->fetchColumn());
    }
    catch(PDOException $e){
        echo json_encode($e);
    }

*/

    function checkParams($params){
        if(count($params)!=9){
            echo "Error en la ejecucion del script: Numero de parametros incorrectos";
        }else{
            echo "Iniciando el proceso:" . $params["process"];
        }
    }
?>