<?php
namespace App\Repositories;
use Illuminate\Support\Facades\Storage;

use Image;
use Illuminate\Support\Facades\File;

class ImageUtils
{

      public function __construct(){

      }

    public function saveImage($file, $path){
                   
      
       $fileName = time() . '.' . $file->getClientOriginalExtension();
       $location = $path . $fileName;

       $checker = Storage::put($location, File::get($file));

        $sizes = cc('image.sizes');
        // perform resize
        foreach ($sizes as $prefix => $dimension) {
            $sizeName = $prefix . $fileName;
            if (extension_loaded('exif')) {
                $img = Image::make($file)->orientate();
            } else {
                $img = Image::make($file);
            }
            $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                $dimension[0],
                $dimension[1],
                function ($constraint) {
                    //$constraint->aspectRatio();
                    $constraint->upsize();
                }, cc('image.focus')
            );
            Storage::put($path . $sizeName, $resizedImg->stream());
            $img->destroy();
        }
         

       if($checker){
        return $fileName;
       }
       return '';
    }



    public function removeImage($imgDir, $path){
        $response1 = Storage::delete($path . $imgDir);
        $response2 = Storage::delete($path . getLargeImage($imgDir));
        $response3 = Storage::delete($path . getSmallImage($imgDir));
        $response4 = Storage::delete($path . getNormalImage($imgDir));
        if($response1)
           return $response1;
        
        return true;
        
    }

    public function saveImg($file, $path, $id, $files = []){
                   
         //$subName  = time() . '.';
         $fileName = time() . '.' . $file->getClientOriginalExtension();
       $location = $path . $id . '/' . $fileName;
        
       $checker = Storage::put($location, File::get($file));
     
        $sizes = cc('image.' . str_replace('/', '', $path));
        // perform resize
        foreach ($sizes as $prefix => $dimension) {
            $sizeName = $prefix . $fileName;
            if (extension_loaded('exif')) {
                $img = Image::make($file)->orientate();
            } else {
                $img = Image::make($file);
            }
            $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                $dimension[0],
                $dimension[1],
                function ($constraint) {
                    //$constraint->aspectRatio();
                    $constraint->upsize();
                }, cc('image.focus')
            );
            Storage::put($path . $id . '/' . $sizeName, $resizedImg->stream());
            $img->destroy();
        }
         
         $i = 1;
        foreach($files as $filee){
            $img = Image::make($filee);
            
          $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                1920,
                1080,
                function ($constraint) {
                    //$constraint->aspectRatio();
                    $constraint->upsize();
                }, cc('image.focus')
            );
            
          $fileNamee = $i . '_' . $fileName;
          $location = $path . $id . '/' . $fileNamee;
          $ichecker = Storage::put($location, $resizedImg->stream());
          $img->destroy();
          $i++;
        }

       if($checker){
        return $fileName;
       }
         return '';
    }


    public function removeImg($id, $category)
    {
        
        // remove from file system
      if(Storage::deleteDirectory($category.$id))
          return True;
        return False;
        

    }

    public function saveImgArray($file, $path, $id, $files = []){
         
        $image_array = array();           
         //$subName  = time() . '.';
         $fileName = time() . '.' . $file->getClientOriginalExtension();
       $location = $path . $id . '/' . $fileName;

       $image_array[] = $fileName; 
       $checker = Storage::put($location, File::get($file));
     
        $sizes = cc('image.' . str_replace('/', '', $path));
        // perform resize
        foreach ($sizes as $prefix => $dimension) {
            $sizeName = $prefix . $fileName;
            if (extension_loaded('exif')) {
                $img = Image::make($file)->orientate();
            } else {
                $img = Image::make($file);
            }
            $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                $dimension[0],
                $dimension[1],
                function ($constraint) {
                    //$constraint->aspectRatio();
                    $constraint->upsize();
                }, cc('image.focus')
            );
            Storage::put($path . $id . '/' . $sizeName, $resizedImg->stream());
            $img->destroy();
        }
         
         $i = 1;
        foreach($files as $filee){
            $img = Image::make($filee);
            
          $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                800,
                504,
                function ($constraint) {
                    //$constraint->aspectRatio();
                    $constraint->upsize();
                }, cc('image.focus')
            );
            
          $fileNamee = $i . '_' . $fileName;
          $location = $path . $id . '/others/' . $fileNamee;

          $image_array[] = $location;
          $ichecker = Storage::put($location, $resizedImg->stream());
          $img->destroy();
          $i++;
        }

       if($checker){
        return $image_array;
       }
         return '';
    }


    public function saveDocument($fileArray, $path, $id){
      $linkArray = array();
      $homeLink = asset('/storage');
      if (is_array($fileArray)) {
        foreach ($fileArray as $file) {
          $location = $path . $id . '/' . $file->getClientOriginalName();
          $checker = Storage::put($location, File::get($file));
          $linkArray[] = $location;
        }
      }else{
        $location = $path . $id . '/' . $fileArray->getClientOriginalName();
        $checker = Storage::put($location, File::get($fileArray));
        $linkArray[] =  $homeLink . $location;
      }
      
       
      if($checker){
        return $linkArray;
      }
     return [];

    }

    public function removeDocument($id, $category)
    {
        
        // remove from file system
      if(Storage::deleteDirectory($category.$id))
          return True;
        return False;
        

    }

    public function saveDoc($file, $path, $id){
      $location = $path . $id . '/' . $file->getClientOriginalName();
      $checker = Storage::put($location, File::get($file));

      
      if($checker){
        return $location;
      }
     return '';

    }

    

}