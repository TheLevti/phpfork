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

class PosixException extends RuntimeException implements PhpforkExceptionInterface
{
    public static function posixError(?string $pretext = null, ?LoggerInterface $logger = null): self
    {
        $posixErrno = posix_get_last_error();
        /** @var string|false $posixStrError */
        $posixStrError = posix_strerror($posixErrno);
        if (false === $posixStrError) {
            $posixStrError = 'Unable to translate posix error number.';
        }

        $exceptionMessage = $posixStrError;
        if (is_string($pretext)) {
            $exceptionMessage = sprintf('%s %s', $pretext, $posixStrError);
        }

        if ($logger instanceof LoggerInterface) {
            $logger->critical($exceptionMessage, [
                'posix_errno' => $posixErrno,
                'posix_strerror' => $posixStrError,
            ]);
        }

        return new self($exceptionMessage, $posixErrno);
    }
}
