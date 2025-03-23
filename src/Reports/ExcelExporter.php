<?php 

namespace AlboradaIT\LaravelProgress\Reports;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\{FromCollection, FromQuery, WithHeadings};

class ExcelExporter extends BaseExporter implements WithHeadings
{
    protected bool $isQuery;

    protected function ensureDependenciesAreInstalled()
    {
        if (!class_exists(\Maatwebsite\Excel\Excel::class)) {
            throw new \Exception('Laravel Excel package is not installed.');
        }
    }

    public function __construct(Collection|Builder $source, array $columns = [])
    {
        parent::__construct($source, $columns);
        $this->isQuery = $source instanceof Builder;
    }

    public function download(string $filename)
    {
        return Excel::download($this->exportable(), $filename);
    }

    public function store(string $path)
    {
        return Excel::store($this->exportable(), $path);
    }

    public function stream()
    {
        return Excel::download($this->exportable(), 'export.xlsx', \Maatwebsite\Excel\Excel::XLSX, [
            'Content-Disposition' => 'inline'
        ]);
    }

    protected function exportable()
    {
        $source = $this->source;
        $columnsResolver = fn($item) => $this->resolveColumns($item);
        
        if ($this->isQuery) {
            return new class($source, $columnsResolver) implements FromQuery, WithHeadings {
                public function __construct(public $query, public $resolver) {}

                public function query() { return $this->query; }

                public function headings(): array {
                    return array_keys(($this->resolver)($this->query->first()));
                }

                public function map($item): array {
                    return ($this->resolver)($item);
                }
            };
        }

        return new class($source, $columnsResolver) implements FromCollection, WithHeadings {
            public function __construct(public $collection, public $resolver) {}

            public function collection() {
                return $this->collection->map($this->resolver);
            }

            public function headings(): array {
                return array_keys(($this->resolver)($this->collection->first()));
            }
        };
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
