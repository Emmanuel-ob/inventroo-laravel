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
      $homeLink = asset('/storage');
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
      $homeLink = asset('/storage');  
      $checker = Storage::put($location, File::get($file));
     
      // $sizes = cc('image.' . str_replace('/', '', $path));
      // // perform resize
      // foreach ($sizes as $prefix => $dimension) {
      //     $sizeName = $prefix . $fileName;
      //     if (extension_loaded('exif')) {
      //         $img = Image::make($file)->orientate();
      //     } else {
      //         $img = Image::make($file);
      //     }
      //     $resizedImg = $img->encode(null, cc('image.quality'))->fit(
      //         $dimension[0],
      //         $dimension[1],
      //         function ($constraint) {
      //             //$constraint->aspectRatio();
      //             $constraint->upsize();
      //         }, cc('image.focus')
      //     );
      //     Storage::put($path . $id . '/' . $sizeName, $resizedImg->stream());
      //     $img->destroy();
      // }
         
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


  public function deleteImage($imagePath)
  {
    $homeLink = asset('/storage').'/'; 
    if (!is_null($imagePath)) {
      if (is_array($imagePath)) {
        $newArr = [];
        foreach ($imagePath as $path) {
          $newLink = str_replace($homeLink, '', $path);
          $newArr[] = $newLink;
        }
        //return $newArr;
        if (count($newArr) > 0) {
          Storage::delete($newArr);
          return true;
        }
        return false;
      }else{
        $newLink = str_replace($homeLink, '', $imagePath);
        if(Storage::exists($newLink)){
          Storage::delete($newLink);
          /*
              Delete Multiple File like this way
              Storage::delete(['upload/test.png', 'upload/test2.png']);
          */
             return true;
        }else{
            return false;
        }
      }
    }
    return null;
  }

  public function removeImg($id, $sub_path)
  {
      // remove from file system
    if(Storage::deleteDirectory($sub_path.$id))
        return True;
      return False;
      

  }

  public function saveImgArray($file, $path, $id, $files = []){
      $image_array = array();           
      //$subName  = time() . '.';
      $fileName = time() . '.' . $file->getClientOriginalExtension();
      $location = $path . $id . '/' . $fileName;
      $homeLink = asset('/storage'); 
      $primary_link = $homeLink . $location;
      $image_array[] = $primary_link; 
      $checker = Storage::put($location, File::get($file));
         
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
        $other_image_link = $homeLink . $location;
        //$image_array[] = $other_image_link;
        $ichecker = Storage::put($location, $resizedImg->stream());
        if ($ichecker) {
          $image_array[] = $other_image_link;
        }
        $img->destroy();
        $i++;
      }

      if($checker){
        return $image_array;
      }
      return null;
    }
    
    public function saveOptionalImgArray($files, $path, $id){
      if (is_array($files)) {
        $homeLink = asset('/storage'); 
        $image_array = []; 
        $i = 1;
        foreach($files as $filee){
          $fileName = time() . '.' . $filee->getClientOriginalExtension();
          $img = Image::make($filee);
          $resizedImg = $img->encode(null, cc('image.quality'))->fit(
                800,
                504,
                function ($constraint) {
                    $constraint->upsize();
                }, cc('image.focus')
            );
          $fileNamee = $i . '_' . $fileName;
          $location = $path . $id . '/others/' . $fileNamee;
          $other_image_link = $homeLink . $location;
          $ichecker = Storage::put($location, $resizedImg->stream());
          if ($ichecker) {
            $image_array[] = $other_image_link;
          }
          $img->destroy();
          $i++;
        }

        if($ichecker){
          return $image_array;
        }
        return false;
      }
      return false;
    }


    // public function saveDocument($fileArray, $path, $id){
    //   $linkArray = array();
    //   foreach ($fileArray as $file) {
    //     $location = $path . $id . '/' . $file->getClientOriginalName();
    //     $checker = Storage::put($location, File::get($file));
    //     $linkArray[] = $location;
    //   }
       
    //   if($checker){
    //     return $linkArray;
    //   }
    //  return [];

    // }

    public function saveDocument($fileArray, $path, $id){
      $linkArray = array();
      $homeLink = asset('/storage');
      if (is_array($fileArray)) {
        foreach ($fileArray as $file) {
          $location = $path . $id . '/' . $file->getClientOriginalName();
          $checker = Storage::put($location, File::get($file));
          $linkArray[] = $homeLink . $location;
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