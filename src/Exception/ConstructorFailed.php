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

    /** @var \PDOException|null $realException */
    private $realException = null;

    /**
     * @param \PDOException $ex
     * @return ConstructorFailed
     */
    public function setRealException(\PDOException $ex): self
    {
        $this->realException = $ex;
        return $this;
    }

    /**
     * @return \PDOException|null
     */
    public function getRealException()
    {
        return $this->realException;
    }
}
