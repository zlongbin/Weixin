<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WeixinController extends Controller
{
    //
    public function weixin(){
        echo $GET['echostr']; 
    }
    // public function weixin(){
    //     echo $GET['echostr']; 
    // }
}
