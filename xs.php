<?php
        require_once('/usr/local/xunsearch/sdk/php/lib/XS.php');
        try {
            $xs = new XS('demo'); // demo 为项目名称，配置文件是：$sdk/app/demo.ini
            $xs_index = $xs->index;
            $xs_search = $xs->search;
            $res = $xs_search->search('项目');
            var_export($res);
	    $count = $xs_search->getLastCount();
            var_export($count);
            // ... 此外为其它 XSIndex/XSSearch 的相关功能代码
        } catch (XSException $e) {
            echo $e . PHP_EOL . $e->getTraceAsString() . PHP_EOL; // 发生异常，输出描述
        }
