<?php

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace VV;

/**
 * Class Exception
 */
class Exception extends \Exception
{
    use Exception\Core;

    /**
     * @param \Throwable $e
     * @param bool|null  $asHtml
     *
     * @return string
     */
    public static function castToString(\Throwable $e, bool $asHtml = null): string
    {
        // default values from globals
        if ($asHtml === null) {
            $asHtml = \VV\ISHTTP;
        }

        // make message
        $className = get_class($e);
        if ($code = $e->getCode()) {
            $className .= " [$code]";
        }
        $message = $asHtml ? "<b>$className</b>:\n\n" : "$className: ";
        if ($bodyMessage = $e->getMessage()) {
            $message .= $bodyMessage;
        }

        return $message . "\r\n" . self::backtraceString($e, $asHtml);
    }

    /**
     * @param \Throwable $e
     * @param bool|null  $forHttp
     * @param bool|null  $forDev
     */
    public static function show(\Throwable $e, bool $forHttp = null, bool $forDev = null): void
    {
        try {
            if ($forHttp === null) {
                $forHttp = \VV\ISHTTP;
            }
            if ($forDev === null) {
                $forDev = vv()->devMode();
            }

            if ($forHttp && !headers_sent()) {
                header('HTTP/1.1 503');
            }

            if ($forDev || !$forHttp) {
                echo self::castToString($e, $forHttp);
                exit(1);
            }

            if (file_exists($t = 'vv-maintenance-page.php') || file_exists($t = vv()->appPath() . "/$t")) {
                $_VV_ERROR = true;
                require $t;
                exit;
            }
        } catch (\Throwable) {
        }

        if (http_response_code(503)) {
            header('Content-Type: text/plain');
        }

        echo 'Unfortunately the application is down for a bit of maintenance right now.';
        exit(1);
    }

    /**
     * @param \Throwable $e
     *
     * @return \RuntimeException
     */
    public static function runtimeFromPrevious(\Throwable $e): \RuntimeException
    {
        return new \RuntimeException(get_class($e) . ': ' . $e->getMessage(), 0, $e);
    }

    public static function backtraceString(\Throwable $exc, bool $asHtml = null): string
    {
        if ($asHtml === null) {
            $asHtml = \VV\ISHTTP;
        }

        $eol = "\r\n";
        $fullTrace = $eol;

        $addRecord = function ($ttl, $val) use (&$fullTrace, $asHtml, $eol) {
            if (!$val) {
                return;
            }
            $ttl = "$ttl: ";
            if ($asHtml) {
                $ttl = "<b>$ttl</b>";
            }
            $fullTrace .= $ttl . $val . $eol;
        };

        while ($exc) {
            $addRecord('Exception', '\\' . get_class($exc));
            $addRecord('Message', $exc->getMessage());
            $addRecord('Code', $exc->getCode());
            $fullTrace .= $eol;

            $trace = $exc->getTraceAsString();
            $file = $exc->getFile();
            $line = $exc->getLine();
            if (!$asHtml) {
                $trows_file = "$file on line $line";
            } else {
                $trace = \htmlspecialchars($trace);
                $callback = function ($m) {
                    return $m[1] . \VV\ideUrl($m[2], $m[3], "$m[2]($m[3])") . ': ';
                };
                $trace = preg_replace_callback('/^(#\d+ )(.*)\((\d+)\): /m', $callback, $trace);

                $trows_file = \VV\ideUrl($file, $line, true);
            }

            $fullTrace .= "$trace$eol    thrown in $trows_file$eol$eol";
            $exc = $exc->getPrevious();
        }

        if (!$asHtml) {
            return $fullTrace;
        }

        return "<pre>$fullTrace</pre>";
    }
}
