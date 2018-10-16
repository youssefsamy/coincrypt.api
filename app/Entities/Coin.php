<?php
/**
 * Created by PhpStorm.
 * User: ApolloYr
 * Date: 3/27/2018
 * Time: 5:49 PM
 */

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    protected $table = 'coins';
    public $timestamps = false;
}