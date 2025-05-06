<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;

class LevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    

    public function addLevel(Request $request)
    {
        $this->validate($request, [
            'ar_name' => 'bail|required|min:4',
            'en_name' => 'bail|required'
        ]);

        $level = new Level();

        $level->ar_name = $request->ar_name;
        $level->en_name = $request->en_name;

        $flag = $level->save();

        if ($flag) {
                return response()->json("", 201);
            }
        else {
            return response()->json("", 500);
        }
    }
    
    public function getLevels(Request $request) {
         
        if($request->level_id) {
            $levels = Level::where('id',$request->level_id)->withCount('classes')->paginate(10);
        return $levels;
        }
        else {
            $levels = Level::where('ar_name' , 'like' , '%'.$request->name.'%')->withCount('classes')->paginate(10);
        return $levels;
            
        }
    }
    
    public function editLevel(Request $request) {
        $this->validate($request , [
            'level_id' => 'bail|required|exists:levels,id',
            'level_name' => 'bail|required|min:4',
        ]);

        $level = Level::find($request->level_id) ;
        $level->ar_name = $request->level_name;

        $flag = $level->save();

        if($flag) {
            return response()->json("",200);
        }
    }
    
    
}
