<?php
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerTrait;

/**
 * QueryError.
 *
 * @package ParagonIE\EasyDB
 */
class QueryError extends \RuntimeException implements ExceptionInterface
{
    use CornerTrait;
}
