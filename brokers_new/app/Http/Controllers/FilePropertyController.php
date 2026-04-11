<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;
use Exception;
use App\FileProperty;
use App\Property;
use File;



class FilePropertyController extends Controller
{
    public function store(Request $request)
    {
        
        $files=FileProperty::where("property_id", $request->property_id);
        $set_featured=false;
        

        if($request->type==3)
        {
            $urls=[];
            foreach ($request->enlace as $enlace) 
            {
                if($enlace)
                {
                    if($files->where("type",3)->get()->count() >= 5)
                    {
                        return response(["type_msg"=>1,"msg"=>"La propiedad ya tiene 5 enlaces de video de youtube"], 401);
                    }

                    if (!preg_match('@^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$@', $enlace, $match)) {
                        return response(["type_msg"=>1,"msg"=>"Ingresar una url de youtube valida ejemplo: https://www.youtube.com/watch?v=9DCdCoQ49u8"], 401);
                    }
                    
                    // ^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/.+
                    $imageUpload = new FileProperty();
                    
                    $imageUpload->property_id = $request->property_id;

                    if($posicion_coincidencia = strpos($enlace, 'watch?v='))
                    {
                        $imageUpload->src = str_replace('watch?v=', 'embed/', $enlace);
                    }
                    if($posicion_coincidencia = strpos($enlace, 'youtu.be'))
                    {
                        $imageUpload->src = str_replace('youtu.be', 'youtube.com/embed', $enlace);
                    }
                    
                    $imageUpload->type = $request->type;
                    
                    $imageUpload->save();
                    
                    array_push($urls,$imageUpload);
                }
            }

            return response(["type_msg"=>2,"msg"=>"Url's guardados","url"=>$urls],200);
            
        }
        else
        {
            $image = $request->file('file');
            $arrayName = explode('.', $image->getClientOriginalName());
            $name_file=$arrayName[0];
            $size=($image->getSize())/1000000;
            //get company id for route in public folder
            $companyId = auth()->user()->company->id;
            $path = 'companies/'.$companyId.date("/Y/m/d/");
            //get extension
            $extension = end($arrayName);

            $imageUpload = new FileProperty();
            $type_file = 1;
            
            

            //Subir videos
            if($extension=="mp4" || $extension=="avi" || $extension=="3gp")
            {
                if($size<=50)
                {
                    $type_file=4;
                    $files=$files->where("type",4)->first();
                   
                    if($files)
                    {
                        File::delete(public_path($files->src));
                        $imageUpload=$files;
                    }
                }
                else
                {
                    return response(["type_msg"=>1,"msg"=>"No se permiten video de más de 50mb"], 401);
                }
                
            }
            else if($extension=="pdf")
            {
                $type_file=5;
                $files->where("type",5)->get();
                if($size > 2)
                {
                    return response(["type_msg"=>1,"msg"=>"No se permiten imágenes de más de 2mb"], 401);
                }
                
                if($files->count() >= 15)
                {
                    return response(["type_msg"=>1,"msg"=>"No se permiten más de 15 archivos"], 401);
                }
            }
            else
            {
                $files->where("type",1)->get();
                if($size > 8)
                {
                    return response(["type_msg"=>1,"msg"=>"No se permiten imágenes de más de 8mb"], 401);
                }
                
                if($files->count() >= 20)
                {
                    return response(["type_msg"=>1,"msg"=>"No se permiten más de 20 imágenes"], 401);
                }

                if($files->count()==0)
                {
                    $set_featured=true;
                }

                
            }


            //name image uniq id
            $imageName = uniqid().'.'.$extension;
            if($request->type==5)
            {
                
                $imageName = $name_file."-".round(microtime(true) * 1000).".".$extension;
            }
            
          if (!file_exists(public_path($path))) {
                mkdir(public_path($path), 0755, true);
                chmod(public_path($path), 0755);
            }

           
            //store image
            $moved = $image->move(public_path($path), $imageName);
            
            chmod($moved, 0755);
    
            if ($moved) {
               
                // $imageUpload->name = $image->getClientOriginalName();
                 // 1 => 'image', 2 => 'pdf',
                $url=$path.$imageName;
                $imageUpload->property_id = $request->property_id;
                $imageUpload->src = '/'.$url;
                
                if($type_file==1)
                {
                     $path_to_save="";
                    if($extension=="jfif")
                    {
                        $path_to_save=$url;
                    }
                    else
                    {
                        
                       
                        //Make thumbnail
                        $img=Image::make($url);
                      
                        $width=$img->width();
                        $height=$img->height();
        
                        $image=$img;
                        if($width<$height)
                        {
                            $image=Image::canvas($height, $height, '#d3d3d3')->insert($img, 'center');
                        }
                    
                        $image->resize(300, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        
                        $path_to_save=$path.uniqid().'.'.$extension;
                        $image->save(public_path($path_to_save));
                        chmod($path_to_save, 0755);
                    }
    
                    $imageUpload->thumbnail="/".$path_to_save;
                }
                $imageUpload->type=$type_file;
                $imageUpload->index_order = round(microtime(true) * 1000);               
                $imageUpload->save();
                
                if($set_featured)
                {
                    $imageUpload->property->featured_image=$imageUpload->id;
                    $imageUpload->property->save();
                }

                if($request->type==5)
                {
                    return response(["type_msg"=>2,"array"=>[
                        "name"=>basename($imageUpload->src, ".pdf"),
                        "size"=>number_format(File::size(public_path($imageUpload->src))/1000,2),
                        "id"=>$imageUpload->id,
                        "src"=>$imageUpload->src,
                        "count"=>$files->where("type",5)->count(),
                        "date_format"=>$imageUpload->created_at->format("d/m/Y H:s")
                    ]],200);
                }
    
                return response(["type_msg"=>2,"msg"=>$url,"id"=>$imageUpload->id],200);
            }
            else 
            {
                return response(["type_msg"=>1,"msg"=>"Archivo no guardado, favor de internarlo de nuevo"], 401);
            }

        }

    }

    public function delete(Request $request)
    {
        //Buscar archivo
        $file=FileProperty::findOrFail($request->id);
        
        if($file->type==1)
        {
            //Encontrar imagen destacada en las propiedades
            $featured=Property::where("featured_image", $file->id)->first();
           
            //Quitar imagen destacada
            if($featured)
            {
                $featured->featured_image=null;
                $featured->save();
            }
        }
      
        //Mostrar mensaje en caso de no eliminar
        $type_msg="error";
        $msg="El archivo no fue eliminado con éxito";
        if($file)
        {
            //Eliminar el archivo y su thumbnail
            File::delete(public_path($file->src));
            File::delete(public_path($file->thumbnail));
            
            $file->delete();

            //Mostrar mensaje al eliminar exitosa
            $type_msg="success";
            $msg="El archivo fue eliminado con éxito";
        }

        if($file->type==5)
        {
            //Regresar tipo de mensaje y el cuerpo del mensaje en caso de un archivo pdf
            return response(["type_msg"=>$type_msg,"msg"=>$msg,"count"=>$file->count_files_pdf],200);
        }
        //Regresar tipo de mensaje y el cuerpo del mensaje
        return response(["type_msg"=>$type_msg,"msg"=>$msg],200);
    }


    public function deleteFromArray(Request $request){

        $property = Property::findOrFail($request->property_id);

        foreach ($request->images as $image_id) {
            $file=FileProperty::findOrFail($image_id);
            
            if ($property->featured_image == $file->id) {
                $property->featured_image=null;
                $property->save();
            }

            File::delete(public_path($file->src));
            File::delete(public_path($file->thumbnail));
            $file->delete();
        }

        return response()->json('ok', 200);

    }


    public function featured(Request $request)
    {

        $file=FileProperty::findOrFail($request->id);
        
        $featured_id=$file->property->featured_image;
        $file->property->featured_image=$file->id;
        $file->property->save();

        return response(["type_msg"=>2,"msg"=>"Imagen establecida como destacada", "featured_id"=>$featured_id],200);
    }
}


