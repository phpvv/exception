<?php
declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Exception;

/**
 * Trait Core
 *
Created by VV Error
 */
trait Core {

    private ?string $origionalMessage;

    /**
     * Error constructor.
     *
     * @param string|null     $message
     * @param int|null        $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = null, int $code = null, \Throwable $previous = null) {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::__construct(
        // fetch message hierarchically
            ($this->origionalMessage = $message) // passed
                ?: $this->message // or default property
                ?: static::messageFromClassName(), // or class name splitted by words
            // fetch code hierarchically
            $code ?: $this->code,
            //
            $previous
        );
    }

    /**
     * @return string|null
     */
    public function origionalMessage(): ?string {
        return $this->origionalMessage;
    }

    /**
     * @param null|bool $asHtml
     *
     * @return string
     */
    public function toString(bool $asHtml = null): string {
        /** @var \Throwable $this */
        return \VV\Exception::castToString($this, $asHtml);
    }

    public static function messageFromClassName(mixed $fqcn = null): string {
        if (!$fqcn) {
            $fqcn = get_called_class();
        } elseif (is_object($fqcn)) {
            $fqcn = get_class($fqcn);
        }

        $camelMessage = preg_replace('/^.*?(\w+?)(Exception)?$/', '$1', $fqcn);

        return preg_replace('/(\w)([A-Z])/', '\1 \2', $camelMessage);
    }

    /**
     * @param \Throwable $previous
     * @param int|null       $code
     *
     * @return static
     */
    public static function fromPrevious(\Throwable $previous, int $code = null): static {
        return new static($previous->getMessage(), $code !== null ? $code : $previous->getCode(), $previous);
    }

    /**
     * @param \Throwable|mixed $error
     *
     * @return static
     */
    public static function cast(mixed $error): static {
        if ($error instanceof \VV\Exception) return $error;

        if (!is_object($error)) {
            if (is_array($error)) $error = '(array)';
            if ($error === null) $error = '(null)';

            return new static($error);
        }

        $fqcn = get_class($error);
        $message = null;

        if ($error instanceof \Throwable && ($message = $error->getMessage())) {
            $message = "$fqcn: $message";
        }

        if (!$message) {
            $message = static::messageFromClassName($fqcn);
        }

        return new static($message, $error->getCode(), $error);
    }
}
