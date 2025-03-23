<?php 

namespace AlboradaIT\LaravelProgress\Reports;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseExporter
{
    protected Collection|Builder $source;
    protected array $columns;
    protected string $template;

    public function __construct(Collection|Builder $source, array $columns = [], string $template = '')
    {
        $this->ensureDependenciesAreInstalled();
        $this->source = $source;
        $this->columns = $columns;
        $this->template = $template;
    }

    abstract public function download(string $filename);
    abstract public function store(string $path);
    abstract public function stream();
    abstract protected function ensureDependenciesAreInstalled();
}