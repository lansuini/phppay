<?php
use App\Channels\ChannelProxy;

dump(parse_url('http://www.baidu.com/sbsd?adfad'));

dump(parse_url('www.baidu.com'));

(new ChannelProxy)->queryBalance(111111);
