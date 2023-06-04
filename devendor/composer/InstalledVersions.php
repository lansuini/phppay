<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => '1.0.0+no-version-set',
    'version' => '1.0.0.0',
    'aliases' => 
    array (
    ),
    'reference' => NULL,
    'name' => 'codecourse/slender',
  ),
  'versions' => 
  array (
    'awurth/slim-validation' => 
    array (
      'pretty_version' => 'v3.4.0',
      'version' => '3.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c6fe3414d6c43513c06ecce0f6353087cf7a962f',
    ),
    'cache/adapter-common' => 
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '6b87c5cbdf03be42437b595dbe5de8e97cd1d497',
    ),
    'cache/filesystem-adapter' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.1.x-dev',
      ),
      'reference' => '1501ca71502f45114844824209e6a41d87afb221',
    ),
    'cache/tag-interop' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.1.x-dev',
      ),
      'reference' => 'dcf84ad3a590248a9c4d2ee5b1d99d87956800e7',
    ),
    'cakephp/cache' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '0e95a17b118d900f362de07bff902fa147e51fb2',
    ),
    'cakephp/collection' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '5667e755cfafe4b186f5f2a54121cf44cb86159e',
    ),
    'cakephp/core' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '716300a55ac86b7456e52258d3f50545545d2d6b',
    ),
    'cakephp/database' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'e16dae97cfe00b1de9c210bd49f71ad5c2de199a',
    ),
    'cakephp/datasource' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'accbe0c85552f384e587d73abdba1618c604b54d',
    ),
    'cakephp/log' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'cf10bdcce4e78430a780edb2cc5b4b2b4719a65d',
    ),
    'cakephp/utility' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '51b0af31af3239f6141006bbd7cbc7b16aba40d6',
    ),
    'codecourse/slender' => 
    array (
      'pretty_version' => '1.0.0+no-version-set',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'doctrine/inflector' => 
    array (
      'pretty_version' => '1.4.x-dev',
      'version' => '1.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '4bd5c1cdfcd00e9e2d8c484f79150f67e5d355d9',
    ),
    'facebook/webdriver' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'guzzlehttp/guzzle' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '7.4.x-dev',
      ),
      'reference' => '868b3571a039f0ebc11ac8f344f4080babe2cb94',
    ),
    'guzzlehttp/promises' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.5.x-dev',
      ),
      'reference' => 'fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
    ),
    'guzzlehttp/psr7' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '2.1.x-dev',
      ),
      'reference' => 'c55d23ab371b472342bee943a8bdb9c3cf963a18',
    ),
    'hashids/hashids' => 
    array (
      'pretty_version' => '3.0.0',
      'version' => '3.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b6c61142bfe36d43740a5419d11c351dddac0458',
    ),
    'illuminate/container' => 
    array (
      'pretty_version' => '5.8.x-dev',
      'version' => '5.8.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'b42e5ef939144b77f78130918da0ce2d9ee16574',
    ),
    'illuminate/contracts' => 
    array (
      'pretty_version' => '5.8.x-dev',
      'version' => '5.8.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '00fc6afee788fa07c311b0650ad276585f8aef96',
    ),
    'illuminate/database' => 
    array (
      'pretty_version' => '5.8.x-dev',
      'version' => '5.8.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'ac9ae2d82b8a6137400f17b3eea258be3518daa9',
    ),
    'illuminate/support' => 
    array (
      'pretty_version' => '5.8.x-dev',
      'version' => '5.8.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'df4af6a32908f1d89d74348624b57e3233eea247',
    ),
    'jaeger/g-http' => 
    array (
      'pretty_version' => 'V1.7.2',
      'version' => '1.7.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '82585ddd5e2c6651e37ab1d8166efcdbb6b293d4',
    ),
    'jaeger/phpquery-single' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fb80b4a0e5a337438d26e061ec3fb725c9f2a116',
    ),
    'jaeger/querylist' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => '894fb4344e100f0000f0b2c8764de970699f86bf',
    ),
    'jaeger/querylist-rule-google' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => '2a84dbf459fd0ebf7d67bdf7dc66e8a85360d7f5',
    ),
    'league/flysystem' => 
    array (
      'pretty_version' => '1.x-dev',
      'version' => '1.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '3ca8f158ee21efa4bbd4f74bea5730c9b9261e2d',
    ),
    'league/mime-type-detection' => 
    array (
      'pretty_version' => '1.9.0',
      'version' => '1.9.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'aa70e813a6ad3d1558fc927863d47309b4c23e69',
    ),
    'monolog/monolog' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '2.x-dev',
      ),
      'reference' => 'fb2c324c17941ffe805aa7c953895af96840d0c9',
    ),
    'nesbot/carbon' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '2.x-dev',
      ),
      'reference' => 'd37c3dfc52e5b8d2e3612f0740604d8c10d92d48',
    ),
    'nikic/fast-route' => 
    array (
      'pretty_version' => 'v1.x-dev',
      'version' => '1.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '4012884e0b916e1bd895a5061d4abc3c99e283a4',
    ),
    'parsecsv/php-parsecsv' => 
    array (
      'pretty_version' => '1.3.2',
      'version' => '1.3.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '2d6236cae09133e0533d34ed45ba1e1ecafffebb',
    ),
    'php-webdriver/webdriver' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => 'c16f8ee103da236d93d9bed406de49b424c064c6',
    ),
    'phpqrcode/phpqrcode' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => '24094696f394d0579616b7614321958f6d53639c',
    ),
    'pimple/pimple' => 
    array (
      'pretty_version' => 'v3.5.0',
      'version' => '3.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a94b3a4db7fb774b3d78dad2315ddc07629e1bed',
    ),
    'predis/predis' => 
    array (
      'pretty_version' => 'v1.1.x-dev',
      'version' => '1.1.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'b3cd02e2ba1c9f9922a301979ab3c8592e4bc17d',
    ),
    'psr/cache' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd11b50ad223250cf17b86e38383413f5a6764bf8',
    ),
    'psr/cache-implementation' => 
    array (
      'provided' => 
      array (
        0 => '^1.0',
        1 => '1.0',
      ),
    ),
    'psr/container' => 
    array (
      'pretty_version' => '1.1.x-dev',
      'version' => '1.1.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '8622567409010282b7aeebe4bb841fe98b58dcaf',
    ),
    'psr/http-client' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.0.x-dev',
      ),
      'reference' => '22b2ef5687f43679481615605d7a15c557ce85b1',
    ),
    'psr/http-client-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-factory' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.0.x-dev',
      ),
      'reference' => '36fa03d50ff82abcae81860bdaf4ed9a1510c7cd',
    ),
    'psr/http-factory-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-message' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
        0 => '1.0.x-dev',
      ),
      'reference' => 'efd67d1dc14a7ef4fc4e518e7dee91c271d524e4',
    ),
    'psr/http-message-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/log' => 
    array (
      'pretty_version' => '1.1.4',
      'version' => '1.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
    ),
    'psr/log-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0.0 || 2.0.0 || 3.0.0',
        1 => '1.0|2.0',
      ),
    ),
    'psr/simple-cache' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
    ),
    'psr/simple-cache-implementation' => 
    array (
      'provided' => 
      array (
        0 => '^1.0',
        1 => '1.0',
      ),
    ),
    'ralouphie/getallheaders' => 
    array (
      'pretty_version' => '3.0.3',
      'version' => '3.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '120b605dfeb996808c31b6477290a714d356e822',
    ),
    'respect/validation' => 
    array (
      'pretty_version' => '1.1.x-dev',
      'version' => '1.1.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '45d109fc830644fecc1145200d6351ce4f2769d0',
    ),
    'rmccue/requests' => 
    array (
      'pretty_version' => 'v1.8.1',
      'version' => '1.8.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '82e6936366eac3af4d836c18b9d8c31028fe4cd5',
    ),
    'robmorgan/phinx' => 
    array (
      'pretty_version' => '0.10.8',
      'version' => '0.10.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '1960e93169707096fdfde04904a204970077f4be',
    ),
    'slim/slim' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'ce3cb65a06325fc9fe3d0223f2ae23113a767304',
    ),
    'slim/twig-view' => 
    array (
      'pretty_version' => '2.x-dev',
      'version' => '2.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '47bd5cc1cbbdf5196d0873ece0ee97c6c7b352e9',
    ),
    'symfony/cache' => 
    array (
      'pretty_version' => '4.3.x-dev',
      'version' => '4.3.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '8794ccf68ac341fc19311919d2287f7557bfccba',
    ),
    'symfony/cache-contracts' => 
    array (
      'pretty_version' => '1.1.x-dev',
      'version' => '1.1.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '41c956506500bea5502022f6be81da96fb9c7626',
    ),
    'symfony/cache-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'symfony/config' => 
    array (
      'pretty_version' => '4.4.x-dev',
      'version' => '4.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'e99b65a18faa34fde57078095c39a1bc91a22492',
    ),
    'symfony/console' => 
    array (
      'pretty_version' => '4.4.x-dev',
      'version' => '4.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '329b3a75cc6b16d435ba1b1a41df54a53382a3f0',
    ),
    'symfony/deprecation-contracts' => 
    array (
      'pretty_version' => '2.5.x-dev',
      'version' => '2.5.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '6f981ee24cf69ee7ce9736146d1c57c2780598a8',
    ),
    'symfony/filesystem' => 
    array (
      'pretty_version' => '5.4.x-dev',
      'version' => '5.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '731f917dc31edcffec2c6a777f3698c33bea8f01',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '1.23.x-dev',
      ),
      'reference' => '30885182c981ab175d4d034db0f6f469898070ab',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '1.23.x-dev',
      ),
      'reference' => '11b9acb5e8619aef6455735debf77dde8825795c',
    ),
    'symfony/polyfill-php73' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '1.23.x-dev',
      ),
      'reference' => 'cc5db0e22b3cb4111010e48785a97f670b350ca5',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '1.23.x-dev',
      ),
      'reference' => '57b712b08eddb97c762a8caa32c84e037892d2e9',
    ),
    'symfony/polyfill-php81' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '1.23.x-dev',
      ),
      'reference' => '5de4ba2d41b15f9bd0e19b2ab9674135813ec98f',
    ),
    'symfony/process' => 
    array (
      'pretty_version' => '5.4.x-dev',
      'version' => '5.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'e8f02d0795d3852e2e0169dc7660786e9de30859',
    ),
    'symfony/service-contracts' => 
    array (
      'pretty_version' => '1.1.x-dev',
      'version' => '1.1.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '633df678bec3452e04a7b0337c9bcfe7354124b3',
    ),
    'symfony/translation' => 
    array (
      'pretty_version' => '5.3.x-dev',
      'version' => '5.3.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '17a965c8f3b1b348cf15d903ac53942984561f8a',
    ),
    'symfony/translation-contracts' => 
    array (
      'pretty_version' => '2.5.x-dev',
      'version' => '2.5.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'd28150f0f44ce854e942b671fc2620a98aae1b1e',
    ),
    'symfony/translation-implementation' => 
    array (
      'provided' => 
      array (
        0 => '2.3',
      ),
    ),
    'symfony/var-dumper' => 
    array (
      'pretty_version' => '3.4.x-dev',
      'version' => '3.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '0719f6cf4633a38b2c1585140998579ce23b4b7d',
    ),
    'symfony/var-exporter' => 
    array (
      'pretty_version' => '4.4.x-dev',
      'version' => '4.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '75a297f25a87ce9343d39241679578886f3fd458',
    ),
    'symfony/yaml' => 
    array (
      'pretty_version' => '4.4.x-dev',
      'version' => '4.4.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '2c309e258adeb9970229042be39b360d34986fad',
    ),
    'thiagoalessio/tesseract_ocr' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => 'e85c7bcd8617607a261d0ecf37a5b8e42eb80c53',
    ),
    'tightenco/collect' => 
    array (
      'pretty_version' => 'dev-laravel-8-ongoing',
      'version' => 'dev-laravel-8-ongoing',
      'aliases' => 
      array (
        0 => '9999999-dev',
      ),
      'reference' => 'b069783ab0c547bb894ebcf8e7f6024bb401f9d2',
    ),
    'twig/twig' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '5785185018ba41100b7eb319b2123a7642105d66',
    ),
    'vlucas/phpdotenv' => 
    array (
      'pretty_version' => '2.6.x-dev',
      'version' => '2.6.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'f1e2a35e53abe9322f0ab9ada689967e30055d40',
    ),
    'workerman/workerman' => 
    array (
      'pretty_version' => '3.x-dev',
      'version' => '3.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => '6614873e22d88f7eb761c39afcaf8301b4084283',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
