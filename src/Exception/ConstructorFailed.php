<?php
namespace ParagonIE\EasyDB\Exception;

use ParagonIE\Corner\CornerTrait;
use PDOException;

/**
 * ConstructorFailed.
 *
 * @package ParagonIE\EasyDB
 */
class ConstructorFailed extends EasyDBException
{
    use CornerTrait;

    private ?PDOException $realException = null;

    public function setRealException(PDOException $ex): self
    {
        $this->realException = $ex;
        return $this;
    }

    public function getRealException(): ?PDOException
    {
        return $this->realException;
    }
}
