<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('max_execution_time', 480);
ini_set('display_errors', 1);
ini_set('memory_limit', '2048M');
date_default_timezone_set('America/Lima');
require '../../../vendor/autoload.php';
require_once '../../app/config/SimpleXLSX.php';

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\BigQuery\BigQueryClient;

$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'POST':

        $temp_file_name = "";
        $real_file_name = "";
        $final_file_name = "";


        // verify file upload

        if ($_FILES['file1']['error'] === 0) {
            $temp_file_name = $_FILES['file1']['tmp_name'];
            $real_file_name = $_FILES['file1']['name'];
            $exploded_file_name = explode('.', $real_file_name);
            $file_extension = strtolower(end($exploded_file_name));


            // verify file extension

            if ($file_extension === 'xlsx') {


                // parse xlsx

                $data = array();
                $xlsx = SimpleXLSX::parse($temp_file_name);


                // verify valid xlsx

                if ($xlsx) {
                    $rows = $xlsx->rows();


                    // get final field keys

                    $final_keys = array();
                    $keys = $rows[0];
                    for ($j = 0; $j < count($keys); $j++) {
                        $final_fields = array(
                            "PEDIDO",
                            "SOCIO",
                            "SEDE",
                            "TIPO_VENTA",
                            "CODIGO_VENTA",
                            "TIPO_DOCUMENTO",
                            "NUMERO_DOCUMENTO",
                            "FECHA_CREACION_PEDIDO",
                            "HORA_CREACION_PEDIDO",
                            "USUARIO_CREADOR",
                            "FECHA_PACTADA_CLIENTE",
                            "HORARIO_VISITA",
                            "ESTADO_DOCUMENTACION",
                            "MOTIVO_DOCUMENTACION",
                            "COMENTARIO",
                            "USUARIO",
                            "ROL",
                            "VEP",
                            "CAMPANA_VENTA",
                            "SEGUNDA_LINEA",
                            "LUGAR_ENTREGA",
                            "TIPO_DELIVERY",
                            "DEPARTAMENTO_ENTREGA",
                            "PROVINCIA_ENTREGA",
                            "DISTRITO_ENTREGA",
                            "FECHA_ENTREGA_REAL",
                            "NUMERO_PORTAR",
                            "ANEXO60",
                            "COURIER",
                            "NROCONTRATO",
                            "IMEI",
                            "MODELO",
                            "PLAN",
                            "INGRESO",
                            "FECHA_APROBACION",
                            "HORA_APROBACION",
                            "DIRECCION_ENTREGA",
                            "DIRECCION_ORIGINAL",
                            "OPERADOR",
                            "MODALIDAD",
                            "PUNTO_VENTA",
                            "CARGA_CSV",
                            "ARCHIVO_CSV",
                            "TERCERO_TIPODOCUMENTO",
                            "TERCERO_NUMERODOCUMENTO",
                            "TERCERO_NOMBRE",
                            "PUNTOVENTA",
                            "CATEGORIA",
                            "MODALIDAD_AUTOACTIVADO",
                            "DIRECCIONENTREGA",
                            "VISITAS",
                            "TELEFONO1",
                            "TELEFONO2",
                            "CONFIRMACION_INCONCERT",
                            "FECHA_CONFIRMACION_INCONCERT",
                            "FECHAONECLICK",
                            "FECHAPICKING",
                            "IDCITA",
                            "MOTORIZADO",
                            "ID_HUELLA",
                            "NOMBRECLIENTE",
                            "GUIA_REMISION",
                            "PAGO_LINK",
                            "CODIGO_TRANSACCION",
                            "ORDEN_AVANZADA_TDE",
                            "DETALLE",
                            "MODALIDAD_PAGO_NUMERO_PORTAR",
                            "TIPO_CITA",
                            "FECHA_ESTADO_MOTORIZADO",
                            "HORA_ESTADO_MOTORIZADO",
                            "DIA_DESPACHO",
                            "CUMPL_FECHA_PACTADA",
                            "SUB_AREA",
                            "MOTIVO",
                            "TIPO_PRODUCTO",
                            "CORTE_SE",
                            "TIENDA_PUNTO_DE_VENTA",
                            "SLA_TIPO_ENTREGA"
                        );
                        if (in_array($keys[$j], $final_fields)) {
                            $final_keys[] = $j;
                        }
                    }


                    // get data

                    for ($i = 0; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        $final_row = array();
                        for ($j = 0; $j < count($row); $j++) {
                            if (in_array($j, $final_keys)) {
                                $element = $row[$j];
                                $final_row[] = $element;
                            }
                        }


                        // skip first row and empty rows

                        if ($i != 0 && count($final_row) > 0) {
                            $data[] = array("data" => $final_row);
                        }
                    }


                    // verify data length

                    if (count($data) > 0) {


                        // get dates

                        $dates = array();
                        for ($i = 0; $i < count($data); $i++) {
                            $row = $data[$i]["data"];
                            $dates[] = DateTime::createFromFormat("d/m/Y", $row[7])->format('Y-m-d');
                        }
                        sort($dates);
                        $since = $dates[0];
                        $to = $dates[count($dates) - 1];


                        // save csv file

                        $table_name = "entregas_v2";
                        $final_file_name = $table_name . "_" . date("Y-m-d") . ".csv";
                        $out = fopen($final_file_name, 'w');
                        foreach ($data as $row) {
                            fputcsv($out, $row["data"]);
                        }
                        fclose($out);


                        // upload file to cloud storage

                        $storage = new StorageClient([
                            'projectId' => "entel-ecommerce",
                            'keyFilePath' => "../../../entel-ecommerce-5b11ddb572e4.json"
                        ]);
                        $bucket = $storage->bucket('entel-ecommerce-bucket');
                        $bucket->upload(fopen($final_file_name, 'r'));


                        // bigquery object

                        $bigQuery = new BigQueryClient([
                            'projectId' => "entel-ecommerce",
                            'keyFilePath' => "../../../entel-ecommerce-5b11ddb572e4.json"
                        ]);


                        // delete data

                        $queryJobConfig = $bigQuery->query('
                            DELETE
                            FROM
                              `entel-ecommerce.entel_ds_ecommerce.' . $table_name . '`
                            WHERE
                              PARSE_DATE("%d/%m/%Y",FECHA_CREACION_PEDIDO) BETWEEN "' . $since . '" AND "' . $to . '"
                        ');
                        $bigQuery->runQuery($queryJobConfig);


                        // load data from cloud storage to bigquery

                        $dataset = $bigQuery->dataset("entel_ds_ecommerce");
                        $table = $dataset->table($table_name);
                        $object = $bucket->object($final_file_name);
                        $loadJobConfig = $table->loadFromStorage($object);
                        $loadJobConfig->allowQuotedNewlines(true);
                        $job = $table->runJob($loadJobConfig);


                        // verify job status

                        if (count($job->info()["status"]) == 1) {


                            // insert last update

                            $queryJobConfig_2 = $bigQuery->query('
                                INSERT INTO `entel-ecommerce.entel_ds_ecommerce.ultimas_actualizaciones` (
                                    tabla,
                                    ultima_actualizacion
                                )
                                VALUES (
                                    "' . $table_name . '",
                                    "' . date("Y-m-d H:i:s") . '"
                                )
                            ');
                            $bigQuery->runQuery($queryJobConfig_2);


                            // final message

                            $result = array(
                                'code' => 200,
                                'message' => "OK: Se insertaron " . $job->info()["statistics"]["load"]["outputRows"] . " registros.",
                                'data' => ''
                            );
                        } else {
                            $result = array(
                                'code' => 201,
                                'message' => 'ERROR: ' . $job->info()["status"]["errors"][0]["message"],
                                'data' => ''
                            );
                        }


                        // delete file

                        if (file_exists($final_file_name)) {
                            unlink($final_file_name);
                        }
                    } else {
                        $result = array(
                            'code' => 201,
                            'message' => 'ERROR: El archivo no contiene filas.',
                            'data' => ''
                        );
                    }
                } else {
                    $result = array(
                        'code' => 201,
                        'message' => 'ERROR: ' . SimpleXLSX::parse_error(),
                        'data' => ''
                    );
                }
            } else {
                $result = array(
                    'code' => 201,
                    'message' => 'ERROR: Extensión de archivo no es XLSX.',
                    'data' => ''
                );
            }
        } else {
            $result = array(
                'code' => 201,
                'message' => 'ERROR: Archivo cargado incorrectamente.',
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