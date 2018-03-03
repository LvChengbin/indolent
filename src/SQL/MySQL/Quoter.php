<?php

namespace NextSeason\Model\SQL\MySQL;

use NextSeason\Model\SQL\Common;

class Quoter extends Common\Quoter {

    protected $prefix = '`';

    protected $suffix = '`';
}
