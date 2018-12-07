<?php
namespace core;
//框架根目录
defined('CORE_PATH') or define('CORE_PATH', __DIR__);

class Core{
    //配置内容
    protected $config = [];

    public function __construct($config){
        $this->config = $config;
    }
    //运行程序
    public function run(){
        spl_autoload_register(array($this, 'loadClass'));
        $this->setReporting();
        $this->removeMagicQuotes();
        $this->unregisterGlobals();
        $this->setDbConfig();
        $this->route();
    }
    // 路由处理
    public function route(){
        $controllerName = $this->config['defaultController'];
        $actionName = $this->config['defaultAction'];
        $param = array();

        //yoursite.com/controllerName/actionName/queryString
        //$_SERVER['REQUEST_URI'] 获取到的 是/controllerName/actionName/queryString
        $url = $_SERVER['REQUEST_URI'];

        //删除？之后的内容
        $position = strpos($url, '?');
        $url = $position === false ? $url :substr($url, 0, $position);
        //删除前后的/
        $url = trim($url, '/');

        if ($url){
            //使用/分割字符串 并保存在数组中
            $urlArray = explode('/', $url);
            //删除空的数组元素
            $urlArray = array_filter($urlArray);
            //获取控制器名
            $controllerName = ucfirst($urlArray[0]);
            //获取动作名
            array_shift($urlArray);
            $actionName = $urlArray ? $urlArray[0] : $actionName;

            //获取url参数
            array_shift($urlArray);
            $param = $urlArray ? $urlArray :array();

        }

        //判断控制器和操作是否存在
        $controller = 'app\\controllers\\' . $controllerName . 'Controller';
        if (!class_exists($controller)){
            exit($controller . '控制器不存在');
        }

        if (!method_exists($controller, $actionName)){
            exit($actionName . '方法不存在');
        }

        $dispatch = new $controller($controllerName, $actionName);

        //等同于 $dispatch->$actionName($param)
        call_user_func_array(array($dispatch, $actionName), $param);
    }


    //检测开发环境
    public function setReporting(){
        if (APP_DEBUG === true){
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }else{
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set('log_errors', 'On');
        }
    }

    //检测敏感字符并删除
    public function removeMagicQuotes(){
        if (get_magic_quotes_gpc()){
            $_GET = isset($_GET) ? $this->stripSlashesDeep($_GET ) : '';
            $_POST = isset($_POST) ? $this->stripSlashesDeep($_POST ) : '';
            $_COOKIE = isset($_COOKIE) ? $this->stripSlashesDeep($_COOKIE) : '';
            $_SESSION = isset($_SESSION) ? $this->stripSlashesDeep($_SESSION) : '';
        }
    }
    //删除敏感字符
    public function stripSlashesDeep($value){
        $value = is_array($value) ? array_map(array($this, 'stripSlashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    //检测自定义全局变量并移除 因为register_globals已经被弃用 如果被弃用的指令被设置为On那么局部变量也将在全局作用域中可用
    public function unregisterGlobals(){
        if (ini_get('register_globals')){
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach ($array as $value) {
                foreach ($GLOBALS[$value] as $key => $var) {
                    if ($var === $GLOBALS[$key]) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    //自动加载类
    public function loadClass($className){
        $classMap = $this->classMap();

        if (isset($classMap[$className])){
            //包含内核文件
            $file = $classMap[$className];
        }elseif (strpos($className, '\\') !== false){
            //包含应用（application目录）文件
            $file = APP_PATH . str_replace('\\', '/', $className) . '.php';
            if (!is_file($file)){
                return;
            }
        }else{
            return;
        }

        include $file;

    }

    //内核文件命名空间映射关系
    protected function classMap(){
        return [
            'core\base\Controller' => CORE_PATH . '/base/Controller.php',
            'core\base\Model' => CORE_PATH . '/base/Model.php',
            'core\base\View' => CORE_PATH . '/base/View.php',
            'core\db\Db' => CORE_PATH . '/db/Db.php',
            'core\db\Sql' => CORE_PATH . '/db/Sql.php',
        ];
    }

    //配置数据库信息
    public function setDbConfig(){
        if ($this->config['db']){
            define('DB_HOST', $this->config['db']['host']);
            define('DB_NAME', $this->config['db']['dbname']);
            define('DB_USER', $this->config['db']['username']);
            define('DB_PASS', $this->config['db']['password']);
        }
    }
}
