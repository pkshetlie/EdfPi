<?php
require_once('config.php');
require_once('lib/TeleDatas.php');
$teledatas = new TeleDatas('teleinfo');

header('Content-Type: application/json');
$data = array(
    "success"=> true,
    "timestamp"=>"",
    "va"=>"",
);

$obj = $teledatas->getPuissanceByTimestamp($_POST['t']);
if($obj == null){
    $data['success'] = false;
}else{
    $data['timestamp'] = $obj->timestamp;
    $data['va'] = $obj->va;

}
echo json_encode($data);