<?php
$config = include 'config.php';
include 'File.php';

$File = new File($config);

$old_path = $File->getAllDir($config['old_path']);

$new_path = $File->getAllDir($config['new_path']);
$compare_res = $File->getDiff_One2Two($new_path, $old_path);
$File->download($compare_res);
$File->view($compare_res);