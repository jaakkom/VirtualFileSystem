<?php

declare(strict_types=1);

/*
 * This file is part of Twifty Virtual Filesystem.
 *
 * (c) Owen Parry <waldermort@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Twifty\VirtualFileSystem\Content;

/**
 * Implementation of ContentInterface using a string buffer.
 *
 * Outer class will need to implement empty methods.
 *
 * @author Owen Parry <waldermort@gmail.com>
 */
trait StringContentTrait
{
    use SeekableContentTrait;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var int
     */
    private $length;

    /**
     * Initializes the string buffer.
     *
     * Should be called upon class construction.
     *
     * @param string $data
     */
    protected function initializeData(string $data)
    {
        $this->buffer = $data;
        $this->length = strlen($data);
    }

    /**
     * Returns the size in bytes of the content.
     *
     * @return int
     */
    protected function getDataLength(): int
    {
        return $this->length;
    }

    /**
     * Reads $length number of bytes from $offset.
     *
     * @param int $offset
     * @param int $length
     *
     * @return string
     */
    protected function readData(int $offset, int $length): string
    {
        if (false === $data = substr($this->buffer, $offset, $length)) {
            return '';
        }

        return $data;
    }

    /**
     * Performs the actual writing of data.
     *
     * @param string $data
     * @param int    $offset
     * @param int    $replace
     */
    protected function writeData(string $data, int $offset, int $replace = 0): void
    {
        $pre = substr($this->buffer, 0, $offset);
        $post = substr($this->buffer, $offset + $replace);

        $this->initializeData($pre.$data.$post);
    }
}
