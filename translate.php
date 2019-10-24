<?php
ini_set('memory_limit', '1024M');
include "./utils.php";

// 将csv 转为 php
$input_csv_file_path = dirname(dirname(__FILE__)) . "/table/csv/";
$output_php_path = dirname(dirname(__FILE__)) . "/table/cache/";

$minify = true;

//-------------------------新增文件配置--------------------------------------
$php_file_list = array(
    "test" => array(
        "index" => "id"
    )
);
//----------------------------end---------------------------------------------
$file_list = getDirFiles($input_csv_file_path);
if (empty($file_list)) {
    printError("no valid config files at [$input_csv_file_path] path");
}

foreach ($file_list as $file) {
    $base_name = getFileBasename($file);
    $arr = getCsvFileContent($file);

    if (count($arr) <= 1) {
        printError("invalid format file:{$file}");
    }

    if (!isset($php_file_list[$base_name])) {
        continue;
    }
    $keys = $arr[1];
    $cfg = $php_file_list[$base_name];
    $key_index = false;
    if (isset($cfg['index']) && $cfg['index']) {
        $index_key = $cfg['index'];
        if (!in_array($index_key, $keys)) {
            printError("invalid file cfg:{$file}");
        }
        $key_index = arrayIndexByKey($index_key, $keys);
    }
    $ret_data = array();
    foreach ($arr as $k => $v) {
        if ($k <= 1) {
            continue;
        }
        $_v = [];
        foreach ($v as $key => $value) {
            $key_name = arrayKeyByIndex($key, $keys);
            if (is_numeric($value)) {
                $_v[$key_name] = floatval($value);
            } else {
                $lower_str = strtolower($value);
                if ($lower_str == 'false') {
                    $_v[$key_name] = 0;
                } else if ($lower_str == 'true') {
                    $_v[$key_name] = 1;
                } else {
                    $_v[$key_name] = $value;
                }
            }
        }

        if ($key_index !== false) {
            $ret_data[$v[$key_index]] = $_v;
        } else {
            $ret_data[] = $_v;
        }
    }
    $output_filename = $output_php_path . "$base_name" . ".php";
    $ret = phpCacheWrite($output_filename, $ret_data, "", $minify);
    if ($ret) {
        echo "generate $output_filename cache success \n";
    }
    unset($ret_data);
}