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

namespace Twifty\VirtualFileSystem\Inject;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
abstract class AbstractInjectable implements InjectableInterface
{
    /**
     * @var string|null
     */
    protected $protocol;

    /**
     * @var string|null
     */
    protected $hierarchy;

    /**
     * Constructor.
     *
     * @param string|null $workingDirectory
     */
    public function __construct(string $workingDirectory = null)
    {
        $this->setWorkingDirectory($workingDirectory);
    }

    /**
     * Configures the working directory.
     *
     * @param string|null $dir
     */
    public function setWorkingDirectory(string $dir = null)
    {
        $this->protocol = $this->hierarchy = null;

        if (null !== $dir) {
            if (false === $parts = $this->split($dir)) {
                throw new \InvalidArgumentException(sprintf('Not a valid url "%s"', $dir));
            }

            $this->protocol = $parts[0];
            $this->hierarchy = $parts[1];
        }
    }

    /**
     * Returns a path with a scheme or null.
     *
     * @param string|null $path
     *
     * @return string|null
     */
    protected function resolvePath(string $path = null)
    {
        if (empty($path)) {
            return $this->protocol ? ($this->protocol.'://'.$this->hierarchy) : null;
        }

        // Return null on windows paths
        if (preg_match('{^ (?: [a-zA-Z]:\\\\ ) }x', $path)) {
            return null;
        }

        if (false !== $parts = $this->split($path)) {
            return $parts[0].'://'.$parts[1];
        }

        // If an absolute path was given, it can only be appended if cwd is also absolute
        if ($this->protocol && (!$this->hierarchy || '/' !== $path[0])) {
            return $this->protocol.'://'.$this->normalize($this->hierarchy.'/'.$path);
        }

        return null;
    }

    /**
     * Splits and normalizes the path.
     *
     * @param string $path
     *
     * @return bool
     */
    private function split(string $path)
    {
        if (false !== strpos($path, '://')) {
            $parts = explode('://', $path, 2);

            return [
                $parts[0],
                $this->normalize($parts[1]),
            ];
        }

        return false;
    }

    /**
     * Normalizes the directories in a path.
     *
     * @param string $path
     *
     * @return string
     */
    private function normalize(string $hierarchy): string
    {
        $chunks = [];

        foreach (explode('/', $hierarchy) as $chunk) {
            if ('..' === $chunk) {
                array_pop($chunks);
            } elseif ('.' !== $chunk && '' !== $chunk) {
                $chunks[] = $chunk;
            }
        }

        return implode('/', $chunks);
    }
}
