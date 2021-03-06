<?php

namespace common\helpers;

use yii\helpers\BaseFileHelper;

/**
 * Class FileHelper
 * @package common\helpers
 * @author Rf <1458015476@qq.com>
 */
class FileHelper extends BaseFileHelper
{
    /**
     * 检测目录并循环创建目录
     *
     * @param $catalogue
     */
    public static function mkdirs($catalogue)
    {
        if (!file_exists($catalogue)) {
            self::mkdirs(dirname($catalogue));
            mkdir($catalogue, 0777);
        }

        return true;
    }

    /**
     * 写入日志
     *
     * @param $path
     * @param $content
     * @return bool|int
     */
    public static function writeLog($path, $content)
    {
        self::mkdirs(dirname($path));

        return file_put_contents($path, "\r\n" . $content, FILE_APPEND);
    }

    /**
     * 获取文件夹大小
     *
     * @param string $dir 根文件夹路径
     * @return int
     */
    public static function getDirSize($dir)
    {
        $handle = opendir($dir);
        $sizeResult = 0;
        while (false !== ($folderOrFile = readdir($handle))) {
            if ($folderOrFile != "." && $folderOrFile != "..") {
                if (is_dir("$dir/$folderOrFile")) {
                    $sizeResult += self::getDirSize("$dir/$folderOrFile");
                } else {
                    $sizeResult += filesize("$dir/$folderOrFile");
                }
            }
        }

        closedir($handle);

        return $sizeResult;
    }

    /**
     * 基于数组创建目录
     *
     * @param $files
     */
    public static function createDirOrFiles($files)
    {
        foreach ($files as $key => $value) {
            if (substr($value, -1) == '/') {
                mkdir($value);
            } else {
                file_put_contents($value, '');
            }
        }
    }

    /**
     * 软著文件生成
     *
     * @param $dir
     * @param $savePath
     */
    public static function getDirFileContent($dir, $savePath, $suffix = ['php'])
    {
        $handle = opendir($dir);
        while (false !== ($folderOrFile = readdir($handle))) {
            if ($folderOrFile != "." && $folderOrFile != "..") {
                if (is_dir("$dir/$folderOrFile")) {
                    self::getDirFileContent("$dir/$folderOrFile", $savePath, $suffix);
                } else {
                    $array = explode('.', $folderOrFile);
                    if (in_array(end($array), $suffix)) {
                        // 去除注释
                        $str = StringHelper::removeAnnotation(file_get_contents("$dir/$folderOrFile"));
                        // 追加写入
                        file_put_contents($savePath, $str, FILE_APPEND);
                    }
                }
            }
        }
    }
}