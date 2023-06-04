<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2020/3/6
 * Time: 16:40
 */

namespace App\Helpers;

use Predis\Command\Command;
class KeyKeys extends Command
{

    public function getId()
    {
        return 'REDIS_KEYS';
    }
}