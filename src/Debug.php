<?php


namespace Sonar200\Debugger;


use Reflection;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class Debug
{
    static $attachLength = 20;
    static $debug = false;
    static $logsFolder =  '/debug';
    private static $eol = PHP_EOL;

    /**
     * Цвета для форматирования в браузере
     */
    private const COLORS = [
        'number'   => '#ce3f3f',
        'string'   => '#000000',
        'type'     => '#3568ee',
        'boolean'  => '#000000',
        'property' => '#3f6d22',
    ];

    /**
     *
     * @return string
     */
    private static function space()
    {

        return self::$debug ? "" : "\t";
    }

    /**
     * Вывод чисел
     *
     * @param $number
     *
     * @return string
     */
    private static function number($number)
    {
        if (self::$debug) {
            return '<span style="color: ' . self::COLORS['number'] . ';">' . $number . '</span>';
        } else {
            return $number;
        }
    }

    private static function string($string)
    {
        if (self::$debug) {
            return '<span style="color: ' . self::COLORS['string'] . ';">' . $string . '</span>';
        } else {
            return $string;
        }
    }

    private static function boolean($var)
    {
        if (self::$debug) {
            return '<span style="color: ' . self::COLORS['boolean'] . ';">' . ($var ? 'true' : 'false') . '</span>';
        } else {
            return ($var ? 'true' : 'false');
        }
    }

    private static function property($var)
    {
        if (self::$debug) {
            return '<span style="color: ' . self::COLORS['property'] . ';">' . $var . '</span>';
        } else {
            return $var;
        }
    }

    private static function type($type)
    {
        if (self::$debug) {
            return '<span style="color: ' . self::COLORS['type'] . ';">' . $type . '</span>';
        } else {
            return $type;
        }
    }

    private static function getLine()
    {
        $backtrace = debug_backtrace();
        $caller = $backtrace[2];

        if (self::$debug) {
            return self::number($caller['line']) . ':</td><td style="border: 0; padding: 1px 0; vertical-align: top;">' . self::string($caller['file']);
        } else {
            return self::number($caller['line']) . ':' . self::string($caller['file']);
        }
    }


    /**
     * @param       $var
     * @param false $trace
     *
     */
    static function dump($var, $trace = false)
    {
        if (self::$debug) {
            $content = self::dumpToScreen($var, $trace);
        } else {
            $content = self::dumpToFile($var, $trace);
        }

        self::print($content);
    }

    private static function dumpToFile($var, $trace)
    {

        $type = gettype($var);

        $content = '-------------------------------------------------------------------------------------------------------' . self::$eol;
        $content .= '-------------------------------------------------------------------------------------------------------' . self::$eol;
        $content .= date('d.m.Y H:i:s') . self::$eol;
        $content .= '-------------------------------------------------------------------------------------------------------' . self::$eol;

        switch (mb_strtolower($type)) {
            case 'boolean':
                $content .= self::getLine() . ':' . self::type($type) . ':' . self::boolean($var);
            break;
            case 'array':
                $content .= self::getLine() . ':' . self::$eol . self::printArray($var);
            break;
            case 'object':
                $content .= self::getLine() . ':' . self::$eol . self::printObject($var);
            break;
            case 'null':
                $content .= self::getLine() . ':' . self::type($type);
            break;
            case 'double':
            case 'integer':
                $content .= self::getLine() . ':' . self::type($type) . ':' . self::number($var);
            break;
            default:
                $content .= self::getLine() . ':' . self::type($type) . ':' . self::string($var);
            break;
        }

        if ($trace) {
            $content .= self::$eol . self::$eol . self::viewTraceToFile();
        }
        $content .= self::$eol;
        $content .= self::$eol;

        return $content;
    }

    private static function dumpToScreen($var, $trace)
    {
        $type = gettype($var);

        $content = '<pre style="background-color: #ffffff; display: block; margin-top: 5px; border: 1px dotted #cccccc; width: 100%; padding: 5px; font-family: monospace; font-size: 12px; text-align: left; box-sizing: border-box;"><table style="border: 0;">';

        switch (mb_strtolower($type)) {
            case 'boolean':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':' . self::type($type) . ':' . self::boolean($var) . "</td></tr>";
            break;
            case 'array':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':';
                $content .= '<div>' . self::printArray($var) . '</div></td></tr>';
            break;
            case 'object':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':';
                $content .= '<div>' . self::printObject($var) . '</div></td></tr>';
            break;
            case 'null':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':' . self::type($type) . '</td></tr>';

            break;
            case 'string':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':' . self::type($type) . ':' . self::string($var) . '</td></tr>';
            break;
            case 'double':
            case 'integer':
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':' . self::type($type) . ':' . self::number($var) . '</td></tr>';
            break;
            default:
                $content .= '<tr><td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::getLine() . ':' . self::type($type) . ':' . self::string($var) . "</td></tr>";
            break;
        }

        $content .= "</table>";
        if ($trace) {
            $content .= self::viewTraceToScreen();
        }
        $content .= "</pre>";
        return $content;
    }

    public static function print($content)
    {
        if (self::$debug) {
            echo $content;
        } else {
            if (!file_exists(self::$logsFolder)) {
                mkdir(self::$logsFolder, 0777, true);
            }

            file_put_contents(self::$logsFolder . '/' . date('d.m.Y') . '.log', $content, FILE_APPEND);
        }
    }

    /**
     *
     */
    static function trace()
    {
        $content = '';

        if (self::$debug) {
            $content .= '<pre style="background-color: #ffffff; display: block; margin-top: 5px; border: 1px dotted #cccccc; width: 100%; padding: 5px; font-family: monospace; font-size: 12px; text-align: left;">';
            $content .= self::viewTraceToScreen();
            $content .= "</pre>";
        } else {
            $content = '-------------------------------------------------------------------------------------------------------' . self::$eol;
            $content .= '-------------------------------------------------------------------------------------------------------' . self::$eol;
            $content .= date('d.m.Y H:i:s') . self::$eol;
            $content .= '-------------------------------------------------------------------------------------------------------' . self::$eol;
            $content .= self::viewTraceToFile();
        }

        $content .= self::$eol;
        $content .= self::$eol;

        self::print($content);
    }

    /**
     * @param        $var
     *
     * @return string
     */
    private static function printArray($var)
    {
        if (self::$debug) {
            $content = self::printArrayToScreen($var);

        } else {
            $content = self::printArrayToFile($var);
        }

        return $content;
    }

    private static function printArrayToScreen($var, $spacer = '', $i = 0)
    {
        $countVar = count($var);
        $content = self::type('Array') . ' [' . self::number($countVar) . ']' . self::$eol;
        $spacer .= self::space();

        if ($i > self::$attachLength) {
            $content .= self::$eol . '<div style="padding-left: 30px;">' . $spacer . '...' . '</div>';
            return $content;
        }

        if ($countVar > 0) {
            $content .= self::btnCollapse();

            $content .= '<div class="debug-collapse" style="display: none;">';
            foreach ($var as $index => $item) {
                $type = gettype($item);
                $content .= self::$eol . '<div style="padding-left: 30px;">' . $spacer;
                switch (mb_strtolower($type)) {
                    case 'array':
                        $content .= $index . ' => ' . self::printArrayToScreen($item, $spacer, $i + 1);
                    break;
                    case 'object':
                        $content .= $index . ' => ' . self::printObjectToScreen($item, $spacer, $i + 1);
                    break;
                    case 'null':
                        $content .= $index . ' => ' . self::type($type);
                    break;
                    case 'boolean':
                        $content .= $index . ' => ' . self::type($type) . ':' . self::boolean($item);
                    break;
                    case 'double':
                    case 'integer':
                        $content .= $index . ' => ' . self::type($type) . ':' . self::number($item);
                    break;
                    default:
                        $content .= $index . ' => ' . self::type($type) . ':' . self::string($item);
                    break;
                }
                $content .= '</div>';
            }
            $content .= '</div>';

        }

        return $content;
    }

    private static function printArrayToFile($var, $spacer = '', $i = 0)
    {
        $countVar = count($var);
        $content = self::type('Array') . ' [' . self::number($countVar) . ']' . self::$eol;
        $spacer .= self::space();

        if ($i > self::$attachLength) {
            $content .= $spacer . '...';
            return $content;
        }
        if ($countVar > 0) {
            foreach ($var as $index => $item) {
                $type = gettype($item);
                $content .= $spacer;
                switch (mb_strtolower($type)) {
                    case 'array':
                        $content .= $index . ' => ' . self::printArrayToFile($item, $spacer, $i + 1);
                    break;
                    case 'object':
                        $content .= $index . ' => ' . self::printObjectToFile($item, $spacer, $i + 1);
                    break;
                    case 'null':
                        $content .= $index . ' => ' . self::type($type) . self::$eol;
                    break;
                    case 'boolean':
                        $content .= $index . ' => ' . self::type($type) . ':' . self::boolean($item) . self::$eol;
                    break;
                    case 'double':
                    case 'integer':
                        $content .= $index . ' => ' . self::type($type) . ':' . self::number($item) . self::$eol;
                    break;
                    default:
                        $content .= $index . ' => ' . self::type($type) . ':' . self::string($item) . self::$eol;
                    break;
                }
            }
        }

        return $content;
    }

    /**
     * @param mixed $var
     *
     * @return string
     */
    private static function printObject($var)
    {

        if (self::$debug) {
            $content = self::printObjectToScreen($var);
        } else {
            $content = self::printObjectToFile($var);
        }

        return $content;
    }

    private static function printObjectToScreen($var, $spacer = '', $i = 0)
    {
        try {
            $reflect = new ReflectionClass($var);
            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_STATIC);;
            $content = self::type('Object ' . get_class($var)) . self::$eol;
            $spacer .= self::space();

            if ($i > self::$attachLength) {
                $content .= self::$eol . '<div style="padding-left: 30px;">' . $spacer . '...' . '</div>';
                return $content;
            }

            if (!empty($properties)) {
                $content .= self::btnCollapse();

                $content .= '<div class="debug-collapse" style="display: none;">';
                foreach ($properties as $index => $property) {
                    $modifiers = Reflection::getModifierNames($property->getModifiers());
                    $property->setAccessible(true);

                    $item = $property->getValue($var);

                    if (in_array('private', $modifiers) || in_array('protected', $modifiers)) {
                        $property->setAccessible(false);
                    }

                    $type = gettype($item);

                    $content .= self::$eol . '<div style="padding-left: 30px;">' . $spacer;
                    $content .= $index . ' => ' . implode(' ', $modifiers) . ' ' . self::property($property->getName());

                    switch (mb_strtolower($type)) {
                        case 'array':
                            $content .= ':' . self::printArrayToScreen($item, $spacer, $i + 1);
                        break;
                        case 'object':
                            $content .= ':' . self::printObjectToScreen($item, $spacer, $i + 1);
                        break;
                        case 'null':
                            $content .= ':' . self::type($type);
                        break;
                        case 'boolean':
                            $content .= ':' . self::type($type) . ':' . self::boolean($item);
                        break;
                        case 'double':
                        case 'integer':
                            $content .= ':' . self::type($type) . ':' . self::number($item);
                        break;
                        default:
                            $content .= ':' . self::type($type) . ':' . self::string($item);
                        break;
                    }
                    $content .= '</div>';
                }

                $content .= '</div>';
            }

            return $content;
        } catch (ReflectionException $e) {
            return '';
        }
    }

    private static function printObjectToFile($var, $spacer = '', $i = 0)
    {
        try {
            $reflect = new ReflectionClass($var);
            $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_STATIC);;
            $content = self::type('Object ' . get_class($var));
            $spacer .= self::space();

            if ($i > self::$attachLength) {
                $content .= self::$eol . $spacer . '...';
                return $content;
            }

            if (!empty($properties)) {
                $content .= self::btnCollapse();

                foreach ($properties as $index => $property) {
                    $modifiers = Reflection::getModifierNames($property->getModifiers());
                    $property->setAccessible(true);

                    $item = $property->getValue($var);

                    if (in_array('private', $modifiers) || in_array('protected', $modifiers)) {
                        $property->setAccessible(false);
                    }


                    $content .= self::$eol . $spacer;
                    $content .= $index . ' => ' . implode(' ', $modifiers) . ' ' . self::property($property->getName());

                    $type = gettype($item);
                    switch (mb_strtolower($type)) {
                        case 'array':
                            $content .= ':' . self::printArrayToFile($item, $spacer, $i + 1);
                        break;
                        case 'object':
                            $content .= ':' . self::printObjectToFile($item, $spacer, $i + 1);
                        break;
                        case 'null':
                            $content .= ':' . self::type($type);
                        break;
                        case 'boolean':
                            $content .= ':' . self::type($type) . ':' . self::boolean($item);
                        break;
                        case 'double':
                        case 'integer':
                            $content .= ':' . self::type($type) . ':' . self::number($item);
                        break;
                        default:
                            $content .= ':' . self::type($type) . ':' . self::string($item);
                        break;
                    }
                }
            }

            return $content;
        } catch (ReflectionException $e) {
            return '';
        }
    }

    private static function viewTraceToFile()
    {
        $content = '';
        $backtrace = debug_backtrace();

        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && mb_strpos($trace['file'], 'Core/Debug') !== false) {
                continue;
            }
            if (isset($trace['line'])) {
                $content .= self::number($trace['line']) . ':' . self::string($trace['file']) . self::$eol;
            }
        }

        return $content;
    }

    private static function viewTraceToScreen()
    {
        $content = '<table style="border: 0;">';
        $backtrace = debug_backtrace();

        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && mb_strpos($trace['file'], 'Core/Debug') !== false) {
                continue;
            }
            if (isset($trace['line'])) {
                $content .= '<tr>';
                $content .= '<td style="border: 0; padding: 1px 0; text-align: right; vertical-align: top;">' . self::number($trace['line']) . ':</td>';
                $content .= '<td style="border: 0; padding: 1px 0; vertical-align: top;">' . self::string($trace['file']) . '</td>';

                $content .= '</tr>';
            }
        }
        $content .= "</table>";

        return $content;
    }

    /**
     * @return string
     */
    private static function btnCollapse()
    {
        return self::$debug ? '<button style="width: 15px; height: 20px; padding: 0;" onclick="this.nextSibling.style.display = this.nextSibling.style.display === \'none\' ? \'block\' : \'none\'; this.innerHTML = this.innerHTML === \'+\' ? \'-\' : \'+\';">+</button>' : '';
    }


    /**
     * @param string $logsFolder
     */
    public static function setLogsFolder(string $logsFolder): void
    {
        self::$logsFolder = $logsFolder;
    }
}