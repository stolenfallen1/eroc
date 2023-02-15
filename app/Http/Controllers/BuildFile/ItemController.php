<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Helpers\SearchFilter\Items;
use App\Http\Controllers\Controller;

class ItemController extends Controller
{
    public function searchItem(){
        return (new Items)->searchable();
    }
}
