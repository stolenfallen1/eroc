<?php

    function storeBase64Document($file, $path){

        if(!$file){
            return null;
        }
        $random = generateRandomString();
        list($type, $file) = explode(';', $file);
        list(, $file)      = explode(',', $file);
        $data = base64_decode($file);
        $name =  date("YmdHis").$random.'.png';

        if(!file_exists(public_path($path))){
            mkdir(public_path($path), 0777, true);
        }
        file_put_contents(public_path() . '/' . $path . '/' . $name, $data);
        return $path . '/' . $name;
        // return  asset('').$path .'/' . $name ;

    }

    function storeDocument($file, $path, $index=0){
        if (empty($file)) return '';
        if (!file_exists(public_path($path))){
            mkdir(public_path($path), 0777, true);
        }

        $name = $index.time().'_'.$file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        // $name = $name.'.'.$ext;
        // $image_path = 'public/'.$path.'/'.$name;

        // file_put_contents(public_path() . '/' . $path . '/' . $name, $file);
        $file->move(public_path($path), $name);
        // $status =Storage::put($image_path, $file);
        
        return ['/'. $path .'/' . $name, $ext, $file->getClientOriginalName()] ;
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function uploadPhotos($id=0,$img = null, $path = null)
    {
        if($img == null){
            return null;
        }
        $imageName = "";
        try {
            $random = generateRandomString();
            $image = $img;  // your base64 encoded
            list($type, $image) = explode(';', $image);
            list(, $image)      = explode(',', $image);
            $data = base64_decode($image);
            $imageName = date("YmdHis").$random.'.jpeg';

            if (!file_exists(public_path($path))){
                mkdir(public_path($path), 0777, true);
            }

            file_put_contents(public_path() . '/' . $path . $imageName, $data);

        } catch (\Exception $e) {}

        return $imageName;
    }

    function generateCompleteSequence($prefix, $number, $suffix, $delimeter=""){
        $sq = $number;
        if($prefix){
            $sq = $prefix . $delimeter . $number;
        }
        if($suffix){
            $sq = $sq . $delimeter . $suffix;
        }
        return $sq;
    }
?>