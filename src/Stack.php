<?php

declare(strict_types=1);

namespace thomas\phplox\src;

/**
 * @template T
 */
class Stack
{
    /** @var array<T> */
    private array $lifo;

    public function __construct()
    {
        $this->lifo = [];
    }

    /**
     * Push elements onto the stack.
     *
     * @param T $item The item to push onto the stack.
     *
     * @return int The number of elements in the stack.
     */
    public function push($item): int
    {
        return array_push($this->lifo, $item);
    }

    /**
     * Pop last elements off the stack.
     *
     * @return ?T The last element from the stack. If stack is empty, null will be returned.
     */
    public function pop(): mixed
    {
        return array_pop($this->lifo);
    }

    /**
     * Return last element of the stack without removing it.
     *
     * @return ?T The last element from the stack. If stack is empty, null will be returned.
     */
    public function peek(): mixed
    {
        $index = count($this->lifo) - 1;
        $element = $this->lifo[$index] ?? null;
        return $element;
    }

    public function get(int $index): mixed
    {
        return $this->lifo[$index] ?? null;
    }

    public function size(): int
    {
        return count($this->lifo);
    }

    public function empty(): bool
    {
        return $this->size() == 0;
    }
}