<?php
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerTrait;

/**
 * QueryError.
 *
 * @package ParagonIE\EasyDB
 * @api
 */
class EasyDBException extends \RuntimeException implements ExceptionInterface
{
    use CornerTrait;
}
