# Solucion a problema planteado por SuperTech

La siguiente solucion presenta un script en php, al cual se le deben de pasar ciertos parametros para su correcto funcionamiento.

Los parametros a pasar al script son los siguientes:
- BD origen
- Host origen
- User origen
- Pass origen

(Por origen se comprende la base de datos de la cual se hara la copia de la tabla)

- BD destino
- Host destino
- User destino
- Pass destino
- Proceso a realizar
- Nombre de la tabla

Ejemplo de ejecucion de script: `php -q index.php --originDB=supertech --originHost=localhost --originUser=root --originPass=" " --destinationDB=test --destinationHost=localhost --destinationUser=root --destinationPass=" " --process=create`