<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\CoinPair;
use App\Entities\Order;
use App\Entities\Transaction;
use App\Events\OrderEvent;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function getBuyOrders(Request $request)
    {

        $input = $request->input();

        $orders = Order::where('type', MarketOrderType::BUY)
            ->where('src_currency', $input['src_currency'])
            ->where('dest_currency', $input['dest_currency'])
            ->groupBy('rate')
            ->orderBy('rate', 'DESC')
            ->select('rate', \DB::raw('SUM(amount) AS amount'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getSellOrders(Request $request)
    {

        $input = $request->input();

        $orders = Order::where('type', MarketOrderType::SELL)
            ->where('src_currency', $input['src_currency'])
            ->where('dest_currency', $input['dest_currency'])
            ->groupBy('rate')
            ->orderBy('rate', 'ASC')
            ->select('rate', \DB::raw('SUM(amount) AS amount'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function addBuyExchange($user_id, $src_currency, $dest_currency, $amount, $rate, $feeRate, $main)
    {
        $amount = round($amount, 8);
        if ($amount <= 0) {
            return;
        }

        $feeAmount = round($amount * $feeRate / 100, 8);

        $transaction = new Transaction();
        $transaction->user_id = $user_id;
        $transaction->datetime = Carbon::now();
        $transaction->src_currency = $src_currency;
        $transaction->src_amount = round($rate * $amount, 2);
        $transaction->dest_currency = $dest_currency;
        $transaction->dest_amount = round($amount - $feeAmount, 8);
        $transaction->rate = $rate;
        $transaction->fee = $feeAmount;
        $transaction->fee_rate = $feeRate;
        $transaction->type = TransactionType::EXCHANGE;
        $transaction->exchange_type = MarketOrderType::BUY;
        $transaction->status = TransactionStatus::SUCCESS;
        $transaction->main = $main;
        $transaction->save();

    }

    public function addSaleExchange($user_id, $src_currency, $dest_currency, $amount, $rate, $feeRate, $main)
    {
        $amount = round($amount, 8);
        if ($amount <= 0) {
            return;
        }

        $feeAmount = round($rate * $amount * $feeRate / 100, 2);
        $dest_amount = round($rate * $amount - $feeAmount, 2);

        $transaction = new Transaction();

        $transaction->user_id = $user_id;
        $transaction->datetime = Carbon::now();
        $transaction->src_currency = $src_currency;
        $transaction->src_amount = $amount;
        $transaction->dest_currency = $dest_currency;
        $transaction->dest_amount = $dest_amount;
        $transaction->rate = $rate;
        $transaction->fee = $feeAmount;
        $transaction->fee_rate = $feeRate;
        $transaction->type = TransactionType::EXCHANGE;
        $transaction->exchange_type = MarketOrderType::SELL;
        $transaction->status = TransactionStatus::SUCCESS;
        $transaction->main = $main;
        $transaction->save();
    }

    public function buyOrder(Request $request)
    {
        $input = $request->input();

        $src_currency = $input['src_currency'];
        $dest_currency = $input['dest_currency'];

        if (!isset($input['amount'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount to buy'
            ]);
        }

        if (!isset($input['rate'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter exhcange rate to buy'
            ]);
        }

        if ($input['amount'] < env('ORDER_MINIMUM_AMOUNT')) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 0.001'
            ]);
        }

        $user = Auth::user();

        $amount = $input['amount'];
        $rate = $input['rate'];

        $transactionController = new TransactionController();
        $balance = $transactionController->balance($user, $src_currency);

        if ($rate * $amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your Balance is not enough to buy.'
            ]);
        }

        $rate = round($rate, 2);
        $amount = round($amount, 8);
        $buyAmount = 0;
        $users = array();
        $last_price = '';

        $coins = new CoinController();

        $saleOrders = Order::where([['type', MarketOrderType::SELL], ['rate', $rate], ['src_currency', $dest_currency], ['dest_currency', $src_currency]])->orderBy('created_at')->get();

        foreach ($saleOrders as $order) {
            if ($order->amount > $amount) {
                $buyAmount += $amount;

                $this->addSaleExchange($order->user_id, $dest_currency, $src_currency, $amount, $order->rate, $coins->getSellFee($dest_currency), 0);

                array_push($users, $order->user_id);
                $last_price = $rate;

                $order->amount = $order->amount - $amount;
                $order->save();
                $amount = 0;

                break;
            } else {
                $buyAmount += $order->amount;

                $this->addSaleExchange($order->user_id, $dest_currency, $src_currency, $order->amount, $order->rate, $coins->getSellFee($dest_currency), 0);

                array_push($users, $order->user_id);
                $last_price = $rate;

                $order->delete();
                $amount = $amount - $order->amount;
            }
            if ($amount <= 0) break;
        }

        if ($buyAmount > 0) {
            $this->addBuyExchange($user->id, $src_currency, $dest_currency, $buyAmount, $rate, $coins->getBuyFee($dest_currency), 1);

            array_push($users, $user->id);
        }

        if ($amount > 0) {
            $buyOrder = new Order();

            $buyOrder->type = MarketOrderType::BUY;
            $buyOrder->user_id = $user->id;
            $buyOrder->src_currency = $src_currency;
            $buyOrder->dest_currency = $dest_currency;
            $buyOrder->rate = $rate;
            $buyOrder->amount = $amount;

            array_push($users, $user->id);

            $buyOrder->save();
        }

        $buyAmount1 = Order::where([['type', MarketOrderType::BUY], ['src_currency', $src_currency], ['dest_currency', $dest_currency], ['rate', $rate]])->sum('amount');
        $saleAmount1 = Order::where([['type', MarketOrderType::SELL], ['src_currency', $dest_currency], ['dest_currency', $src_currency], ['rate', $rate]])->sum('amount');

        event(new OrderEvent(array(
            'type' => 'sendOrder',
            'buyAmount' => $buyAmount1,
            'sellAmount' => $saleAmount1,
            'selPairCurrency' => $src_currency,
            'selPairCoin' => $dest_currency,
            'users' => $users,
            'rate' => $rate,
            'last_price' => $last_price,
            'exchange_type' => MarketOrderType::BUY,
            'trade_amount' => $buyAmount,
            'trade_currency_amount' => $buyAmount*$rate,
            'time' => Carbon::now()->toDateTimeString()
        )));

        return response()->json([
            'success' => true,
        ]);
    }

    public function saleOrder(Request $request)
    {
        $input = $request->input();

        $src_currency = $input['src_currency'];
        $dest_currency = $input['dest_currency'];

        if (!isset($input['amount'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount to buy'
            ]);
        }

        if (!isset($input['rate'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter exhcange rate to buy'
            ]);
        }

        if ($input['amount'] < env('ORDER_MINIMUM_AMOUNT')) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 0.001'
            ]);
        }

        $user = Auth::user();

        $amount = $input['amount'];
        $rate = $input['rate'];

        $transactionController = new TransactionController();
        $balance = $transactionController->balance($user, $src_currency);

        if ($amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your Balance is not enough to sale.'
            ]);
        }

        $rate = round($rate, 2);
        $amount = round($amount, 8);

        $saleAmount = 0;
        $users = array();
        $last_price = '';

        $coins = new CoinController();

        $saleOrders = Order::where([['type', MarketOrderType::BUY], ['rate', $rate], ['src_currency', $dest_currency], ['dest_currency', $src_currency]])->orderBy('created_at')->get();

        foreach ($saleOrders as $order) {
            if ($order->amount > $amount) {
                $saleAmount += $amount;

                $this->addBuyExchange($order->user_id, $dest_currency, $src_currency, $amount, $rate, $coins->getBuyFee($src_currency), 0);

                array_push($users, $order->user_id);
                $last_price = $rate;

                $order->amount = $order->amount - $amount;
                $order->save();
                $amount = 0;

                break;
            } else {
                $saleAmount += $order->amount;

                $this->addBuyExchange($order->user_id, $dest_currency, $src_currency, $order->amount, $rate, $coins->getBuyFee($src_currency), 0);

                array_push($users, $order->user_id);
                $last_price = $rate;

                $order->delete();
                $amount = $amount - $order->amount;
            }
            if ($amount <= 0) break;
        }

        if ($saleAmount > 0) {
            $this->addSaleExchange($user->id, $src_currency, $dest_currency, $saleAmount, $rate, $coins->getSellFee($src_currency), 1);

            array_push($users, $user->id);
        }

        if ($amount > 0) {
            $saleOrder = new Order();

            $saleOrder->type = MarketOrderType::SELL;
            $saleOrder->user_id = $user->id;
            $saleOrder->src_currency = $src_currency;
            $saleOrder->dest_currency = $dest_currency;
            $saleOrder->rate = $rate;
            $saleOrder->amount = $amount;

            $saleOrder->save();

            array_push($users, $user->id);
        }

        $buyAmount1 = Order::where([['type', MarketOrderType::BUY], ['src_currency', $dest_currency], ['dest_currency', $src_currency], ['rate', $rate]])->sum('amount');
        $saleAmount1 = Order::where([['type', MarketOrderType::SELL], ['src_currency', $src_currency], ['dest_currency', $dest_currency], ['rate', $rate]])->sum('amount');
//
        event(new OrderEvent(array(
            'type' => 'sendOrder',
            'buyAmount' => $buyAmount1,
            'sellAmount' => $saleAmount1,
            'selPairCurrency' => $dest_currency,
            'selPairCoin' => $src_currency,
            'users' => $users,
            'rate' => $rate,
            'last_price' => $last_price,
            'exchange_type' => MarketOrderType::SELL,
            'trade_amount' => $saleAmount,
            'trade_currency_amount' => $saleAmount*$rate,
            'time' => Carbon::now()->toDateTimeString()
        )));

        return response()->json([
            'success' => true,
        ]);
    }

    public function getMarketTrades(Request $request)
    {

        $input = $request->input();
        $coin1 = $input['coin1'];
        $coin2 = $input['coin2'];

        $transactions = Transaction::where([['type', TransactionType::EXCHANGE], ['src_currency', $coin1], ['dest_currency', $coin2], ['main', 1]])
            ->orWhere([['type', TransactionType::EXCHANGE], ['src_currency', $coin2], ['dest_currency', $coin1], ['main', 1]])
            ->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function getMyTrades(Request $request)
    {

        $input = $request->input();
        $coin1 = $input['coin1'];
        $coin2 = $input['coin2'];

        $user = Auth::user();

        $transactions = Transaction::where([['user_id', $user->id], ['type', TransactionType::EXCHANGE], ['src_currency', $coin1], ['dest_currency', $coin2]])
            ->orWhere([['user_id', $user->id], ['type', TransactionType::EXCHANGE], ['src_currency', $coin2], ['dest_currency', $coin1]])
            ->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function getMyOpenOrders(Request $request)
    {
        $input = $request->input();
        $coin1 = $input['coin1'];
        $coin2 = $input['coin2'];


        $user = Auth::user();

        $orders = Order::where([['user_id', $user->id], ['src_currency', $coin1], ['dest_currency', $coin2]])
            ->orWhere([['user_id', $user->id], ['src_currency', $coin2], ['dest_currency', $coin1]])
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function deleteOpenOrder(Request $request)
    {
        $input = $request->input();
        $id = $input['id'];

        $order = Order::where('id', $id)->first();
        $order->delete();

        $order_type = $order->type;

        if ($order_type == MarketOrderType::BUY) {
            event(new OrderEvent(array(
                'type' => 'deleteOrder',
                'order_type' => $order_type,
                'amount' => $order->amount,
                'selPairCurrency' => $order->src_currency,
                'selPairCoin' => $order->dest_currency,
                'rate' => $order->rate,
                'time' => Carbon::now()->toDateTimeString()
            )));
        } else if ($order_type == MarketOrderType::SELL) {
            event(new OrderEvent(array(
                'type' => 'deleteOrder',
                'order_type' => $order_type,
                'amount' => $order->amount,
                'selPairCurrency' => $order->dest_currency,
                'selPairCoin' => $order->src_currency,
                'rate' => $order->rate,
                'time' => Carbon::now()->toDateTimeString()
            )));
        }

        return response()->json([
            'success' => true,
        ]);
    }



}
