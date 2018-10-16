<?php
/**
 * Created by PhpStorm.
 * User: ApolloYr
 * Date: 3/27/2018
 * Time: 5:48 PM
 */

namespace App\Http\Controllers\Api;


use App\Constants\MarketOrderType;
use App\Constants\TransactionStatus;
use App\Entities\Coin;
use App\Entities\CoinPair;
use App\Entities\Setting;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use React\Http\Request;

class CoinController extends Controller
{
    public function getBuyFee($coin) {
        $coin = Coin::where('coin', $coin)->first();
        if (isset($coin)) {
            return $coin['buy_fee'];
        } else {
            return 0.15;
        }
    }

    public function getSellFee($coin) {
        $coin = Coin::where('coin', $coin)->first();
        if (isset($coin)) {
            return $coin['sell_fee'];
        } else {
            return 0.15;
        }
    }

    public function getCoinPairs()
    {
        $coinpairs = CoinPair::get();

        return response()->json([
            'success' => true,
            'data' => $coinpairs
        ]);
    }

    public function coinsInfo() {

        $before_day = Carbon::now()->addDay(-1)->toDateTimeString();

        $query =    "SELECT coin_pairs.*, coins.*, IFNULL(trans.high, 0) high, IFNULL(trans.low, 0) low, " .
                        "IFNULL(first_tran.rate, 0) first_price, IFNULL(last_tran.rate, 0) last_price, IFNULL(trans.volume, 0) volume, IFNULL(trans.currency_volume, 0) currency_volume " .
                    "FROM coin_pairs ".
                        "LEFT JOIN (" .
                            "SELECT dest_currency AS pair_coin, src_currency AS coin, MAX(rate) high, MIN(rate) low, MIN(id) min_id, MAX(id) max_id, SUM(src_amount) volume, SUM(dest_amount) currency_volume " .
                            "FROM transactions " .
                            "WHERE status = " . TransactionStatus::SUCCESS . " AND exchange_type = " . MarketOrderType::SELL . " AND datetime >= '" . $before_day . "' " .
                            "GROUP BY dest_currency, src_currency " .
                        ") trans ON coin_pairs.coin = trans.coin AND coin_pairs.pair_coin = trans.pair_coin " .
                        "LEFT JOIN transactions AS first_tran ON first_tran.id = trans.min_id " .
                        "LEFT JOIN transactions AS last_tran ON last_tran.id = trans.max_id " .
                        "LEFT JOIN coins ON coin_pairs.coin = coins.coin " .
                    "ORDER BY coins.id";



        $result = \DB::select($query);

        foreach ($result as $item) {
            if ($item->last_price == 0) {
                $item->change = 0;
            } else {
                $item->change = ($item->last_price - $item->first_price)/$item->first_price*100;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

}