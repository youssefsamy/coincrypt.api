<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Constants\VerifyStatus;
use App\Entities\Profile;
use App\Libs\GoogleAuthenticator;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MarketController extends Controller
{
    public function getChartData() {
        $data = array();

        $date = Carbon::create(2018,1,1,0,0,0);
        $now = Carbon::now();
        while ($date < $now) {

            $price1 = rand(1500, 2000) / 1000;
            $price2 = rand(1500, 2000) / 1000;
            $price3 = rand(1500, 2000) / 1000;
            $price4 = rand(1500, 2000) / 1000;

            $volume = rand(30000, 100000);

            array_push($data, array(strtotime($date) * 1000, min($price1, $price2), max($price1, $price2), min($price3, $price4), max($price3, $price4), $volume));

            $date->addHour(1);
        }

        return response()->json($data);
    }
}