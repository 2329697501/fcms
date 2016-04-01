<?php
//header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
date_default_timezone_set("Asia/chongqing");
error_reporting(E_ALL);
header("Content-Type: text/html; charset=utf-8");

$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("config.json")), true);

/*---------------------------------------------------------------------------------------*/
global $di;
$di = new \Phalcon\DI\FactoryDefault();

$di->set( 'session', function(){
//	$session = new \Phalcon\Session\Adapter\Libmemcached( array(
//			'servers' => array(
//					array( 'host' => '127.0.0.1', 'port' => 11211, 'weight' => 1 )
//			),
//			'client' => array(
//					Memcached::OPT_HASH => Memcached::HASH_MD5,
//					Memcached::OPT_PREFIX_KEY => 'huaer.'
//			),
//			'lifetime' => 3600,
//			'prefix' => 'huaer_'
//	));
    $session = new \Phalcon\Session\Adapter\Memcache();
	session_set_cookie_params( 3600, '/', '.huaer.dev' );
	ini_set("session.cookie_httponly", 1);

	@$session->start();

	return $session;
});

$di->set( 'mongodb', function (){

    $mongo =  new \MongoClient( '192.168.1.126:27017' );

    return $mongo->selectDB( 'imgs' );

}, true );
    
    

//session判断防止未登陆用户上传文件
global $session;
global $di;
$session = $di->get( 'session' );
if( $session->getId() != $_GET[ 'sid' ] )
{
	echo '必须登陆后才能上传文件！';
	exit();
}

 $rootPath = $_SERVER['DOCUMENT_ROOT'];
 if( strpos($rootPath, 'public') )      
 {
     $rootPath = str_replace( 'public', '', $rootPath );
 }
 
//加载配置文件
 $editorConfig = include $rootPath . '/config/ueditor.php';
 
//设置保存目录

/*---------------------------------------------------------------------------------------*/

$action = $_GET['action'];
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
//         $result = include("action_list.php");
//         break;
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