<?php
namespace core\base;

class View{
    protected $variables = array();
    protected $_controller;
    protected $_action;

    function __construct($controller, $action){
        $this->_controller = strtolower($controller);
        $this->_action = strtolower($action);
    }

    //分配变量
    public function assign($name, $value){
        $this->variables[$name] = $value;
    }

    //渲染显示
    public function render(){
        extract($this->variables);
        $defaultHeader = APP_PATH . 'app/views/header.php';
        $defaultFooter = APP_PATH . 'app/views/footer.php';

        $controllerHeader = APP_PATH . 'app/views/' . $this->_controller . '/header.php';
        $controllerFooter = APP_PATH . 'app/views/' . $this->_controller . '/footer.php';
        $controllerLayout = APP_PATH . 'app/views/' . $this->_controller . '/' . $this->_action . '.php';

        //页头文件
        if (is_file($controllerHeader)){
            include ($controllerHeader);
        }else{
            include ($defaultHeader);
        }

        //判断视图文件是否存在
        if (is_file($controllerLayout)){
            include ($controllerLayout);
        }else{
            echo '<h1>视图文件不存在</h1>';
        }

        //页脚文件
        if (is_file($controllerFooter)){
            include ($controllerFooter);
        }else{
            include ($defaultFooter);
        }

    }
}
