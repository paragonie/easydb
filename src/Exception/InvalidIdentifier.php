<?php
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerTrait;

/**
 * InvalidIdentifier.
 *
 * @package ParagonIE\EasyDB
 */
class InvalidIdentifier extends \InvalidArgumentException implements ExceptionInterface
{
    use CornerTrait;
}
