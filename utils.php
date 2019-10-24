<?php

function printError($err)
{
    echo "$err \r\n";
    exit();
}

function writeFile($file_name, $data)
{
    $fp = fopen($file_name, "w");
//    fwrite($fp, iconv('UTF-8', 'GB2312', $data));
    fwrite($fp, $data);
    fclose($fp);
    return true;
}

function readFileContent($file_name)
{
    if (!file_exists(($file_name))) {
        throw Exception("file $file_name not exists");
        return;
    }
    return file_get_contents($file_name);
}

function getCsvFileContent($file_name)
{
    $file_handler = fopen($file_name, 'r');
    $info_list = [];
    while ($data = fgetcsv($file_handler)) {
        $info_list[] = $data;
    }
    fclose($file_handler);
    return $info_list;
}

function getFileBasename($file_path, $ext = ".csv")
{
    $file_path = str_replace("\\", "/", $file_path);
    $arr = explode("/", $file_path);
    $len = count($arr);
    $name = $arr[$len - 1];
    return str_replace($ext, "", $name);
}

function arrayIndexByKey($key, $arr = [])
{
    $n = 0;
    foreach ($arr as $k => $v) {
        if ($k == $key) return $n;

        $n += 1;
    }
    return false;
}

function arrayKeyByIndex($index, $keys = [])
{
    foreach ($keys as $k => $v) {
        if ($index == $k) return $v;
    }
    throw new Exception("error: $index" . json_encode($keys));
}

function getDirFiles($dir)
{
    $files = [];
    if (!is_dir($dir) || !file_exists($dir)) {
        return $files;
    }

    $directory = new \RecursiveDirectoryIterator($dir);
    $iterator = new \RecursiveIteratorIterator($directory);
    foreach ($iterator as $info) {
        $file = $info->getFilename();
        if ($file == "." || $file == "..") {
            continue;
        }
        $files[] = $info->getPathname();
    }
    return $files;
}

// compress files all of dirs
function stripCommentAndWhitespace($path = '')
{
    if (empty($path)) {
        throw new Exception("please select file directory");
    }

    $path = str_replace("\\", "/", $path);

    if ($handle = opendir($path)) {
        while (false !== ($file_name = readdir($handle))) {
            if ($file_name != "." && $file_name != "..") {
                if (is_file($path . "/" . $file_name)) {
                    $suffix = pathinfo($path . "/" . $file_name, PATHINFO_EXTENSION);
                    if ($suffix == "php") {
                        $new_file_data = php_strip_whitespace($path . "/" . $file_name);
                        file_put_contents($path . "/" . $file_name, $new_file_data);
                    }
                }
                if (is_dir($path . "/" . $file_name)) {
                    stripCommentAndWhitespace($path . "/" . $file_name);
                }
            }
        }
        closedir($handle);
    }
}

// array to string
function phpArrayEval($array, $level = 0)
{
    $space = '';
    for ($i = 0; $i < $level; $i++) {
        $space .= "\t";
    }

    $evaluate = "array\n{$space}(\n";
    $comma = $space;
    foreach ($array as $key => $val) {
        $key = is_string($key) ? '\'' . addcslashes($key, '\'\\') . '\'' : $key;
        $val = is_string($val) ? '\'' . addcslashes($val, '\'\\') . '\'' : $val;

        if (is_array($val)) {
            $evaluate .= "{$comma}{$key} => " . phpArrayEval($val, $level + 1);
        } else {
            $evaluate .= "{$comma}{$key} => {$val}";
        }
        $comma = ",\n$space";
    }
    $evaluate .= "\n$space)";
    return $evaluate;
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function stripWhitespace($content)
{
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<ZBDOC\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "ZBDOC;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

// PHP cache文件生成
function phpCacheWrite($file_name, $values, $var = "", $minify = false)
{
    $content = !empty($var) ? "${$var}=" . phpArrayEval($values) : "return " . phpArrayEval($values);
    $cachetext = "<?php\r\n{$content};";
    if ($minify)
        $cachetext = stripWhitespace($cachetext);

    return writeFile($file_name, $cachetext);
}

