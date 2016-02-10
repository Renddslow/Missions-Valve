<?php
ini_set('display_errors', '1');

    require_once('../vendor/autoload.php');
    require_once('FellowshipOne.php');
    require_once('valve.php');


    $settings = array(
      'key' => 'F1 API KEY',
      'secret' =>'F1 API SECRET',
      'username' => 'USER',
      'password' => 'PASSWORD',
      'baseUrl' => 'https://CHURCHNAME.fellowshiponeapi.com',
      'debug' => false,
    );

    $obj = new valve();

    $obj->apiKey = 'MANAGED MISSIONS API KEY';
    $obj->get_contrib();

    $f1 = new FellowshipOne($settings);
    if(($r = $f1->login2ndparty('USER','PASSWORD')) === false){
      die("Failed to login");
    } else {
      echo "Here we go!";
    }
    $obj->searchF1Households($f1);
    // echo $obj->getfundies($f1);
    // echo $obj->getModel($f1);
