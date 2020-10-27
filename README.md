# Solucion a problema planteado por SuperTech

La siguiente solucion presenta un script en php, al cual se le deben de pasar ciertos parametros para su correcto funcionamiento.

La solucion toma los parametros explicados a continuacion, verifica que puede conectar a ambas bases de datos y dependiendo de el proceso (actualizacion/creacion) crea o actualiza la tabla origen a copiar. El proceso de creacion es una consulta SQL de crear tabla, mientras el proceso de actualizacion hacemos un truncate de la tabla destino y luego copiamos todos los datos de la tabla origen, esto porque una siemple insercion de datos genera problemas al copiar las llaves primarias.

Los parametros a pasar al script son los siguientes:
- BD origen
- Host origen
- User origen
- Pass origen
- BD destino
- Host destino
- User destino
- Pass destino
- Proceso a realizar
- Nombre de la tabla origen
- Nombre de la tabla destino
- Campos a anonimizar (Opcional)

Ejemplo de ejecucion de script: `php -q index.php --originDB=supertech --originHost=localhost --originUser=root --originPass=" " --destinationDB=test --destinationHost=localhost --destinationUser=root --destinationPass=" " --process=create --originTableName=usuarios --destinationTableName=copy_usuarios --campos=nombre,email`