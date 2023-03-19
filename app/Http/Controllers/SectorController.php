<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Sector;

class SectorController extends Controller
{
    public function getAll(){
        $array = ['errors' => ''];
 
        $array['sectors'] = Sector::all();
        return $array;
    }

    public function insert(Request $request){
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'name'=>'required',
            //'arquivo' => 'required|mimes:jpg, png, pdf
        ]);

        if(!$validator->fails()){
            
            //$file = $request->file('photo')->store('public');

            $sector = new Sector();
            $sector->name = $request->input('name');
            $sector->save();

        }else {
           $array['error'] = $validator->errors()->first();
           return $array; 
        }

        return $array;
    }

    public function edit($id){
        $array = ['errors' => ''];
        $array['sectors'] = Sector::find($id);
        return $array;
    }

    public function update(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name'=>'required',
        ]);
        

        $sector = Sector::find($request->id);


        if(!$validator->fails()){
                       

            if(is_null($sector)){

                $array['errors'] = "sector has not found";
    
            } else {

            $sector->name = $request->input('name');
            $sector->save();

            }

        }else {
           $array['errors'] = $validator->errors()->first();
           return $array; 
        }

        return $array;
    }

    public function delete($id){
        $array = ['errors' => ''];

        $sector = Sector::find($id);

        if(is_null($sector)){

            $array['errors'] = "sector has not found";

        } else {

        $sector->delete();

        }
        return $array;
    }
}
