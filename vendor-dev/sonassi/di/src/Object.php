<?php namespace Sonassi\Di;

class Object
{

    protected static $_instances = array();

    private static $_underscoreCache;
    private $_data = array();
    private $_origData = array();

    public function __toArray(array $arrAttributes = array())
    {
        if (empty($arrAttributes)) {
                return $this->_data;
        }

        $arrRes = array();
        foreach ($arrAttributes as $attribute) {
            if (isset($this->_data[$attribute])) {
                $arrRes[$attribute] = $this->_data[$attribute];
            }
            else {
                $arrRes[$attribute] = null;
            }
        }
        return $arrRes;
    }

    public function setOrigData($data)
    {
        $this->_origData = $data;
        return $this;
    }

    public function getOrigData()
    {
        return $this->_origData;
    }

    public function setData($key, $value=null)
    {
        if (is_array($key)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }
        return $this;
    }

    public function getData($key='', $index=null)
    {
        if (''===$key) {
            return $this->_data;
        }

        $default = null;

        // accept a/b/c as ['a']['b']['c']
        if (strpos($key,'/')) {
            $keyArr = explode('/', $key);
            $data = $this->_data;
            foreach ($keyArr as $i=>$k) {
                if ($k==='') {
                    return $default;
                }
                if (is_array($data)) {
                    if (!isset($data[$k])) {
                        return $default;
                    }
                    $data = $data[$k];
                } elseif ($data instanceof Object) {
                    $data = $data->getData($k);
                } else {
                    return $default;
                }
            }
            return $data;
        }

        // legacy functionality for $index
        if (isset($this->_data[$key])) {
            if (is_null($index)) {
                return $this->_data[$key];
            }

            $value = $this->_data[$key];
            if (is_array($value)) {
                //if (isset($value[$index]) && (!empty($value[$index]) || strlen($value[$index]) > 0)) {
                /**
                 * If we have any data, even if it empty - we should use it, anyway
                 */
                if (isset($value[$index])) {
                        return $value[$index];
                }
                return null;
            } elseif (is_string($value)) {
                $arr = explode("\n", $value);
                return (isset($arr[$index]) && (!empty($arr[$index]) || strlen($arr[$index]) > 0)) ? $arr[$index] : null;
            } elseif ($value instanceof Object) {
                return $value->getData($index);
            }
            return $default;
        }
        return $default;
    }

    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get' :
                $key = $this->_underscore(substr($method,3));
                $data = $this->getData($key, isset($args[0]) ? $args[0] : null);
                return $data;

            case 'set' :
                $key = $this->_underscore(substr($method,3));
                $result = $this->setData($key, isset($args[0]) ? $args[0] : null);
                return $result;

            case 'uns' :
                $key = $this->_underscore(substr($method,3));
                $result = $this->unsetData($key);
                return $result;

            case 'has' :
                $key = $this->_underscore(substr($method,3));
                return isset($this->_data[$key]);
        }
    }

    public function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        self::$_underscoreCache[$name] = $result;
        return $result;
    }

    public function _camelize($name)
    {
        return lcfirst($this->_ucwords(strtolower($name), ''));
    }

    public function _ucwords($str, $destSep='_', $srcSep='_')
    {
        return str_replace(' ', $destSep, ucwords(str_replace($srcSep, ' ', $str)));
            if (!empty($_COOKIE)) $_COOKIE = $this->stripMagicQuotes($_COOKIE);
    }

    public function stripMagicQuotes($arr)
    {
        foreach ($arr as $k => $v) {
            if (!empty($_COOKIE)) $_COOKIE = $this->stripMagicQuotes($_COOKIE);
        }
    }

    public function serialize($attributes = array(), $valueSeparator='=', $fieldSeparator=' ', $quote='"')
    {
        $res  = '';
        $data = array();
        if (empty($attributes)) {
            $attributes = array_keys($this->_data);
        }

        foreach ($this->_data as $key => $value) {
            if (in_array($key, $attributes)) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }
        $res = implode($fieldSeparator, $data);
        return $res;
    }

    public function __get($var)
    {
        $var = $this->_underscore($var);
        return $this->getData($var);
    }

    public function __set($var, $value)
    {
        $this->_isChanged = true;
        $var = $this->_underscore($var);
        $this->setData($var, $value);
    }

    public static function getMethods($className = false, $includeParentMethods = false)
    {
        if (!$className)
            $className = get_called_class();

        $methods = array();
        $reflector = new \ReflectionClass($className);

        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$includeParentMethods && $method->class != $className)
                continue;

            $block = (string) $reflector->getMethod($method->name)->getDocComment();
            $docblock = new DocBlock($block);
            $params = array();

            foreach ($reflector->getMethod($method->name)->getParameters() as $param) {
                $params[] = [ 'name' => $param->name, 'required' => !$param->isOptional() ];
            }

            $methods[$method->name] = array('name' => $method->name,
                                            'dockblock' => $docblock,
                                            'parameters' => $params);
        }
        return $methods;
    }

    public static function getNamespaceClasses($className = false)
    {
        if (!$className)
            $className = get_called_class();

        $reflector = new \ReflectionClass($className);
        $dir = dirname($reflector->getFileName());

        $classPaths = glob($dir.'/*.php');
        $classes = array_map(function($value) use($dir) {
                return str_replace([ $dir.'/', '.php' ], '', $value);
            }, $classPaths);

        return $classes;
    }

    public static function getInstance()
    {
        return Di::get(get_called_class());
    }

    public static function di($params)
    {
        $instances = [];
        foreach (self::getMethods(false, true)['__construct']['parameters'] as $id => $param) {
            $data = self::diNestObjects(explode('_', $param['name']), $params[$id]);
            $instances = array_merge_recursive($instances, $data);
        }

        $di = self::arrayToObject($instances);

        // Add the configurable DI objects

        return $di;
    }

    private static function diNestObjects($array, $value)
    {
        $result = [];
        $key = array_shift($array);
        if (count($array) > 0) {
            $result[$key] = self::diNestObjects($array, $value);
        } else {
            $result[$key] = $value;
        }
        return $result;
    }

    private static function arrayToObject($array)
    {
        $stdClass = new self;
        foreach ($array as $key => $value){
           $stdClass->{$key} = (is_array($value)) ? self::arrayToObject($value) : $value;
        }
        return $stdClass;
    }

}