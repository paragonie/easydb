<?php
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerTrait;

/**
 * ConstructorFailed.
 *
 * @package ParagonIE\EasyDB
 */
class ConstructorFailed extends \RuntimeException implements ExceptionInterface
{
    use CornerTrait;
}
