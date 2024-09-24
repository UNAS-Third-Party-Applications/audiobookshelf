<?php
include 'functions.php';

// 获取请求参数
$jsonData = file_get_contents("php://input");
// 解析JSON数据
$jsonObj = json_decode($jsonData);
// 现在可以使用$jsonObj访问传递的JSON数据中的属性或方法
// 获取token，通过token获取用户名
$token = $jsonObj->token;
if(empty($token)) {
  echo json_encode(array(
    'err' => 1,
    'msg' => 'Token is empty'
  ));
  return;
}
session_id($token);
// 强制禁止浏览器的隐式cookie中的sessionId
$_COOKIE = [ 'PHPSESSID' => '' ];
session_start([ // php7
    'cookie_lifetime' => 2000000000,
    'read_and_close'  => false,
]);
// 获取用户名
$userId = isset($_SESSION['uid']) && is_string($_SESSION['uid']) ? $_SESSION['uid'] : $_SESSION['username'];
if(!isset($userId)) {
  echo json_encode(array(
    'err' => 1,
    'msg' => 'User information not obtained'
  ));
  return;
}
// 获取要进行的操作
$action = $jsonObj->action;

if($action == "getConfig") {
  // 判断服务状态
  $enable = false;
  // 判断Audiobookshelf服务是否已经安装
  if(checkServiceExist("audiobookshelf")) {
    // Audiobookshelf服务已经安装，判断是否运行
    $enable = checkServiceStatus("audiobookshelf");
  }
  // 获取共享文件夹列表
  $shareFolders = getAllSharefolder();
  // 获取homes目录中apps的目录
  $homesAppsFolder = getHomesAppsDir();
  // 读取配置文件中的配置
  $configFile = '/unas/apps/audiobookshelf/config/config.json';
  if(file_exists($configFile)) {
    $jsonString = file_get_contents($configFile);
    $configData = json_decode($jsonString, true);
    $configData['enable'] = $enable;
    $configData['shareFolders'] = $shareFolders;
    $configData['homesAppsFolder'] = $homesAppsFolder;
    if(empty($configData['configDir'])) {
      $configData['configDir'] = $homesAppsFolder;
    }
    echo json_encode($configData);
  } else {
    echo json_encode(array(
      'enable' => $enable,
      'homesAppsFolder' => $homesAppsFolder,
      'shareFolders' => $shareFolders,
      'configDir' => $homesAppsFolder,
      'port' => 3333
    ));
  }
} if($action == "manage") {
  // 保存配置并启动或者停止服务
  // 是否启用audiobookshelf服务
  $enable = false;
  if (property_exists($jsonObj, "enable")) {
    $enable = $jsonObj->enable;
  }
  // audiobookshelf的配置文件目录
  if (property_exists($jsonObj, 'configDir')) {
    $configDir = $jsonObj->configDir;
  } else {
    // 配置目录未设置
    echo json_encode(array(
      'err' => 2,
      'msg' => 'No configuration directory set'
    ));
    return;
  }

  // 检测配置目录是否存在
  if (is_dir($configDir)) {
    $audiobookshelfConfigDir = $configDir."/audiobookshelf";
    if (!is_dir($audiobookshelfConfigDir)) {
      // 文件夹不存在，创建文件夹
      exec("sudo mkdir -p $audiobookshelfConfigDir");
      // 此处不判断是否创建成功，交由后续判断统一处理
    }
    if (is_dir($audiobookshelfConfigDir)) {
      // 设置www-data对audiobookshelf配置文件目录访问权限
      exec("sudo setfacl -d -m u:www-data:rwx $audiobookshelfConfigDir && sudo setfacl -m m:rwx $audiobookshelfConfigDir && sudo setfacl -R -m u:www-data:rwx $audiobookshelfConfigDir");
    } else {
      // audiobookshelf配置目录创建失败
      echo json_encode(array(
        'err' => 2,
        'msg' => 'Failed to create Configuration directory'
      ));
      return;
    }
  } else {
    // 配置目录不存在
    echo json_encode(array(
      'err' => 2,
      'msg' => 'Configuration directory is not exist'
    ));
    return;
  }
  // audiobookshelf的端口，默认3333
  $port = 3333;
  if (property_exists($jsonObj, 'port')) {
    $portData = $jsonObj->port;
    if(is_numeric($portData)) {
      $port = intval($portData);
    }
  }
  $configData = array(
    'configDir' => $configDir,
    'port' => $port
  );
  // 将配置换成JSON格式
  $configJson = json_encode($configData);
  // 配置文件
  $configFile = '/unas/apps/audiobookshelf/config/config.json';
  if(file_exists($configFile)) {
    // 如果配置文件存在，和修改文件权限和所有者
    exec("sudo chown www-data:www-data $configFile");
    exec("sudo chmod 644 $configFile");
  }
  // 将JSON数据写入文件
  $result = file_put_contents($configFile, $configJson);
  if($result == false) {
    // 配置写入文件失败
    echo json_encode(array(
      'err' => 1,
      'msg' => 'Failed to save configuration'
    ));
    return;
  }

  // audiobookshelf安装程序目录
  $sbinPath = "/unas/apps/audiobookshelf/sbin";
  // audiobookshelf的程序文件
  $appFile = $sbinPath."/audiobookshelf";
  // 修改audiobookshelf的权限和所有者
  exec("sudo chown www-data:www-data $appFile");
  exec("sudo chmod 755 $appFile");

  // 修改安装、卸载脚本的权限和所有者
  $installScript = $sbinPath."/install.sh";
  exec("sudo chown www-data:www-data $installScript");
  exec("sudo chmod 755 $installScript");

  $uninstallScript = $sbinPath."/uninstall.sh";
  exec("sudo chown www-data:www-data $uninstallScript");
  exec("sudo chmod 755 $uninstallScript");

  // 卸载audiobookshelf的命令
  $uninstallServiceCommand = "sudo $uninstallScript $sbinPath";
  if($enable) {
    // audiobookshelf的安装命令
    $installServiceCommand = "sudo $installScript $sbinPath $port $audiobookshelfConfigDir";
    // error_log("安装命令为：".$installServiceCommand);

    // 判断Audiobookshelf服务是否已经安装
    if(checkServiceExist("audiobookshelf")) {
      // Audiobookshelf服务已经安装，则执行卸载后再安装
      exec($uninstallServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
      exec($installServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
    } else {
      // Audiobookshelf服务未安装，则执行安装
      exec($installServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
      // error_log("服务安装，结果为：".$result);
    }
  } else {
    // 判断Audiobookshelf服务是否已经安装
    if(checkServiceExist("audiobookshelf")) {
      // Audiobookshelf服务已经安装，则执行卸载
      exec($uninstallServiceCommand, $output, $returnVar);
      // 输出Shell脚本的输出
      // error_log($output);
    }
  }
  echo json_encode(array(
    'err' => 0
  ));
} if($action == "checkport") {
  $port = $jsonObj->port;
  if(isset($port)) {
    if (is_numeric($port)) {
      if ($port >= 1 && $port <= 65535 ) {
        if (isPortOccupied($port)) {
          echo json_encode(array(
            'err' => 1,
            'msg' => 'Port has been used'
          ));
          return;
        }
        echo json_encode(array(
          'err' => 0
        ));
        return;
      }
    }
  }
  // 返回错误提示
  echo json_encode(array(
    'err' => 1,
    'msg' => 'Port should between 1 and 65535'
  ));
}
?>