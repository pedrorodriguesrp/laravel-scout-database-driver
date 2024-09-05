<?php

namespace AdrianoPedro\Scout\Engines;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use AdrianoPedro\Scout\APSearchable;

class APSearchEngine extends Engine
{
    /**
     * @var APSearch
     */
    protected $apsearchable;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * Create a new engine instance.
     *
     * @param APSearch $tnt
     */
    public function __construct(APSearchable $apsearchable)
    {
        $this->apsearchable = $apsearchable;
    }

    /**
     * Update the given model in the index.
     *
     * @param Collection $models
     *
     * @return void
     */
    public function update($models)
    {
        $apsearchable = $this->apsearchable;

        $models->each(function ($model) use ($apsearchable) {
            if (($model->searchMode && $model->searchMode !== "DIRECT") || (!$model->searchMode && $this->apsearchable->searchMode !== "DIRECT")) {
                $array              = $model->toSearchableArray();

                // Normalize dates to a common format (e.g., Y-m-d)
                $array = $this->normalizeDatesInArray($array);

                $modelclass         = get_class($model);
                $modelclass         = str_replace("\App", "App", $modelclass);


                $apsearchable       = APSearchable::where('searchable_id', $model->getKey())->where("searchable_model", $modelclass)->first() ?? new APSearchable();

                // $searchable_data    = mb_strtolower(implode(" ", $model->toSearchableArray()));

                $searchable_data    = "";
                foreach ($model->toSearchableArray() ?? [] as $key => $value) {
                    $searchable_data .= is_object($value) || is_array($value) ? json_encode($value) : $value;
                    $searchable_data .= " ";
                }
                $searchable_data    =  mb_strtolower($searchable_data);

                if (!$apsearchable->searchable_data || ($apsearchable->searchable_data && $apsearchable->searchable_data != $searchable_data)) {
                    $apsearchable->fill([
                        "searchable_id"     => $model->getKey(),
                        "searchable_model"  => $modelclass,
                        "searchable_data"   => $searchable_data,
                    ]);
                    $apsearchable->save();
                }
            }
        });
    }

    /**
     * Remove the given model from the index.
     *
     * @param Collection $models
     *
     * @return void
     */
    public function delete($models)
    {
        $models->each(function ($model) {
            $modelclass         = get_class($model);
            $apsearchable       = APSearchable::where('searchable_id', $model->getKey())->where("searchable_model", $modelclass)->first();

            if ($apsearchable) {
                $apsearchable->delete();
            }
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    public function search(Builder $builder, array $options = [])
    {
        try {
            return $this->performSearch($builder, $options);
        } catch (IndexNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param int     $perPage
     * @param int     $page
     *
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $results = [];
        $searchResults = $this->performSearch($builder);
        $searchResults = $searchResults['ids'];

        if ($builder->limit) {
            $results['hits'] = $builder->limit;
        }

        /**
         * New feature to implement overall orderby
         */
        $model      = $this->builder->model;
        $builder    = $this->getBuilder($model);

        if ($this->builder->queryCallback) {
            call_user_func($this->builder->queryCallback, $builder);
        }

        // $models     = $builder->whereIn(
        //     $model->getQualifiedKeyName(),
        //     array_values($searchResults)
        // )->pluck('id');

        // sort models by user choice
        // if (!empty($this->builder->orders)) {
        //     $searchResults = $models;
        // } else {
        //     $searchResults = collect($searchResults);
        // }
        /** ********* **/

        $searchResults = collect($searchResults);
        $results['hits'] = $searchResults->count();
        $chunks = array_chunk($searchResults->toArray(), $perPage);

        if (empty($chunks)) {
            return $results;
        }

        if (array_key_exists($page - 1, $chunks)) {
            $results['ids'] = $chunks[$page - 1];
        } else {
            $results['ids'] = [];
        }

        return $results;
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $searchable_model   = get_class($builder->model);
        $search             = strtolower($builder->query);
        // Normalize date in search query if any date-like string is detected
        $search = $this->normalizeDate($search);

        $sanatized_search   = str_replace(["+", "-", "*"], "", $search);
        $this->builder      = $builder;
        $searchMode         = $builder->model->searchMode ?? $this->apsearchable->searchMode;

        switch ($searchMode) {
            case 'BOOLEAN':
            case 'NATURAL LANGUAGE':
                if (strlen($sanatized_search) > 0) {
                    $mode               = $searchMode;
                    $searchable_model   = addslashes($searchable_model);
                    $apsearchable       = APSearchable::whereRaw("searchable_model = '$searchable_model' AND MATCH(searchables.searchable_data)AGAINST('*$search*' IN $mode MODE)")->pluck('searchable_id');
                } else {
                    $apsearchable       = [];
                }
                break;
            case 'DIRECT':
                if (strlen($sanatized_search) > 0) {
                    $mode               = $searchMode;
                    $searchable_model   = addslashes($searchable_model);
                    $apsearchable       = $builder->model->get()->filter(function ($item) use ($search) {
                        return strpos(strtolower(implode(" ", $item->toSearchableArray())), $search) > -1;
                    })->pluck("id");
                } else {
                    $apsearchable = [];
                }
                break;
            default:
                $apsearchable       = APSearchable::where('searchable_model', $searchable_model)->where('searchables.searchable_data', 'like', "%" . $search . "%")->join($builder->model->getTable(), $builder->model->getQualifiedKeyName(), "=", "searchables.searchable_id");
                $apsearchable       = isset($builder->constraints) ? $apsearchable->mergeConstraintsFrom($builder->constraints) : $apsearchable;
                foreach ($builder->orders as $order) {
                    $apsearchable = $apsearchable->orderBy($order['column'], $order['direction']);
                }
                $apsearchable       = $apsearchable->pluck('searchable_id');
                break;
        }
        $results = $apsearchable->unique()->toArray();
        $results = ["ids" => $results];
        return $results;
    }

    /**
     * Normalize dates in the array to Y-m-d format.
     *
     * @param array $array
     * @return array
     */
    protected function normalizeDatesInArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->normalizeDate($value);
            }
        }
        return $array;
    }

    /**
     * Detect and normalize date strings to Y-m-d format.
     *
     * @param string $value
     * @return string
     */
    protected function normalizeDate($value)
    {
        // Check for date formats and normalize them to 'Y-m-d'
        if (preg_match('/(\d{2})[\/\-](\d{2})[\/\-](\d{4})/', $value, $matches)) {
            // Assume input could be either d/m/Y or m/d/Y, so we normalize both ways.
            if (checkdate($matches[2], $matches[1], $matches[3])) {
                return "{$matches[3]}-{$matches[2]}-{$matches[1]}"; // d/m/Y format
            } elseif (checkdate($matches[1], $matches[2], $matches[3])) {
                return "{$matches[3]}-{$matches[1]}-{$matches[2]}"; // m/d/Y format
            }
        }

        return $value; // Return original if not a date
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param mixed                               $results
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        $results = $results['ids'] ?? [];

        if (is_null($results) || count($results) === 0) {
            return Collection::make();
        }

        $keys       = collect($results)->values()->all();
        $builder    = $this->getBuilder($model);

        if ($this->builder->queryCallback) {
            call_user_func($this->builder->queryCallback, $builder);
        }

        $models     = $builder->whereIn(
            $model->getQualifiedKeyName(),
            $keys
        )->get()->keyBy($model->getKeyName());

        // sort models by user choice
        if (!empty($this->builder->orders)) {
            return $models->values();
        }

        // sort models by tnt search result set
        return collect($results)->map(function ($hit) use ($models) {
            if (isset($models[$hit])) {
                return $models[$hit];
            }
        })->filter()->values();
    }

    /**
     * Return query builder either from given constraints, or as
     * new query. Add where statements to builder when given.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return Builder
     */
    public function getBuilder($model)
    {
        // get query as given constraint or create a new query
        $builder = isset($this->builder->constraints) ? $this->builder->constraints : $model->newQuery();

        $builder = $this->handleSoftDeletes($builder, $model);

        $builder = $this->applyWheres($builder);

        $builder = $this->applyOrders($builder);

        return $builder;
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['ids'])->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits'];
    }


    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Determine if soft delete is active and depending on state return the
     * appropriate builder.
     *
     * @param  Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return Builder
     */
    private function handleSoftDeletes($builder, $model)
    {
        // remove where statement for __soft_deleted when soft delete is not active
        // does not show soft deleted items when trait is attached to model and
        // config('scout.soft_delete') is false
        if (!$this->usesSoftDelete($model) || !config('scout.soft_delete', true)) {
            unset($this->builder->wheres['__soft_deleted']);
            return $builder;
        }

        /**
         * Use standard behaviour of Laravel Scout builder class to support soft deletes.
         *
         * When no __soft_deleted statement is given return all entries
         */
        if (!in_array('__soft_deleted', $this->builder->wheres)) {
            return $builder->withTrashed();
        }

        /**
         * When __soft_deleted is 1 then return only soft deleted entries
         */
        if ($this->builder->wheres['__soft_deleted']) {
            $builder = $builder->onlyTrashed();
        }

        /**
         * Returns all undeleted entries, default behaviour
         */
        unset($this->builder->wheres['__soft_deleted']);
        return $builder;
    }

    /**
     * Apply where statements as constraints to the query builder.
     *
     * @param Builder $builder
     * @return \Illuminate\Support\Collection
     */
    private function applyWheres($builder)
    {
        // iterate over given where clauses
        return collect($this->builder->wheres)->map(function ($value, $key) {
            // for reduce function combine key and value into array
            return [$key, $value];
        })->reduce(function ($builder, $where) {
            // separate key, value again
            list($key, $value) = $where;
            return $builder->where($key, $value);
        }, $builder);
    }

    /**
     * Apply order by statements as constraints to the query builder.
     *
     * @param Builder $builder
     * @return \Illuminate\Support\Collection
     */
    private function applyOrders($builder)
    {
        //iterate over given orderBy clauses - should be only one
        return collect($this->builder->orders)->map(function ($value, $key) {
            // for reduce function combine key and value into array
            return [$value["column"], $value["direction"]];
        })->reduce(function ($builder, $orderBy) {
            // separate key, value again
            list($column, $direction) = $orderBy;
            return $builder->orderBy($column, $direction);
        }, $builder);
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        //
    }

    public function lazyMap($builder, $results, $model){

    }
    public function createIndex($name, $options = []){

    }
    public function deleteIndex($name){

    }
}
