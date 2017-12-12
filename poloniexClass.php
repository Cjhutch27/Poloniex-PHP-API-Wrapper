<?php
  
/**
 * Revised Poloniex API Helper (PHP); originally created by compcentral
 * @link https://pastebin.com/iuezwGRZ
 *
 * Full documentation can be found at 'https://poloniex.com/support/api/'
 *
 * @category  API
 * @author    Christopher Hutchison <chrishutchison.dev@gmail.com>
 * @copyright Copyright (c) 2010-2017
 * @link      https://github.com/Cjhutch27/PHP-MySqli-WrapperClass
 * @version   1.0
 */
        class Poloniex {
                protected $api_key = "YOUR-API-KEY-HERE";
                protected $api_secret = "YOUR-API-SECRET-HERE";

                protected $trading_url = "https://poloniex.com/tradingApi";
                protected $public_url = "https://poloniex.com/public";
               
                public function __construct($api_key = null, $api_secret = null) 
                {
                        if($api_key != null && $api_secret != null){
                        $this->api_key = $api_key;
                        $this->api_secret = $api_secret;
                        }
                }
                       
                private function query(array $req = array()) {
                        $key = $this->api_key;
                        $secret = $this->api_secret;
                        if($key == null || $secret == null)
                                return array("Error"=>"API key or secret is not set, specify them to access non-public calls");
                 
                        $mt = explode(' ', microtime());
                        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);

                        $post_data = http_build_query($req, '', '&');
                        $sign = hash_hmac('sha512', $post_data, $secret);

                        $headers = array(
                                'Key: '.$key,
                                'Sign: '.$sign,
                        );
 
                        static $ch = null;
                        if (is_null($ch)) {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_USERAGENT,
                                        'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
                                );
                        }
                        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                        $res = curl_exec($ch);
 
                        if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
                
                        $dec = json_decode($res, true);
                        if (!$dec){
                                return false;
                        }else{
                                return $dec;
                        }
                }
               
                protected function retrieveJSON($URL) 
                {
                        $opts = array('http' =>array('method'  => 'GET','timeout' => 10));
                        $context = stream_context_create($opts);
                        $feed = file_get_contents($URL, false, $context);
                        $json = json_decode($feed, true);
                        return $json;
                }

                /**  
                * Method that returns balances
                *
                * @param $hideZero boolean indicating if wanting to return balances with 0 or not
                * @param $symbol the currency trade symbol if only one is desired
                *
                * @return array containing balances
                */
                public function returnBalances($hideZero = false,$symbol = null) 
                {       
                        if(!$hideZero && $symbol == null){
                                return $this->query(array('command' => 'returnBalances'));
                        }else{
                        $balances = $this->query(array('command' => 'returnBalances'));
                                foreach ($balances as $key => $value) {
                                        if(strtoupper($symbol) == $key && $symbol != null)
                                                return $balances[$key];
                                        if(floatval($value) <= 0.0 && $hideZero)
                                                unset($balances[$key]);
                                }
                        }
                        return $balances;
                }

                /**  
                * Method that returns
                *
                * @param $hideZero boolean indicating if wanting to return balances with 0 or not
                * @param $symbol the currency trade symbol if only one is desired
                * @param $accounts a specific account to return defaults to all if left blank
                *
                * @return array containing balances
                */
                public function returnCompleteBalances($hideZero = false,$symbol = null,$accounts = null)
                {
                $bals;
                if($accounts != null){
                        if(!$hideZero && $symbol == null)
                                return $this->query(array('command' => 'returnCompleteBalances', 'account' => $accounts));
                        else
                              $bals = $this->query(array('command' => 'returnCompleteBalances', 'account' => $accounts));        
                }else {
                        if(!$hideZero && $symbol == null)
                                 return $this->query(array('command' => 'returnCompleteBalances'));
                         else
                                $bals = $this->query(array('command' => 'returnCompleteBalances'));
                }
                        foreach ($bals as $key => $value) {
                                foreach ($value as $key2 => $value2) {
                                        if($symbol != null && $key == strtoupper($symbol))
                                                return $bals[strtoupper($symbol)];
                                           if($key2 == "available" && floatval($value2) <= 0.0)
                                                unset($bals[$key]);
                                    }
                              }
                              return $bals;
                }

                /**  
                * Method for viewing deposit adresses
                *
                * @param $symbol the currency trade symbol 
                *
                * @return string containing address
                */
                public function returnDepositAddresses($symbol = null)
                {
                        $adresses = $this->query(array('command' => 'returnDepositAddresses'));
                        if($symbol == null){
                                 return $adresses;
                         }else{
                                foreach ($adresses as $key => $value) {
                                        if($key == $symbol)
                                                return $value;
                                }
                                return "Could not find adress for given symbol";
                         }
                }

                /**  
                * Method for generating a new wallet for a particular currency
                *
                * @param $symbol the currency trade symbol to generate a wallet for
                */
                public function generateNewAddress($symbol)
                {
                        return $this->query(array('command' => 'generateNewAddress', 'currency' => $symbol));     
                }

                /**  
                * Method for getting your deposit history
                *
                * @param $start the unix timestamp for start of time range
                * @param $end the unix timestamp for end of time range
                * @param $deposits a boolean indicating if return should be deposits or withdrawls
                *
                * @return array
                */
                public function returnDeposits($start,$end,$deposits = true)
                {
                $history = $this->query(array('command' => 'returnDepositsWithdrawals','start' => $start, 'end' => $end));
                        foreach ($history as $key => $value) {
                                if($deposits && $key == "deposits")
                                        return array("deposits" => $value);
                                else if (!$deposits && $key == "withdrawals")
                                        return array("withdrawals" => $value);
                        }
                return array();
                }

                /**  
                * Method for getting your withdrawl history
                *
                * @param $start the unix timestamp for start of time range
                * @param $end the unix timestamp for end of time range
                *
                * @return array
                */
                public function returnWithdrawals($start,$end)
                {
                        return $this->returnDeposits($start,$end,false);        
                }

                /**  
                * Method for fetching all open orders
                *
                * @param $pair the trade symbol of currency orders to grab
                *
                * @return array
                */
                public function returnOpenOrders($pair = 'all') 
                {               
                return $this->query(array('command' => 'returnOpenOrders',
                                                'currencyPair' => strtoupper($pair)));
                }

                /**  
                * Method for fetching all open buy orders
                *
                * @param $pair the trade symbol of currency orders to grab
                * @param $type only used if want to inverse and grab sell orders, otherwise leave param blank
                *
                * @return array
                */
                public function returnOpenBuyOrders($pair,$type = 'buy')
                {
                       $requested = array();
                       $orders = $this->returnOpenOrders($pair);
                       foreach ($orders as $key => $value) {
                               if (count($value) != 0) {
                                      foreach ($value as $key2 => $value2) {
                                              if(strtoupper($value2) == strtoupper($type))
                                                        array_push($requested, $value);
                                      }
                               }
                       }
                       return $requested;
                }

                /**  
                * Method for fetching all open sell orders
                *
                * @param $pair the trade symbol of currency orders to grab
                *
                * @return array
                */
                public function returnOpenSellOrders($pair)
                {
                       return $this->returnOpenBuyOrders($pair,'sell');
                }
               
                /**  
                * Method for fetching all trade history
                *
                * @param $pair the trade symbol of currency orders to grab
                * @param $limit optional parameter for the limit for how many entries to grab
                * @param $start & $end optional parameters for start and end period to fetch from in UNIX time
                *
                * @return array
                */
                public function returnTradeHistory($pair = 'all',$limit = 500,$start = null, $end = null) 
                {
                        if($limit > 10000)
                                return array("Error"=>"Limit cannot be greater than 10,000");
                        if($start == null && $end == null){
                        return $this->query(array('command' => 'returnTradeHistory',
                                        'currencyPair' => strtoupper($pair),
                                        'limit' => intval($limit)));
                        } else{
                         return $this->query(array('command' => 'returnTradeHistory',
                                        'currencyPair' => strtoupper($pair),
                                        'start' => intval($start),
                                        'end' => intval($end),
                                        'limit' => intval($limit)));    
                        }
                }
               
                /**  
                * Method for creating a buy order
                *
                * @param $pair the trade symbol of currency orders to grab
                * @param $rate the trade rate
                * @param $amount amount to order
                *
                * @return array
                */
                public function buy($pair, $rate, $amount) 
                {
                        return $this->query( array('command' => 'buy',    
                                        'currencyPair' => strtoupper($pair),
                                        'rate' => $rate,
                                        'amount' => $amount));
                }
               
                /**  
                * Method for creating a sell order
                *
                * @param $pair the trade symbol of currency orders to grab
                * @param $rate the trade rate
                * @param $amount amount to order
                *
                * @return array
                */
                public function sell($pair, $rate, $amount) 
                {
                        return $this->query(array('command' => 'sell',   
                                        'currencyPair' => strtoupper($pair),
                                        'rate' => $rate,
                                        'amount' => $amount));
                }
               
                /**  
                * Method for cancelling a buy or sell order
                *
                * @param $pair the trade symbol of currency orders to grab
                * @param $order_number the order number to cancel
                *
                * @return array
                */
                public function cancelOrder($pair, $order_number) 
                {
                        return $this->query(array('command' => 'cancelOrder',    
                                        'currencyPair' => strtoupper($pair),
                                        'orderNumber' => $order_number));
                }
               
                /**  
                * Method for withdrawing an amount from your wallet to another
                *
                * @param $currency the trade symbol of currency orders to grab
                * @param $amount the amount to withdraw
                * @param $address the address to send to
                *
                * @return array
                */
                public function withdraw($currency, $amount, $address) {
                        return $this->query(array('command' => 'withdraw',       
                                        'currency' => strtoupper($currency),                           
                                        'amount' => $amount,
                                        'address' => $address));
                }
               
                /**  
                * Method for returning order books
                *
                * @param $pair currency pair
                * @param $limit the limit of orders to return
                *
                * @return array
                */
                public function returnOrderBook($pair = 'all',$limit = 20) 
                {
                        $orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair)."&depth=".$limit);
                        return $orders;
                }

                /**  
                * Method for returning buy order books
                *
                * @param $pair currency pair
                * @param $limit the limit of orders to return
                * @param $type specifies buy or sell orders to return
                *
                * @return array
                */
                public function returnBuyOrderBook($pair = 'all',$limit = 20,$type = 'bids')
                {
                $requested = array();
                $orders =  $this->returnOrderBook($pair,$limit);
                if(strtoupper($pair) == "ALL"){
                        foreach ($orders as $key => $value) {
                                array_push($requested, array($key=>$value[$type]));
                        }
                return $requested;
                }else{
                        return $orders[$type]; 
                }

                }

                /**  
                * Same as returnBuyOrderBook()
                */
                public function returnSellOrderBook($pair = 'all',$limit = 20)
                {
                        return $this->returnBuyOrderBook($pair,$limit,'asks');
                }
               
                /**  
                * Method for fetching 24 hr volume of a currency
                *
                * @param $pair currency trade symbol
                *
                * @return array
                */
                public function return24Volume($pair = "all") 
                {
                        $volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
                        if(strtoupper($pair) == "ALL")
                                return $volume;
                        $pair = strtoupper($pair);
                        if(isset($volume[strtoupper($pair)]))
                                return $volume[strtoupper($pair)];
                        else
                                return array();
                }

                /**  
                * Method for fetching chart data for a specified currency
                *
                * @param $pair currency trade symbol
                * @param $period the period in seconds for each candlestick
                * @param start & end parameters for start and end period in UNIX time
                *
                * @return array
                */
                public function returnChartData($pair,$period,$start,$end)
                {
                        $validPeriod = false;
                        $acceptablePeriods = array( 300, 900, 1800, 7200, 14400, 86400);
                        foreach ($acceptablePeriods as $key => $value) {
                                if($value == $period)
                                        $validPeriod = true;
                        }
                        if(!$validPeriod)
                                return array("Error"=>"Invalid candlestick period");
                        return $this->retrieveJSON($this->public_url.'?command=returnChartData&currencyPair='.strtoupper($pair).'&start='.$start.'&end='.$end.'&period='.$period);
                }
       
                /**  
                * Method for fetching ticker for a specified market
                *
                * @param $pair currency trade symbol
                *
                * @return array
                */
                public function returnTicker($pair = "all") 
                {
                        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
                        if(strtoupper($pair) == "ALL")
                                return $prices;
                         $pair = strtoupper($pair);
                                if(isset($prices[strtoupper($pair)]))
                                        return $prices[strtoupper($pair)];
                                else
                                        return array(); 
                }

                /**  
                * Method for fetching all tradeable currency pairs
                *
                * @return array
                */
                public function returnAllTradingPairs() 
                {
                        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
                        return array_keys($tickers);
                }
               
        


        }
        
?>