<?php

class File
{
    public $not_load_dir = [];
    public $not_load_file = [];
    public $export = '';
    public $new_path = '';

    public function __construct($config=array())
    {
        $this->not_load_dir = isset($config['not_load_dir'])?$config['not_load_dir']:[];
        $this->not_load_file = isset($config['not_load_file'])?$config['not_load_file']:[];
        $this->export = isset($config['export'])?$config['export']:__DIR__.'/export/';
        $this->new_path = isset($config['new_path'])?$config['new_path']:die('new_path不能为空');
    }

    /**
     * 遍历所有文件夹和文件
     * @param $dir
     * @param array $node
     * @return array
     */
    public function getAllDir($dir, $node=array()){
        if(is_file($dir)){
            $node[$dir] = $this->getFileInfo($dir);
            return $node;
        } elseif (!is_dir($dir)) {
            $node[$dir] = [
                'basename'=>$dir,
                'type'=>'error',
            ];
            return $node;
        }

        $handle = scandir($dir);
        foreach ($handle as $value){
            if($value != '.' && $value != '..'){
                if (is_dir($dir.'/'.$value) && in_array($value, $this->not_load_dir)) {
                    continue;
                }
                if (is_file($dir.'/'.$value) && in_array($value, $this->not_load_file)) {
                    continue;
                }
                if(is_file($dir.'/'.$value)){
                    $node[$value] = $this->getFileInfo(trim($dir, '/').'/'.$value);
                    continue;
                }
                $node[$value] = [
                    'basename'=>$value,
                    'type'=>'dir',
                    'node'=>$this->getAllDir(trim($dir, '/').'/'.$value, [])
                ];
            }
        }
        return $node;
    }

    /**
     * 获取文件信息
     * @param $file
     * @return array
     */
    public function getFileInfo($file){
        $data = [];
        $data['modify'] = filemtime($file);
        $data['type'] = 'file';
        $data['modify_t'] = date("Y-m-d H:i:s", $data['modify']);
        $data = array_merge($data, pathinfo($file));
        unset($file);
        return $data;
    }

    /**
     * 得出文件夹下的差异（新增和修改过的文件） 由 文件二 减 文件夹一
     * @param $node_array1
     * @param $node_array2
     * @param array $res
     * @return array
     */
    public function getDiff_One2Two($node_array1, $node_array2, $res = []){
        foreach ($node_array1 as $key1=>$value1) {
            //node1有而node2没有
            if (!isset($node_array2[$key1])) {
                $res[] = $value1;
            } else {
                //node1有, node2有, 比较不同
                $value2 = $node_array2[$key1];
                //文件修改过
                if ($value1['type'] == 'file' && $value2['type'] == 'file' && $value1['modify'] != $value2['modify']) {
                    $res[] = $value1;
                } else if ($value1['type'] == 'dir' && $value2['type'] == 'dir') {
                    //都是文件夹
                    $tmp = $this->getDiff_One2Two($value1['node'], $value2['node']);
                    if (!empty($tmp)) {
                        $res[] = [
                            'basename'=>$value1['basename'],
                            'type'=>'dir',
                            'node'=>$tmp,
                        ];
                    }
                } else if ($value2['type'] == 'error') {
                    $res[] = $value1;
                }
            }
        }
        return $res;
    }

    /**
     * 下载文件（需要先建立好存储的根文件夹）
     * @param $files
     * @param string $source_path
     * @param string $path
     */
    public function download($files, $source_path='', $path='') {
        $source_path = empty($source_path)?$this->new_path:$source_path;
        $mkpath = trim($this->export."/{$path}", '/');
        if (!is_dir($mkpath)){
            mkdir($mkpath);
        }
        foreach ($files as $value) {
            if ($value['type'] == 'file'){
                if (empty($path)) {
                    copy($source_path.'/'.$value['basename'], $this->export."/{$value['basename']}");
                } else {
                    copy($source_path."/{$path}/".$value['basename'], $this->export."/{$path}/{$value['basename']}");
                }
            } else if ($value['type'] == 'dir') {
                $this->download($value['node'], $source_path, trim($path."/{$value['basename']}", '/'));
            }
        }
    }

    /**
     * 简单查看文件
     * @param $result
     */
    public function view($result) {
        echo "新/修改的文件:<br>";
        $this->deal_result($result, '');
    }

    /**
     * 简单输出文件
     * @param $result
     * @param string $parent
     * @param int $num
     */
    public function deal_result($result, $parent='', $num=0){
        $num++;
        foreach ($result as $value) {
            if ($value['type'] == 'dir') {
                if (!empty($parent)) {
                    echo "<font color='#6495ed'>".$this->pathPre($num)."{$value['basename']}/</font><br>";
                } else {
                    echo "<font color='#6495ed'>"."{$value['basename']}/</font><br>";
                }
                $this->deal_result($value['node'], $value['basename'], $num);
            } else if ($value['type'] == 'file') {
                if (!empty($parent)) {
                    echo $this->pathPre($num)."{$value['basename']}<br>";
                } else {
                    echo "{$value['basename']}<br>";
                }
            }
        }
    }

    /**
     * 路径前缀，配合输出显示
     * @param $num
     * @return string
     */
    public function pathPre($num) {
        $string = "";
        for ($i=0;$i<$num;$i++){
            $string .= '&#12288;';
        }
        $string .= '|—';
        return $string;
    }
}