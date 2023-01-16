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

use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\NodeInterface;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class Glob extends AbstractInjectable
{
    const BRACE_PATTERN = '/(.*){(.*)}(.*)/';

    /**
     * Performs a glob on the given pattern.
     *
     * @param string   $pattern
     * @param int|null $options
     *
     * @return string[]
     */
    public function __invoke(...$args): array
    {
        $pattern = $args[0];
        $options = $args[1] ?? 0;

        $path = $this->resolvePath($pattern);
        if (!$path || !$root = Registry::resolveRoot($path)) {
            return \glob($pattern, $options);
        }

        $hierarchy = Registry::getHierarchy($path);

        $results = [];

        if ($options & GLOB_BRACE) {
            $patterns = $this->expandBraces($hierarchy);
        } else {
            $patterns = array_map(function (string $dir) { return [$dir]; }, $hierarchy);
        }

        $cwd = $this->resolvePath(null);
        $cwdLength = $cwd ? strlen($cwd) : 0;

        foreach ($this->doGlob($root, $patterns, $options) as $node) {
            $path = $node->getPath();

            if ($node instanceof Directory && GLOB_MARK === ($options & GLOB_MARK)) {
                $path .= '/';
            }

            $results[] = substr($path, $cwdLength);
        }

        if (0 === ($options & GLOB_NOSORT)) {
            sort($results);
        }

        if (empty($results) && GLOB_NOCHECK === ($options & GLOB_NOCHECK)) {
            $results[] = $pattern;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'glob';
    }

    /**
     * Takes an array of dir names and expands braces for each.
     *
     * Note, other wildcards will remain in the chunks.
     *
     * Input: [ 'foo', '{bar,baz}' ]
     * Output: [ ['foo'], ['bar', 'baz'] ]
     *
     * @param string[] $chunks
     *
     * @return string[][]
     */
    private function expandBraces(array $chunks): array
    {
        $expand = function (string $chunk) use (&$expand): array {
            $matches = [];
            $result = [];

            if (preg_match(self::BRACE_PATTERN, $chunk, $matches)) {
                foreach (explode(',', $matches[2]) as $option) {
                    if ('' !== $option = trim($option)) {
                        // There may be multiple -> '{foo,bar}log.{txt,json}'
                        $result = array_merge($result, $expand($matches[1].$option.$matches[3]));
                    }
                }
            } else {
                $result[] = $chunk;
            }

            return $result;
        };

        return array_map($expand, $chunks);
    }

    /**
     * Yields all end nodes matching the patterns.
     *
     * @param Directory  $node
     * @param string[][] $patterns
     * @param int        $options
     *
     * @return NodeInterface[]
     */
    private function doGlob(Directory $node, array $patterns, int $options): \Traversable
    {
        $filterDirectories = function ($node) {
            foreach ($node->getChildren() as $child) {
                if ($child instanceof Directory) {
                    yield $child;
                }
            }
        };

        $flags = 0;
        $fileNames = array_shift($patterns);

        if ($options & GLOB_NOESCAPE) {
            $flags |= FNM_NOESCAPE;
        }

        if (!empty($patterns)) {
            foreach ($filterDirectories($node) as $dirNode) {
                if ($this->isFileNameMatch($dirNode->getFilename(), $fileNames, $flags)) {
                    yield from $this->doGlob($dirNode, $patterns, $options);
                }
            }
        } elseif ($options & GLOB_ONLYDIR) {
            foreach ($filterDirectories($node) as $fileNode) {
                if ($this->isFileNameMatch($fileNode->getFilename(), $fileNames, $flags)) {
                    yield $fileNode;
                }
            }
        } else {
            foreach ($node->getChildren() as $fileNode) {
                if ($this->isFileNameMatch($fileNode->getFilename(), $fileNames, $flags)) {
                    yield $fileNode;
                }
            }
        }
    }

    /**
     * Checks if the filename matches one of the given patterns.
     *
     * @param string   $filename
     * @param string[] $patterns
     * @param int      $flags
     *
     * @return bool
     */
    private function isFileNameMatch(string $filename, array $patterns, int $flags)
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $filename, $flags)) {
                return true;
            }
        }

        return false;
    }
}
