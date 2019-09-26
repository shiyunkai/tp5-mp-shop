<?php


namespace app\api\controller\v1;

use app\api\model\Category as CategoryModel;


class Category
{

    public function getAllCategories(){
        // 等同于 $categories = CategoryModel::with('img')->select();
        $categories = CategoryModel::all([],'img');
        if($categories->isEmpty()){
            throw new CategoryException();
        }
        return $categories;
    }
}