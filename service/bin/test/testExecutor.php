<?php
use App\Queues\PayNotifyExecutor;

(new PayNotifyExecutor)->push(34876, 'P20190416183027936854');
