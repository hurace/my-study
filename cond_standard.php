<?php // 开头必须使用 <?php
//开头块状注释
/**
 * 此文件是用来做什么的
 * 作者 hurace
 * 日期
 */

require_once('conf/config.inc.php'); // require_once必须有括弧，并且左括弧前面没有空格

class CodingStandard { // 大括号前面加空格，类名开头字母大写，多个字母首字母大写
    public $attribute;// 属性注释直接注释在后方
    private $_attribute; //私有变量加下划线

    // 数组格式
    public $color = array(
        '1' => 'red', // 用tab缩进一次
        '2' => 'blue',
        '3' => 'yellow',
        '4' => array(
            '1' => 'green', // 在前面的数组对齐列之后再tab缩进一次
            '2' => 'gray'
        ) // 数组的结尾与声明的变量最前面对齐
    ); // 数组的结尾与数组变量声明的地方对齐

    public $number = array(1, 2, 3, 4); // 对于简单数组，可以放一行

    /**
     * 函数作用
     * @param $i 参数说明
     * @param $list 参数说明
     * @return int|null
     */
    public function foo($i, $list) { // 1.function名后面的(前面没有空格 2.多个参数，如果有逗号，那么逗号后面要有空格
        for ($j = 0; $j < $i; $j++) { // for后面加空格
            echo "This is no.{$j}, content is {$list[$j]}"; // echo语句不加括号。

            // echo语句里面用单引号还是双引号，根据实际情况定
            echo '&lttable border="0" cellspacing="5" cellpadding="5"&gt';
        }
        if ($i > 0) { // 1.if后面加空格 2.操作符前后都要有空格
            return $i % 2; // 操作符前后是有空格的
        } else { // else前后也要有空格
            return null;
        }

        if ($j == $i) return 1; // if里面只有一句语句且较短的情况，建议写成一行，如果要拆成多行，则前后建议加上括号。

        $count = count($_SERVER); // 在外面写赋值
        if ($count > 10) echo 'pass'; // if里面只做布尔判断，不要写赋值语句

        //TODO 这里是待做事项
    }

    /**
     * 函数作用
     * @return array
     */
    public static function testFunction() { // 静态非静态方法命名都遵守驼峰原则
        return array();
    }

    /**
     * 函数作用
     */
    private function _privateFunction() { //私有函数，函数名以下划线开头
    }
}

$code_stand = new CodingStandard(); // new一个对象，后面必须加括弧
$code_stand->foo(10, $code_stand->color); // 函数后面的括弧不要有空格，函数里面超过一个参数，逗号后面就要有空格
CodingStandard::testFunction(); // 静态代码的调用方式唯一，仅限双冒号调用方式
