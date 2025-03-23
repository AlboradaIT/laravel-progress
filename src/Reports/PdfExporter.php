<?php 

namespace AlboradaIT\LaravelProgress\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PdfExporter extends BaseExporter
{
    protected string $template;

    public function __construct(Collection|Builder $source, array $columns = [], ?string $template = null)
    {
        $this->template = $template ?? config('progress.templates.default_pdf');
        parent::__construct($source, $columns);
    }

    protected function ensureDependenciesAreInstalled()
    {
        if (!class_exists(Pdf::class)) {
            throw new \Exception('DOMPDF package is not installed.');
        }
    }

    protected function prepareData(): array
    {
        $data = $this->source instanceof Builder ? $this->source->get() : $this->source;

        $rows = $data->map(fn($item) => $this->resolveColumns($item))->toArray();
        $headings = array_keys($rows[0]);

        return compact('rows', 'headings');
    }

    public function download(string $filename)
    {
        return Pdf::loadView($this->template, $this->prepareData())->download($filename);
    }

    public function store(string $path)
    {
        return Pdf::loadView($this->template, $this->prepareData())->save(storage_path("app/$path"));
    }

    public function stream()
    {
        return Pdf::loadView($this->template, $this->prepareData())->stream();
    }

    protected function resolveColumns($item): array
    {
        if ($this->columns) {
            return array_map(fn($col) => data_get($item, $col), $this->columns);
        }

        if ($item instanceof HasProgressReportColumns) {
            return $item->progressReportColumns();
        }

        return config('progress.exports.default_columns');
    }
}

