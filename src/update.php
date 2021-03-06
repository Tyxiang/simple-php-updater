<?php
/* 
file_path: a/b/c.php
dir_path: a/b
file_name: c.php
dir_name: b
file_name_main_part: c
file_name_extn_part: c
*/

ini_set('display_errors', 'On');
date_default_timezone_set('Asia/Shanghai');
// main
$this_file_name_main_part = basename(__FILE__, '.php');
$this_file_name = $this_file_name_main_part . '.php';
$config_file_name = $this_file_name_main_part . '.json';
$log_file_name = $this_file_name_main_part . '.log';
//// config
$config_json = file_get_contents($config_file_name);
if ($config_json == false) {
    $msg = 'load config error!';
    save_log($msg, $log_file_name);
    echo '<p>';
    echo $msg;
    echo '</p>';
    exit();
}
$config = json_decode($config_json, true);

//// jobs
$jobs = $config['jobs'];
if (isset($config['protect'])) {
    $protects_jobs = $config['protect'];
} else {
    $protects_jobs = [];
}
foreach ($jobs as $key => $job) {
    echo '<p>';
    echo '---------- job ( ' . $key . ' ) ----------';
    echo '<p>';
    $msgs = do_job($job, $protects_jobs);
    foreach($msgs as $msg){
        save_log($msg, $log_file_name);
        echo $msg;
        echo '<br>';
    }
    echo '</p>';
    echo '</p>';
}
//// del temp
echo '<p>';
echo '----------- clear -----------';
foreach ($jobs as $job) {
    echo '<p>';
    $msgs = clear_temp($job);
    foreach($msgs as $msg){
        save_log($msg, $log_file_name);
        echo $msg;
        echo '<br>';
    }
    echo '</p>';
}
echo '</p>';

// func
//// app
function do_job($job, $protects) {
    $r = [];
    // job name
    $job_name = md5($job['download']);
    $download_file_name = $job_name . '.tmp'; 
    $unzip_dir_name = $job_name;
    $clear_path = $job['clear'];
    $source_path = $unzip_dir_name . '/' . $job['copy'];
    $dest_path = $job['to'];
    if (isset($job['protect'])) {
        $protects_job = $job['protect'];
    } else {
        $protects_job = [];
    }
    $protects = array_merge($protects, $protects_job);
    // clear dest
    // Clean should be in front, otherwise the downloaded content will be deleted when the dest is './'
    if ($clear_path != '') {
        if (file_exists($clear_path)) {
            if (is_dir($clear_path)) $msg = remove_dir($clear_path, $protects);
            if (is_file($clear_path)) $msg = remove_file($clear_path, $protects);
        } else {
            $msg = 'dest not exist!';
        }
        $r[] = 'clear "' . $job['clear'] . '" ---> ' . $msg;
    }
    // download
    if ($download_file_name != '') {
        if (file_exists($download_file_name)) {
            $msg = 'file already exist!';
        } else {
            $msg = download($job['download'], $download_file_name);
        }
        $r[] = 'download "' . $job['download'] . '" ---> ' . $msg;
    }
    // unzip
    if (file_exists($download_file_name)){
        $msg = unzip($download_file_name, $unzip_dir_name);
        $r[] = 'unzip ---> ' . $msg;
    }
    // copy
    if ($source_path != '' && $dest_path != '') {
        if (file_exists($source_path)) {
            if (is_dir($source_path)) $msg = copy_dir($source_path, $dest_path, $protects);
            if (is_file($source_path)) $msg = copy_file($source_path, $dest_path, $protects);
        }else{
            $msg = 'source not exist!';
        }
        $r[] = 'copy "' . $job['copy'] . '" to "' . $job['to'] . '" ---> ' . $msg;
    }
    // return
    return $r;
}

function clear_temp($job) {
    $r = [];
    $job_name = md5($job['download']);
    // del download file
    $download_file_name = $job_name . '.tmp'; 
    if (file_exists($download_file_name)) {
        $msg = remove_file($download_file_name, []);
        $r[] = 'remove download file ---> ' . $msg;
    }
    // del unzip dir
    $unzip_dir_name = $job_name;
    if (file_exists($unzip_dir_name)) {
        $msg = remove_dir($unzip_dir_name, []);
        $r[] = 'remove unzip dir ---> ' . $msg;
    }
    // return
    return $r;
}

//// core
function download($url, $dir) {
    $content = file_get_contents($url);
    if ($content === false) return 'file get contents error!';
    $r = file_put_contents($dir, $content);
    if ($r === false) return 'file put content error!';
    return 'ok.';
}

function unzip($path, $dir) {
    $zipper = new ZipArchive;
    $r = $zipper->open($path);
    if ($r !== true) return 'open zip file error!';
    $r = $zipper->extractTo($dir);
    if ($r !== true) return 'extract zip file error!';
    $r = $zipper->close();
    if ($r !== true) return 'close zip file error!';
    return 'ok.';
}

function remove_dir($path, $protects) {
    // protect
    foreach ($protects as $protect) {
        if (realpath($path) == realpath($protect)) return 'ok.';
    }
    // main
    if (!file_exists($path)) return 'dir not exist!';
    foreach (glob($path . '/*') as $item) {
        if (is_file($item)) {
            $r = remove_file($item, $protects);
            if ($r !== 'ok.') return 'remove file error!';
        } else {
            $r = remove_dir($item, $protects);
            if ($r !== 'ok.') return 'remove dir error!';
        }
    }
    @rmdir($path);
    return 'ok.';
}

function remove_file($path, $protects) {
    // protect
    foreach ($protects as $protect) {
        if (realpath($path) == realpath($protect)) return 'ok.';
    }
    // main
    if (!file_exists($path)) return 'file not exist';
    // $path_gbk = iconv('UTF-8', 'GBK', $path);
    // $r = unlink($path_gbk);
    $r = unlink($path);
    if ($r === false) return 'unlink error!';
    return 'ok.';
}


function copy_dir($source, $dest, $protects) {
    // protect
    foreach ($protects as $protect) {
        if (realpath($dest) == realpath($protect)) return 'ok.';
    }
    // main
    if (!file_exists($dest)) {
        $r = mkdir($dest, 0755, true);
        if ($r === false) return 'mkdir error!';
    }
    $handle = opendir($source);
    if ($handle == false) return 'opendir error!';
    while ($item = readdir($handle)) {
        if ($item == '.' || $item == '..') continue;
        $source_path = $source . '/' . $item;
        $dest_path = $dest . '/' . $item;
        if (is_file($source_path)) {
            $r = copy_file($source_path, $dest_path, $protects);
            if ($r !== 'ok.') return 'copy file error!';
        }
        if (is_dir($source_path)) {
            $r = copy_dir($source_path, $dest_path, $protects);
            if ($r !== 'ok.') return 'copy dir error!';
        }
    }
    closedir($handle);
    return 'ok.';
}

function copy_file($source, $dest, $protects) {
    // protect
    foreach ($protects as $protect) {
        if (realpath($dest) == realpath($protect)) return 'ok.';
    }
    // main
    $dest_dir_path = dirname($dest);
    if (!file_exists($dest_dir_path)) {
        $r = mkdir($dest_dir_path, 0755, true);
        if ($r === false) return 'mkdir error!';
    }
    // $source_gbk = iconv('UTF-8', 'GBK', $source);
    // $dest_gbk = iconv('UTF-8', 'GBK', $dest);
    // $r = copy($source_gbk, $dest_gbk);
    $r = copy($source, $dest);
    if ($r === false) return 'copy error!';
    return 'ok.';
}

function save_log($msg, $log_file_name) {
    $item = '[ ' . date('Y-m-d H:i:s') . ' ] ' . $msg . PHP_EOL;
    $r = file_put_contents($log_file_name, $item, FILE_APPEND);
    if ($r === false) return 'file put content error!';
    return 'ok.';
}
