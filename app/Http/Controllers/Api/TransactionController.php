<?php
/**
 * Created by PhpStorm.
 * User: ApolloYr
 * Date: 3/21/2018
 * Time: 11:32 PM
 */

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Entities\Order;
use App\Entities\Transaction;
use App\Http\Controllers\Controller;
use React\Http\Request;

class TransactionController extends Controller
{
    public function balance($user, $currency) {
        $balance = 0;

        $sent = Transaction::where([['src_currency', $currency], ['user_id', $user->id]])->sum('src_amount');
        $receive = Transaction::where([['dest_currency', $currency], ['user_id', $user->id]])->sum('dest_amount');

        $sale = Order::where([['user_id', $user->id], ['type', MarketOrderType::SELL], ['src_currency', $currency]])->sum('amount');

        $balance = $sent + $receive - $sale;

        return $balance + 1000;
    }

    public function getSummary(Request $request) {

    }
}