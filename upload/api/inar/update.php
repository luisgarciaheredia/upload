<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('max_execution_time', 480);
ini_set('memory_limit', '1024M');
date_default_timezone_set('America/Lima');
require '../../../vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\BigQuery\BigQueryClient;

$requestMethod = $_SERVER["REQUEST_METHOD"];

switch ($requestMethod) {
    case 'POST':
        $filename = filter_input(INPUT_POST, 'filename');


        // verify filename

        if (!empty($filename)) {


            // get cloud storage

            $storage = new StorageClient([
                'projectId' => "entel-ecommerce",
                'keyFilePath' => "../../../entel-ecommerce-5b11ddb572e4.json"
            ]);
            $bucket = $storage->bucket('entel-ecommerce-bucket');
            $object = $bucket->object($filename);


            // verify file

            if ($object->exists()) {


                // get date

                $date = substr($filename, 8, 10);
                $time = strtotime($date);
                $newDate = date('Ym', $time);


                // get big query

                $bigQuery = new BigQueryClient([
                    'projectId' => "entel-ecommerce",
                    'keyFilePath' => "../../../entel-ecommerce-5b11ddb572e4.json"
                ]);


                // delete data

                $queryJobConfig = $bigQuery->query('
                    DELETE FROM `entel-ecommerce.entel_ds_ecommerce.inar_v3`
                    WHERE
                      PERIODO = "' . $newDate . '"
                ');
                $bigQuery->runQuery($queryJobConfig);


                // import data from cloud storage

                $dataset = $bigQuery->dataset("entel_ds_ecommerce");
                $table = $dataset->table("inar_v3");
                $loadJobConfig = $table->loadFromStorage($object);
                $loadJobConfig->fieldDelimiter(';');
                $job = $table->runJob($loadJobConfig);


                // verify error

                if (empty($job->info()["status"]["errorResult"])) {


                    // bigquery last update

                    $queryJobConfig_2 = $bigQuery->query('
                        INSERT INTO `entel-ecommerce.entel_ds_ecommerce.ultimas_actualizaciones` (
                            tabla,
                            ultima_actualizacion
                        )
                        VALUES (
                            "inar_v3",
                            "' . date("Y-m-d H:i:s") . '"
                        )
                    ');
                    $bigQuery->runQuery($queryJobConfig_2);


                    // final message

                    $result = array(
                        'code' => 200,
                        'message' => 'Archivo ' . $filename . ' cargado correctamente',
                        'data' => ''
                    );
                } else {
                    $result = array(
                        'code' => 201,
                        'message' => $job->info()["status"]["errorResult"]["message"],
                        'data' => ''
                    );
                }
            } else {
                $result = array(
                    'code' => 201,
                    'message' => 'No existe el archivo ' . $filename,
                    'data' => ''
                );
            }
        } else {
            $result = array(
                'code' => 201,
                'message' => 'No hay nombre de archivo.',
                'data' => ''
            );
        }
        header('Content-Type: application/json');
        echo $encode = json_encode($result, true);
        break;
    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}