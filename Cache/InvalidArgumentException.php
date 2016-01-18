<?php

namespace Tale\Cache;

use Exception;

class InvalidArgumentException extends Exception implements \Psr\Cache\InvalidArgumentException
{
}