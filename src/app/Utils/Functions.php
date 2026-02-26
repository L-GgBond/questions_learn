<?php
declare(strict_types=1);

if(!function_exists('convert_size')){
    /**
     * 将字节转化为 kb mb 等单位
     * @param $size
     * @return string
     */
    function convert_size($size):string {
        if ($size <= 0) {
            return '0 B';
        }

        $units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        // 强制转换为 int，避免 PHP 8.1+ 中 float 作为数组索引引发 Deprecated 警告
        $i = (int) floor(log($size, 1024));

        return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}