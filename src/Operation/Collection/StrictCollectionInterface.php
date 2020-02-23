<?php
declare(strict_types=1);

namespace App\Operation\Collection;

use Closure;

interface StrictCollectionInterface extends CollectionInterface
{
    /**
     * @return int
     */
    public function count(): int;

    public function map(Closure $callable): array;

    /**
     * @param array $collection
     *
     * @return mixed
     */
    public static function fromArray(array $collection): self;

    /**
     * @return array
     */
    public function toArray(): array;
}