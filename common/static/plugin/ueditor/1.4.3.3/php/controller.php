<?php
//header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
date_default_timezone_set('PRC');  
error_reporting(E_ERROR);
header("Content-Type: text/html; charset=utf-8");
session_start();

$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("config.json")), true);
$action = $_GET['action'];

//YzmCMS 新增 Start
define('IN_YZMPHP', true); 
define('IN_YZMCMS', true); 

$document_root = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']), '/');
$web_path = str_replace('\\','/',dirname(dirname(__FILE__)));  
if(strpos($web_path, $document_root) !== false){
	$web_path = str_replace($document_root,'', $web_path);
	$web_path = str_replace('/common/static/plugin/ueditor/1.4.3.3','',$web_path).'/';
}else{
	//换一种方式获取安装目录
	$web_path = str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_FILENAME']))); 
	if(strpos($web_path, $document_root) !== false){
		$web_path = str_replace($document_root,'', $web_path);
		$web_path = str_replace('/common/static/plugin/ueditor/1.4.3.3','',$web_path).'/';
	}else{
		//如果还是获取不到安装目录，就设置安装目录为根目录（/）
		$web_path = '/';
	}
}
define('YZMPHP_PATH', $document_root.$web_path);

class yzm_base {
        
    /**
     * 加载系统类方法
     * @param string $classname 类名
     * @param string $path 扩展地址
     * @param intger $initialize 是否初始化
     * @return object or true
     */
    public static function load_sys_class($classname, $path = '', $initialize = 1) {
        static $classes = array();
        if (empty($path)) $path = YZMPHP_PATH.'yzmphp'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'class';

        $key = md5($path.$classname);
        if (isset($classes[$key])) {
            return !empty($classes[$key]) ? $classes[$key] : true;
        }
        if (!is_file($path.DIRECTORY_SEPARATOR.$classname.'.class.php')) {
            exit($path.DIRECTORY_SEPARATOR.$classname.'.class.php'.' FILE NOT Existent!');
        }
        
        include $path.DIRECTORY_SEPARATOR.$classname.'.class.php'; 
        if ($initialize) {
            $classes[$key] = new $classname;
        } else {
            $classes[$key] = true;
        }
        return $classes[$key];
    }


    /**
     * 加载系统的函数库
     * @param string $func 函数库名
     */
    public static function load_sys_func($func) {
        if (is_file(YZMPHP_PATH.'yzmphp'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'function'.DIRECTORY_SEPARATOR.$func.'.func.php')) {
            include YZMPHP_PATH.'yzmphp'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'function'.DIRECTORY_SEPARATOR.$func.'.func.php';
        }
    }

}

yzm_base::load_sys_class('debug', '', 0);
yzm_base::load_sys_class('image', '', 0);
yzm_base::load_sys_func('global');

$web_upload = $web_path.'uploads';
$CONFIG['imagePathFormat'] = $web_upload.$CONFIG['imagePathFormat'];
$CONFIG['scrawlPathFormat'] = $web_upload.$CONFIG['scrawlPathFormat'];
$CONFIG['snapscreenPathFormat'] = $web_upload.$CONFIG['snapscreenPathFormat'];
$CONFIG['catcherPathFormat'] = $web_upload.$CONFIG['catcherPathFormat'];
$CONFIG['videoPathFormat'] = $web_upload.$CONFIG['videoPathFormat'];
$CONFIG['filePathFormat'] = $web_upload.$CONFIG['filePathFormat'];
$CONFIG['imageManagerListPath'] = $web_upload.$CONFIG['imageManagerListPath'];
$CONFIG['fileManagerListPath'] = $web_upload.$CONFIG['fileManagerListPath'];

//YzmCMS 新增 End

switch ($action) {
    case 'config':
        $result =  json_encode($CONFIG);
        break;

    /* 上传图片 */
    case 'uploadimage':
    /* 上传涂鸦 */
    case 'uploadscrawl':
    /* 上传视频 */
    case 'uploadvideo':
    /* 上传文件 */
    case 'uploadfile':
        $result = include("action_upload.php");
        break;

    /* 列出图片 */
    case 'listimage':
        $result = include("action_list.php");
        break;
    /* 列出文件 */
    case 'listfile':
        $result = include("action_list.php");
        break;

    /* 抓取远程文件 */
    case 'catchimage':
        $result = include("action_crawler.php");
        break;

    default:
        $result = json_encode(array(
            'state'=> '请求地址出错'
        ));
        break;
}

/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state'=> 'callback参数不合法'
        ));
    }
} else {
    echo $result;
}