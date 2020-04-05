<?php

/*
 * This file is part of the thelevti/phpfork package.
 *
 * (c) Petr Levtonov <petr@levtonov.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace TheLevti\phpfork\Exception;

use Psr\Log\LoggerInterface;
use RuntimeException;

class ProcessControlException extends RuntimeException implements PhpforkExceptionInterface
{
    public static function pcntlError(?string $pretext = null, ?LoggerInterface $logger = null): self
    {
        $pcntlErrno = pcntl_get_last_error();
        /** @var string|false $pcntlStrError */
        $pcntlStrError = pcntl_strerror($pcntlErrno);
        if (false === $pcntlStrError) {
            $pcntlStrError = 'Unable to translate process control error number.';
        }

        $exceptionMessage = $pcntlStrError;
        if (is_string($pretext)) {
            $exceptionMessage = sprintf('%s %s', $pretext, $pcntlStrError);
        }

        if ($logger instanceof LoggerInterface) {
            $logger->critical($exceptionMessage, [
                'pcntl_errno' => $pcntlErrno,
                'pcntl_strerror' => $pcntlStrError,
            ]);
        }

        return new self($exceptionMessage, $pcntlErrno);
    }
}
