<?php
use App\Helpers\Tools;
use App\Helpers\GoogleAuthenticator;
$ga = new GoogleAuthenticator;
// $secret =  $ga->createSecret();

// echo $secret, PHP_EOL;
//$secret="dasdaddas";
// echo Tools::encrypt($secret), PHP_EOL;
$pass = "Facai2023";
echo Tools::getHashPassword($pass).PHP_EOL;
$pass = "naima2023";
echo Tools::getHashPassword($pass).PHP_EOL;

exit;
$pass = "Hulala123";
echo Tools::getHashPassword($pass).PHP_EOL;


exit;
$checkpass = "Tg10010";
echo Tools::getHashPassword($checkpass);exit;
// echo Tools::decrypt($secret), PHP_EOL;

$name = 'hofa';
$secret = 'HTPSTSZG3HVW52S2';
// echo $ga->getQRCodeGoogleUrl($name, $secret, $title = null, $params = array()), PHP_EOL;
$oneCode = '872628';
$checkResult = $ga->verifyCode($secret, $oneCode, 2);
if ($checkResult) {
    echo 'OK', PHP_EOL;
} else {
    echo 'Fail', PHP_EOL;
}
 