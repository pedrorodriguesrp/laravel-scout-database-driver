<?php

namespace AdrianoPedro\Scout;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $searchable_id
 * @property string $searchable_model
 * @property text $searchable_data
 * @property string $updated_at
 * @property string $created_at
 */
class APSearchable extends Model
{

    protected $searchMode = "LIKE";
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table    = 'searchables';

    /**
     * @var array
     */
    protected $fillable = ['searchable_id', 'searchable_model','searchable_data', 'updated_at', 'created_at'];

    public function __construct()
    {
        // 
    }

}
