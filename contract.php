<?php
require_once 'ethereum.php';
$ethereum = new Ethereum('127.0.0.1', '8545');      //以太坊类
$conAddr = "0x063b528c344a63648dbb65c0ecef5068d64adcd1";        //合约地址
$addr = $ethereum->eth_accounts()[0];   //执行合约的地址
// $ethereum->net_version();

/*
 * $orderid为为字典映射的下标做索引
 * $data传入四个参数，string jydh,string name,uint add,string je
 * 第三个参数，金额应为int类型
 */
function insertVal($orderid, $_data = array())
{
    global $ethereum,$conAddr,$addr;
    $ethereum->personal_unlockAccount($addr, 'yan');
    $funName = '0x3e2c20bd000000000000000000000000000000000000000000000000000000000000008000000000000000000000000000000000000000000000000000000000000000c0';            //执行函数的字节码
    
    $data = '';
    $res = '';
    $res1 = '';
    $_str = '0000000000000000000000000000000000000000000000000000000000000000';
    /**************数据转码****************/
    foreach ($_data as $v)
    {
        $_res ='' ;
        $code1 ='';
        if(is_numeric($v))
        {
            $_res = dechex($v);
            $code1 = substr($_str, 0,64-strlen($_res)).$_res.'0000000000000000000000000000000000000000000000000000000000000100';
        }
        else
        {
            for($i = 0;$i<strlen($v); $i++){
                $_res .= dechex(ord($v[$i]));                
            }
            $code = substr($_str, 0,64-strlen(strlen($_res)/2)).strlen($_res)/2 .$_res.substr($_str,0 ,64-strlen($_res));
        }        
        $res1 .= $code1;
        $res .= $code;        
    }
    $data .= $funName.$res1.$res;
    $ethTran = new Ethereum_Transaction($addr, $conAddr, '0x47b760','0xff47b760',null,$data);
    $tranHash = $ethereum ->eth_sendTransaction($ethTran);

//     $ethereum->personal_lockAccount($addr); //账户加锁
    
    return $tranHash;
}



/*
 * getVal作为查询使用，传入订单id返回值为数组
 * 第一个值为金额
 */

function getVal($orderid)
{
    global $ethereum,$conAddr,$addr;
    $funName = '0xf8b99190';    
    $_str = '0000000000000000000000000000000000000000000000000000000000000000';
    $data_fix = '0000000000000000000000000000000000000000000000000000000000000020'.substr($_str, 0,64-strlen(strlen($orderid))).strlen($orderid);
    
    $data = '';
    $_res = '';
    $res = '';
    for($i = 0;$i < strlen($orderid); $i++){
        $_res .= dechex(ord($orderid[$i]));
        $str = substr($_str, 0,64-strlen($_res));
    }
    $res .= $_res.$str;
    $data .= $funName.$data_fix.$res;
    $ethCall = new Ethereum_Message($addr, $conAddr, '0x47b760','0xff47b760', '0x0',$data);
    $_result = $ethereum->eth_call($ethCall, 'latest');
    
    $_result = substr($_result, 2,strlen($_result));
    
    if((strlen($_result)/64)%2 ==1)             //处理返回值不成对
    {
        $_result = $_str.$_result;
    }
    $nums = strlen($_result)/64;

    /*************返回值解码*******************/
    $data = array();
    for($i = 1;$i < $nums/2; $i++){
        $result = substr($_result, $i*128,128);         //成对截取返回值    位数+值
        if(ltrim(substr($result, 64,128),0) == 'a0')      //判断是否数字，代码需改
        {
            $result1 = hexdec(ltrim(substr($result,0 ,64),0));
            array_push($data, $result1);
        }
        else 
        {
            $result2 = '';
            $result = rtrim(substr($result, 64,128),0);
            for($j = 0;$j < strlen($result);$j+=2){
                $resHex = substr($result, $j,2);
                $result2 .= chr(hexdec($resHex));
            }
            array_push($data, $result2);
        }        
    }
    return $data;
}
