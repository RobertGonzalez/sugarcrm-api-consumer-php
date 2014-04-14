<?php
/**
 * A simple overloaded object implementation of a record
 */
class SugarApiBean
{
    /**
     * Holds the collection of record properties and their values
     *
     * @var array
     */
    protected $vars = array();

    /**
     * Gets a value from a record property
     *
     * @param string $name The property to get a value for
     * @return mixed
     */
    public function __get($name)
    {
        return array_key_exists($name, $this->vars) ? $this->vars[$name] : null;
    }

    /**
     * Sets a value on a record for a record property
     *
     * @param string $name The name of the property to set the value for
     * @param mixed $value The value to set the property to
     */
    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Simple loader that takes a result from an API call and reads it into this
     * object
     *
     * @param array $array An array of properties and values
     */
    public function loadFromArray(array $array)
    {
        $this->vars = array_merge($this->vars, $array);
    }
}
