<?php

namespace NeuroSYS\DoctrineDatatables\Field;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractField
{

    /**
     * @var string Field name
     */
    protected $name;

    /**
     * @var string Field alias
     */
    protected $alias;

    /**
     * @var AbstractField
     */
    protected $parent;

    /**
     * @var bool
     */
    protected $searchable = true;

    /**
     * Search string for this column
     *
     * @var string
     */
    protected $search;

    /**
     * Field path
     *
     * @var array
     */
    protected $path;

    /**
     * Alias index used to generate alias for a field
     * @var int
     */
    private static $aliasIndex = 1;

    /**
     * @var callback
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $options = array();

    public function __construct($name, $alias = null, $options = array())
    {
        $this->name    = $name;
        $this->alias   = $alias ? $alias : self::generateAlias($name);
        $this->options = $options;
    }

    public static function generateAlias($name)
    {
        if (!$name) {
            $name = 'x';
        }
        $name = preg_replace('/[^A-Z]/i', '', $name);

        return $name[0] . (self::$aliasIndex++);
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    public function setParent(AbstractField $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    public function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    public function getSearch()
    {
        return $this->search;
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param bool $searchable
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
    }

    /**
     * Whether this field is searchable
     *
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * Gets this field alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Gets this field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets full name containing entity alias and field name
     *
     * @return string
     */
    public function getFullName()
    {
        return ($this->getParent() ? $this->getParent()->getAlias() . '.' : '') . $this->name;
    }

    public function isSearch()
    {
        return $this->isSearchable()
            && $this->getSearch() != '';
    }

    /**
     * @param QueryBuilder $qb
     * @return self
     */
    public function filter(QueryBuilder $qb)
    {
        $qb->setParameter($this->getName(), '%'.$this->getSearch().'%');

        return $qb->expr()->like($this->getSearchField(), ':' . $this->getName());
    }

    public function getSearchField()
    {
        return ($this->getParent() ? $this->getParent()->getAlias() . '.' : '')
             . (isset($this->options['search_field']) ? $this->options['search_field'] : $this->getName());
    }

    /**
     * @return array Field path
     */
    public function getPath()
    {
        return $this->path ?: array($this->getName());
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     * @return self
     */
    public function select(QueryBuilder $qb)
    {
        $qb->addSelect($this->getFullName() /*. ' as ' . $this->getAlias()*/);
    }

    /**
     * @param QueryBuilder $qb
     * @return $this
     */
    public function order(QueryBuilder $qb, $dir = 'asc')
    {
        $qb->addOrderBy($this->getFullName(), $dir);
    }

    public function join(QueryBuilder $qb)
    {
        if ($this->getParent()) {
            $this->getParent()->join($qb);
        }
        return $this;
    }

    public function format(array $values)
    {
        if ($this->formatter) {
            return call_user_func_array($this->formatter, array($this, @$values[$this->getAlias()], $values));
        }

        return $this->getValue($values);
    }

    public function setFormatter($formatter)
    {
        if (!is_callable($formatter)) {
            throw new \Exception("Formatter must be a collable");
        }
        $this->formatter = $formatter;
    }

    /**
     * Gets field value based on its path and returns reference
     *
     * @param $values
     * @return mixed
     */
    public function &getValue(&$values)
    {
        foreach ($this->getPath() as $name) {
            $values = &$values[$name];
        }
        return $values;
    }
}