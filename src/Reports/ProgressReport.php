<?php 

namespace AlboradaIT\LaravelProgress\Reports;

use AlboradaIT\LaravelProgress\Contracts\Progressable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProgressReport
{
    protected Collection|Builder $source;
    protected array $columns = [];
    protected string $template;

    public static function for(Progressable $progressable)
    {
        return (new static)->fromCollection(collect([$progressable]));
    }

    public static function fromQuery(Builder $query)
    {
        $instance = new static;
        $instance->source = $query;
        return $instance;
    }

    public static function fromCollection(Collection $collection)
    {
        $instance = new static;
        $instance->source = $collection;
        return $instance;
    }

    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function template(string $template)
    {
        $this->template = $template;
        return $this;
    }

    public function toExcel()
    {
        return new ExcelExporter($this->source, $this->columns);
    }

    public function toPdf()
    {
        return new PdfExporter($this->source, $this->columns, $this->template ?? config('progress.templates.default_pdf'));
    }

    public function customExporter(string $exporterClass)
    {
        return new $exporterClass($this->source, $this->columns, $this->template);
    }
}
