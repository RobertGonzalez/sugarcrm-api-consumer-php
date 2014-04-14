<?php

class SugarApiBean
{
    protected $vars = array();
    
    public function __get($name)
    {
        return array_key_exists($name, $this->vars) ? $this->vars[$name] : null;
    }
    
    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }
    
    public function loadFromArray(array $array)
    {
        $this->vars = array_merge($this->vars, $array);
    }
}