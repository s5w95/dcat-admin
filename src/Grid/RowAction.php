<?php

namespace Dcat\Admin\Grid;

use Illuminate\Support\Fluent;

abstract class RowAction extends GridAction
{
    /**
     * @var Fluent
     */
    protected $row;

    /**
     * @var Column
     */
    protected $column;

    /**
     * @var string
     */
    public $selectorPrefix = '.grid-row-action-';

    /**
     * Get primary key value of current row.
     *
     * @return mixed
     */
    public function key()
    {
        if ($this->row) {
            return $this->row->get($this->parent->keyName());
        }

        return parent::key();
    }

    /**
     * Set row model.
     *
     * @param mixed $key
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed
     */
    public function row($key = null)
    {
        if (func_num_args() == 0) {
            return $this->row;
        }

        return $this->row->{$key};
    }

    /**
     * Set row model.
     *
     * @param Fluent $row
     *
     * @return $this
     */
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param Column $column
     *
     * @return $this
     */
    public function setColumn(Column $column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return string
     */
    public function href()
    {
    }

    /**
     * Render row action.
     *
     * @return string
     */
    public function html()
    {
        if (! $href = $this->href()) {
            $href = 'javascript:void(0);';
        }

        $attributes = $this->formatHtmlAttributes();

        return sprintf(
            "<a data-_key='%s' href='%s' class='%s' {$attributes}>%s</a>",
            $this->key(),
            $href,
            $this->elementClass(),
            $this->name()
        );
    }
}
