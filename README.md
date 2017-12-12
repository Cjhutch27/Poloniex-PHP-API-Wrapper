# Poloniex-PHP-API-Wrapper
Wrapper class written in php for Poloniex API  
Originally written by compcentral and revised to add functionality and easier usage
# Installation  
To add the Poloniex helper class into your existing project require or include it.
```  
include 'poloniexClass.php';
```  
# Initialization 
To initialize a Poloniex class object use   
```   
$poloniex = new Poloniex("Your API Key","Your API Secret");   
```   
From there, all public member functions can be accessed as such 
```  
$poloniex->returnBalances();   
```  

