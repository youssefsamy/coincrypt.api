<?php
/**
 * Created by PhpStorm.
 * User: ApolloYr
 * Date: 3/22/2018
 * Time: 12:01 AM
 */

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false;
}