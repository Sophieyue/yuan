<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class core
{
    public $params = [];

    private function runController()
    {
        $r = $_GET["r"];
        isset($r) and $route = explode("/", $r);

        $controller = isset($route[0]) ? $route[0] : "default";
        $controller .= "Controller";

        $function = isset($route[1]) ? $route[1] : "index";

        isset($route) and $params = array_slice($route, 2, count($route));

        if (class_exists($controller)) {
            //run controller
            $controllerObj = new $controller();
            $controllerObj->$function();
        } else {
            echo "不存在控制器{$controller} !";
            exit();
        }

        return true;
    }

    public static function autoload()
    {
        //加载library
        if (file_exists(ROOT . "/libraries")) {
            $handle = opendir(ROOT . "/libraries");
            while (($fileName = readDir($handle)) !== false) {
                if (strpos($fileName, "php") == strlen($fileName)-3) {
                    require_once ROOT . "/libraries/" . $fileName;
                }
            }
        }

        //加载hepler
        if (file_exists(ROOT . "/helpers")) {
            $handle = opendir(ROOT . "/helpers");
            while (($fileName = readDir($handle)) !== false) {
                if (strpos($fileName, "php") == strlen($fileName)-3 && strpos($fileName, "helper") !== false) {
                    require_once ROOT . "/helpers/" . $fileName;
                }
            }
        }

        //加载controller
        if (file_exists(ROOT . "/controllers")) {
            $handle = opendir(ROOT . "/controllers");
            while (($fileName = readDir($handle)) !== false) {
                if (strpos($fileName, "php") == strlen($fileName)-3 && strpos($fileName, "Controller") !== false) {
                    require_once ROOT . "/controllers/" . $fileName;
                }
            }
        }
    }

    private function init() {}

    public static function app()
    {
        $self = new self();
        $self->params = require ROOT . "/config/config.php";

        return $self;
    }

    public function run()
    {
        $this->runController();
    }

    public function logging($errLevel = "DEBUG", $errTitle = "", $errInfo = [])
    {
        $log = new Logger("system");
        //$log->pushHandler(new StreamHandler(ROOT . "/logs/core.log", Logger::WARNING));
        $log->pushHandler(new StreamHandler(ROOT . "/logs/core.log", Logger::DEBUG));

        if ($errLevel == "DEBUG") {
            $log->debug($errTitle, $errInfo);
        } else {
            $log->debug("暂时只提供debug级别的log, >_<");
        }
        //$log->error('Bar');
    }
}


spl_autoload_register(["core", "autoload"]);

