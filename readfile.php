<?php
/**
 * Created by PhpStorm.
 * User: hurace
 * Date: 2018/4/19 0019
 * Time: 15:55
 */

function readLines() {
    $fileHandler = fopen("/path/to/targetFile.log", "r");//第二个参数r即read，对文件 /path/to/targetFile.log 以只读的方式打开
    //这里为啥叫 $fileHandler呢？handler译作：句柄在 PHP 中通常是指 Resource （译作：资源类型）的操纵杆，玩过魂斗罗，超级玛丽等手柄游戏的同学都知道，手柄就是游戏的操作装置。句柄也类似，是操作系统对资源（这里指的是文件资源）暴露出来的可操作选项的控制器。
    try {
        while ($line = fgets($fileHandler)) {//fgets 是读取文件的一行，并使文件指针指向下一行
            yield $line;//yield 译作：产出，生成，可以这么理解：你一个生成器吧，好歹得产出个东西吧，跟 return 类似，它俩都是用来返回值的，但是 yield 是 generator 函数的搭档，能够 yeild （产出）多个值，有这个关键词之后 readLines 函数就变成了 generator 函数，接着才可以使用下面的 foreach 去遍历
        }
    } finally {
        fclose($fileHandler);//这里切记一定要关闭文件句柄，若不关闭的话，它会占用操作系统资源，导致资源泄露（常见的主要是 memory leak，又叫内存泄露，就是指内存长时间被占用着无法再短时间内再利用）
    }
}

foreach (readLines() as $n => $line) {
    //TODO 下面就可以愉快的对 2G 大日志文件可以逐行处理了
    doSomethingYouCan();
}