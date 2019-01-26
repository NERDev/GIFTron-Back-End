<?php

namespace G2A;

abstract class HTTP
{
    public static $requests;
    public static $clientId;
    public static $clientSecret;
    public static $baseURL;

    private static function buildHeaders($data)
    {
        foreach ($data as $header => $value)
        {
            $headers[] = "$header: $value";
        }

        return implode("\r\n", $headers);
    }

    static function get($url)
    {
        //var_dump("Executing GET on $url");
        self::$requests[] = $url;
        $clientId = self::$clientId;
        $clientSecret = self::$clientSecret;
        return json_decode(file_get_contents(self::$baseURL.$url, false, stream_context_create([
            'http' => [
                "header" => "Authorization: $clientId, $clientSecret"
            ]
        ])));
    }
    
    static function post($url, $data)
    {
        /*
        //var_dump("Executing POST on $url");
        self::$requests[] = $url;

        $tokentype && $headers['Authorization'] = "$tokentype {$prevobj->token}";
        $headers['Content-Type'] = "application/" . (json_decode($data) ? 'json' : 'x-www-form-urlencoded');
        
        //var_dump(self::buildHeaders($headers));

        return json_decode(file_get_contents(self::$baseURL.$url, false, stream_context_create([
            'http' => [
                'header'  => self::buildHeaders($headers),
                'method'  => 'POST',
                'content' => $data,
            ]
        ])));
        */
    }
}

class Game
{
    function __construct($id)
    {
        $this->id = $id;
    }

    function __get($n)
    {
        $classname = __NAMESPACE__ . "\\$n";
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
        elseif (class_exists($classname))
        {
            return $this->$n = new $classname();
        }
        else
        {
            return false;
        }
    }

    function info()
    {
        return HTTP::get("/products?id=$this->id");
    }
}

class Games extends API
{
    function __get($n)
    {
        if (!in_array($n, get_class_vars($this)))
        {
            return $this->$n = new Game($n);
        }
    }
}

class API
{
    function __construct($param1 = null, $param2 = null)
    {
        if ($param1 && $param2)
        {
            HTTP::$clientId = $param1;
            HTTP::$clientSecret = $param2;
            HTTP::$baseURL = "https://sandboxapi.g2a.com/v1";
        }
    }

    function __get($n)
    {
        $classname = __NAMESPACE__ . "\\$n";
        $context = get_parent_class($this) ? (new \ReflectionClass($this))->getShortName() : ucfirst($n);
        //var_dump("Here is where \$context needs to be determined.", $context, get_parent_class($this), $this);
        
        if (method_exists($this, $n))
        {
            return $this->$n = $this->$n();
        }
        elseif (class_exists($classname))
        {
            return $this->$n = new $classname;
        }
        else
        {
            return false;
        }
    }
}