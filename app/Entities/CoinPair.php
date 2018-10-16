<?php
/**
 * Created by PhpStorm.
 * User: ApolloYr
 * Date: 3/27/2018
 * Time: 4:47 PM
 */

namespace App\Entities;


use Illuminate\Database\Eloquent\Model;

class CoinPair extends Model
{
    protected $table = 'coin_pairs';
    public $timestamps = false;
}