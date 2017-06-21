<?php

//定义系统常量
define("ROOT", __DIR__);

require ROOT . "/core/core.php";

require_once ROOT . "/vendor/autoload.php";

core::app()->run();

?>
