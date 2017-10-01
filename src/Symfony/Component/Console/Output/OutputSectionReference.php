<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Output;

/**
 * @internal
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 */
final class OutputSectionReference
{
    private $ref = array();

    public function addRef(ConsoleSectionOutput $output)
    {
        array_unshift($this->ref, $output);
    }

    public function calculateBuffer(ConsoleSectionOutput $output)
    {
        $buffer = 0;

        foreach ($this->ref as $outputStream) {
            if ($outputStream === $output) {
                break;
            }

            $buffer += $outputStream->lines;
        }

        return $buffer;
    }

    public function getOutput(ConsoleSectionOutput $output)
    {
        $content = '';

        foreach ($this->ref as $outputStream) {
            if ($outputStream === $output) {
                break;
            }

            $content .= $outputStream->content;
        }

        return $content;
    }
}
