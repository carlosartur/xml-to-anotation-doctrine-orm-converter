<?php

namespace Main;

use DateTime;
use Main\AbstractSingleton;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

class Logger extends AbstractSingleton implements LoggerInterface
{
    /** @var DateTime $date */
    private DateTime $date;

    protected function __construct()
    {
        $this->date = new DateTime();
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }

    /**
     * Get file of log to write new log. If file doesn't exists, create it.
     *
     * @return string
     */
    private function getLogPath(): string
    {
        $dateFormated = $this->date->format("Y_m_d_h_i_s");
        $realPath = realpath(__DIR__ . "/../..");
        $filename = "{$realPath}/logs/{$dateFormated}.log";
        if (!file_exists($filename)) {
            touch($filename);
            chmod($filename, 0666);
        }
        return $filename;
    }

    /**
     * Write log on logfile.
     *
     * @param mixed $message
     * @param string $level
     * @return void
     */
    private function writeLog(
        $message,
        string $level = LogLevel::INFO,
        array $context = []
    ): void {
        try {
            $message = $this->getMessageLogString($message);
            $context = $context
                ? "context: " . $this->getMessageLogString($message) . PHP_EOL
                : "";
            $now = (new DateTime())->format("Y-m-d h:i:s");
            $logPrefix = PHP_EOL . str_repeat("-", 150) .  PHP_EOL . "[{$now}]: [{$level}]" . PHP_EOL;
            $finalMessage = "{$logPrefix}{$context}{$message}";
            error_log($finalMessage, 3, $this->getLogPath());
        } catch (Throwable $exception) {
            var_dump($this->getMessageLogString($exception));
            die();
        }
    }

    /**
     * Transform any message to write on log
     *
     * @param mixed $message
     * @return string
     */
    private function getMessageLogString($message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_numeric($message)) {
            return (string) $message;
        }

        if (is_bool($message)) {
            return $message ? "true" : "false";
        }

        if (is_object($message)) {
            if ($message instanceof Throwable) {
                return $this->getMessageLogString([
                    "file" => $message->getFile(),
                    "line" => $message->getLine(),
                    "message" => $message->getMessage(),
                    "trace" => explode(PHP_EOL, $message->getTraceAsString()),
                ]);
            }

            return $this->getMessageLogString([
                "object_id" => spl_object_id($message),
                "class" => get_class($message),
                "object" => $message,
            ]);
        }

        return json_encode($message, JSON_PRETTY_PRINT);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::EMERGENCY, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::ALERT, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::CRITICAL, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::ERROR, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::WARNING, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::NOTICE, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::INFO, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->writeLog($message, LogLevel::DEBUG, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($message, $level, $context);
    }
}
