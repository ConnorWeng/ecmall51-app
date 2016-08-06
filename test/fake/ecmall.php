<?php

define('CHARSET', 'utf8');

function ecm_json_encode($value)
{
    if (CHARSET == 'utf-8' && function_exists('json_encode'))
    {
        return json_encode($value);
    }

    $props = '';
    if (is_object($value))
    {
        foreach (get_object_vars($value) as $name => $propValue)
        {
            if (isset($propValue))
            {
                $props .= $props ? ','.ecm_json_encode($name)  : ecm_json_encode($name);
                $props .= ':' . ecm_json_encode($propValue);
            }
        }
        return '{' . $props . '}';
    }
    elseif (is_array($value))
    {
        $keys = array_keys($value);
        if (!empty($value) && !empty($value) && ($keys[0] != '0' || $keys != range(0, count($value)-1)))
        {
            foreach ($value as $key => $val)
            {
                $key = (string) $key;
                $props .= $props ? ','.ecm_json_encode($key)  : ecm_json_encode($key);
                $props .= ':' . ecm_json_encode($val);
            }
            return '{' . $props . '}';
        }
        else
        {
            $length = count($value);
            for ($i = 0; $i < $length; $i++)
            {
                $props .= ($props != '') ? ','.ecm_json_encode($value[$i])  : ecm_json_encode($value[$i]);
            }
            return '[' . $props . ']';
        }
    }
    elseif (is_string($value))
    {
        //$value = stripslashes($value);
        $replace  = array('\\' => '\\\\', "\n" => '\n', "\t" => '\t', '/' => '\/',
                        "\r" => '\r', "\b" => '\b', "\f" => '\f',
                        '"' => '\"', chr(0x08) => '\b', chr(0x0C) => '\f'
                        );
        $value  = strtr($value, $replace);
        if (CHARSET == 'big5' && $value{strlen($value)-1} == '\\')
        {
            $value  = substr($value,0,strlen($value)-1);
        }
        return '"' . $value . '"';
    }
    elseif (is_numeric($value))
    {
        return $value;
    }
    elseif (is_bool($value))
    {
        return $value ? 'true' : 'false';
    }
    elseif (empty($value))
    {
        return '""';
    }
    else
    {
        return $value;
    }
}

?>