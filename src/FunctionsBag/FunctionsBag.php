<?php
/**
 * Created by PhpStorm.
 * User: Costa
 * Date: 05/07/2019
 * Time: 19:33
 */

namespace ThatsIt\FunctionsBag;

use ThatsIt\Configurations\Configurations;
use ThatsIt\Exception\PlatformException;

/**
 * Class FunctionsBag
 * @package ThatsIt\FunctionsBag
 *
 * this class is a bag for functions that will be needed in more than one place
 */
class FunctionsBag
{
    /**
     * @var null|string
     */
    private static $routesPath = null;
    
    /**
     * @var string
     */
    private static $httpHost = "";
    
    /**
     * @param string $routesPath
     */
    public static function setRoutesPath(string $routesPath): void
    {
        self::$routesPath = $routesPath;
    }
    
    /**
     * @param string $httpHost
     */
    public static function setHttpHost(string $httpHost): void
    {
        self::$httpHost = $httpHost;
    }
    
    /**
     * @param string $name
     * @param array $variables[name => value]
     * @param bool $withOptional (url with optional part or not. when there is no optional part, doesn't matter its value)
     * @param bool $addHttpHost (if true, will add the domain to the url)
     * @return string
     * @throws PlatformException
     */
    public static function getUrl(string $name, array $variables = [], bool $withOptional = false,
                                  bool $addHttpHost = false): string
    {
        static $routes;
        
        // just to load routes once
        if (!$routes) $routes = Configurations::getRoutesConfig(self::$routesPath);
        
        if (!isset($routes[$name])) {
            throw new PlatformException("There are no url for '".$name."'",
                PlatformException::ERROR_NOT_FOUND_DANGER);
        }
        
        // save variables names that were already used in url
        $alreadyUsedVariablesInPath = [];
        
        $path = $routes[$name]['path'];
        // will substitute all variables for their value
        foreach ($variables as $name => $value) {
            // limit: -1 is the same as no limit; $count will no how many replaces happened
            $path = preg_replace("/\{".$name."(\:.*){0,1}\}/U", $value, $path, -1, $count);
            // if any replace happened, this variable is already used
            // if not, this variable should be added to path as a GET parameter
            if ($count) $alreadyUsedVariablesInPath[] = $name;
        }
        
        // remove all variables already set in url (this step was not made in last foreach just as a safety measure)
        foreach ($alreadyUsedVariablesInPath as $variableName) unset($variables[$variableName]);
        
        // will add get variables (this variables are the remain ones from $variables that were not set in $path already)
        if (count($variables) > 0) $path .= "?".http_build_query($variables);
        
        if ($withOptional) {
            // if so removes just parenthesis
            $path = preg_replace("/\(|\)/", "", $path);
        } else {
            // else removes everything that is inside of parenthesis
            $path = preg_replace("/\(.*\)/", "", $path);
        }
        
        // if there are some more variables to substitute, it will raise a exception
        preg_match("/\{.*\}/", $path, $matches);
        if (isset($matches[0])) {
            throw new PlatformException("There are some variables that weren't replaced.",
                PlatformException::ERROR_NOT_FOUND_DANGER);
        }
        
        if ($addHttpHost) $path = self::$httpHost.$path;
        
        return $path;
    }
}