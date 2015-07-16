<?php
namespace NeuroSYS\DoctrineDatatables;

class Request
{
    protected $request;

    public function __construct(array $request = array())
    {
        $this->request = $request;
    }

    /**
     * Get request parameter with datatables.js index style
     *
     * @param $name
     * @param $index
     * @param  bool  $default
     * @return mixed
     */
    public function get($name, $index = null, $default = null)
    {
        #$name .= (null !== $index ? '_' . $index : '');
        if (isset($this->request[$name])) {
            $value = $this->request[$name];
            if ($index !== null) {
                return $value[$index];
            } else {
                return $value;
            }
        }

        return $default;
    }

    public function all()
    {
        return $this->request;
    }
}
