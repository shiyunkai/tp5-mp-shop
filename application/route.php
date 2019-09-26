<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 动态注册路由
use think\Route;

Route::get('api/:version/banner/:id','api/:version.Banner/getBanner');

Route::get('api/:version/theme','api/:version.Theme/getSimpleList');

Route::get('api/:version/theme/:id','api/:version.Theme/getComplexOne');

// 限定id为正整数
//Route::get('api/:version/product/:id','api/:version.Product/getOne',[],['id'=>'\d+']);
Route::get('api/:version/product/recent','api/:version.Product/getRecent');
Route::get('api/:version/product/:id','api/:version.Product/getOne');

Route::get('api/:version/product/by_category','api/:version.Product/getAllInCategory');

/*// 路由分组
Route::group('api/:version/product', function (){
    Route::get('/by_category', 'api/:version.Product/getAllInCategory');
    Route::get('/recent','api/:version.Product/getRecent');

});*/

Route::get('api/:version/category/all','api/:version.Category/getAllCategories');

// code有安全性要求，所以使用post
Route::post('api/:version/token/user','api/:version.Token/getToken');

Route::post('api/:version/address','api/:version.Address/createOrUpdateAddress');


