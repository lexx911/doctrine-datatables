<?php
namespace NeuroSYS\DoctrineDatatables;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use NeuroSYS\DoctrineDatatables\Field\Entity;
use NeuroSYS\DoctrineDatatables\Field\AbstractField;
use NeuroSYS\DoctrineDatatables\Field\MultiField;

class TableBuilder
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var FieldRegistry
     */
    private $registry;

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var string Root entity class name
     */
    protected $className;

    protected $index;

    protected $parent;

    public function __construct(EntityManager $em, array $request, FieldRegistry $registry = null, RendererInterface $renderer = null)
    {
        $this->em       = $em;
        if (null === $registry) {
            $registry = new FieldRegistry();
        }
        $this->registry  = $registry;
        $this->request   = new Request($request);
        $this->renderer  = $renderer;
    }

    public function getQueryBuilder()
    {
        return $this->getTable()->getQueryBuilder();
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param  string $className Root entity class name
     * @param  string $alias     Alias of root entity
     * @return $this
     */
    public function from($className, $alias)
    {
        $table = new Table($className, $alias, $this->em, $this->request, $this->renderer);
        //$table->from($className, $alias);

        $this->setTable($table);

        return $this;
    }

    public function setRenderer(RendererInterface $renderer)
    {
        $this->table->setRenderer($renderer);
    }

    /**
     * @param  QueryBuilder $qb QueryBuilder instance
     * @throws \Exception
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        if (!$this->table) {
            $from = $qb->getDQLPart('from');
            $from = array_shift($from);
            /**
             * @var \Doctrine\ORM\Query\Expr\From $from
             */
            $this->table = new Table($from->getFrom(), $from->getAlias(), $this->em, $this->request, $this->renderer);
        }
        $this->getTable()->setQueryBuilder($qb);

        return $this;
    }

    protected function getCurrentIndex()
    {
        if (null != $this->index) {
            return $this->index;
        } elseif (null !== $this->table->getIndex()) {
            return $this->getTable()->getIndex();
        } else {
            return count($this->getTable()->getFields());
        }
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function with($name)
    {
        //$field = new MultiField($this->getTable(), $name);
        //$field->setIndex($this->getCurrentIndex());
        //$field->setParent($this->getTable());

        $builder = new TableBuilder($this->em, $this->request->all(), $this->registry, $this->renderer);
        $this->setIndex($this->getCurrentIndex());
        $builder->setTable($this->getTable());

        $this->getTable()->setField($name, $field);

        $builder->parent = $this;

        return $this->parent;
    }

    public function end()
    {
        return $this->parent;
    }

    protected function setTable(Entity $table)
    {
        $this->table = $table;
    }

    public function join($fullName, $alias)
    {
        return $this->leftJoin($fullName, $alias);
    }

    public function leftJoin($fullName, $alias)
    {
        $this->_join($fullName, $alias, 'LEFT');

        return $this;
    }

    public function innerJoin($fullName, $alias)
    {
        $this->_join($fullName, $alias, 'INNER');

        return $this;
    }

    /**
     * @param string $name Name of field as defined in datatables columns
     * @param string type Type of filter: text, date, number etc. See FieldRegistry::registerStandardFields()
     * @param string $select Name of field for SELECT clause (supported dot notation for relations)
     * @param string $filter Name of field for WHERE clause (optional, set = $select if true or null)
     * @param array $options
     * @return $this
     * @throws \Exception
     * @see FieldRegistry
     */
    public function add($name, $type = 'text', $select = null, $filter = null, $options = array())
    {
        if (null === $select) {
            if (strpos($name, '.') === false) {
                $select = [ $this->getTable()->getAlias() . '.' . $name ];
            } else {
                $select = [ $name ];
            }
        }
        if (null === $filter) {
            $filter = $select;
        }

        if (!is_array($select)) {
            $select = explode(',', $select);
        }
        foreach ($select as $key => $value) {
            if (is_numeric($key)) {
                if (strpos(strtolower($value), ' as ') !== false || strpos($value, '.') === false) { // check for non entity field
                    $parentAlias = '';
                    $fieldName = $value;
                } else {
                    list($parentAlias, $fieldName) = explode('.', trim($value));
                    if (!isset($select[trim($parentAlias)])) {
                        $select[trim($parentAlias)] = array();
                    }
                }
                $select[trim($parentAlias)][] = $fieldName;
                unset($select[$key]);
            }
        }

        if (!is_array($filter)) {
            $filter = explode(',', $filter);
            foreach ($filter as $key => $value) {
                if (strpos($value, '.') === false) {
                    $filter[$key] = $this->getTable()->getAlias() . '.' . $value;
                }
            }
        }

        $index = $this->getColumnIndex($name);
        /**
         * @var AbstractField $field
         */
        $field = $this->registry->resolve($type, $this->getTable(), $options);
        $field->setIndex($index);
        $field->setSelect($select);
        $field->setSearchFields($filter);
        if (isset($options['template'])) {
            $field->setTemplate($options['template']);
        }
        if (isset($options['context'])) {
            $field->setContext($options['context']);
        }
        $this->getTable()->setField($name, $field);

        return $this;
    }

    private function _join($fullName, $alias, $type)
    {
        list($parentAlias, $name) = explode('.', $fullName);
        $parent = $this->getTable()->getEntity($parentAlias);
        if (!$parent) {
            throw new \Exception("Parent entity not found for " . $fullName);
        }
        $parent->join($name, $alias, $type);

        return $this;
    }

    /**
     * @param $name  Hint name
     * @param $value  Hint value
     * @return $this
     */
    public function setHint($name, $value)
    {
        $this->getTable()->setHint($name, $value);

        return $this;
    }

    private function getColumnIndex($name)
    {
        $this->columns = $this->columns ?: $this->request->get("columns");
        foreach($this->columns as $index => $value) {
            if ($value['data'] == $name) {
                return $index;
            }
        }
        return count($this->getTable()->getFields());
    }
}
