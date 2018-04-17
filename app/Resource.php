<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get the events for the resource
     */
    public function events()
    {
        return $this->hasMany('App\Event');
    }
}