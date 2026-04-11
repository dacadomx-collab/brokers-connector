<Publicaciones>
    @foreach ($properties as $property)
    <Inmueble>
        
        <IdInmueble>{{$property->id}}</IdInmueble>
        <TipoInmueble>{{$property->lamudi_type}}</TipoInmueble>
        @if ($property->key)
            <ClaveInmueble>{{$property->key}}</ClaveInmueble>
        @endif
        <FechaAlta>{{$property->created_at}}</FechaAlta> 
        <FechaUltimaActualizacion>{{$property->updated_at}}</FechaUltimaActualizacion>
        <Titulo>{{$property->title}}</Titulo>
        <Descripcion>{{$property->description}}</Descripcion>
        <Operacion>{{$property->lamudi_status}}</Operacion>
        <Precio>{{$property->price}}</Precio>
        <PrecioTipoMoneda>{{$property->currency_attr}}</PrecioTipoMoneda>
        <NumeroBanios>{{$property->baths}}</NumeroBanios>
        <NumeroDormitorios>{{$property->bedrooms}}</NumeroDormitorios>
        <MetrosDeConstruccion>{{$property->built_area ? $property->built_area : 0}}</MetrosDeConstruccion>
        <MetrosDeTerreno>{{$property->built_area ? $property->built_area : 0}}</MetrosDeTerreno>
        <Amueblado>{{$property->features()->where("name","Amueblado")->count() ? 1 : 0 }}</Amueblado>
        
        {{-- Dirección --}}
        <Pais>México</Pais>
        <Provincia>{{$property->local->city->state->name}}</Provincia> 
        <Ciudad>{{$property->local->city->name}}</Ciudad> 
        <Localidad>{{$property->local->name}}</Localidad>
        @if ($property->zipcode)
            <CodigoPostal>{{$property->zipcode}}</CodigoPostal> 
        @endif
        <LocalidadVisible>SI</LocalidadVisible> 
        <Latitud>{{$property->lat}}</Latitud> 
        <Longitud>{{$property->lng}}</Longitud>  

        {{-- Imagenes --}}
        <Imagenes>
            @foreach ($property->imagesAPI() as $image)
                <Imagen> 
                    <URLImagen>{{$image}}</URLImagen> 
                </Imagen>       
            @endforeach
        </Imagenes>

        {{-- Empresa --}}
        <Empresa> 
            <Nombre>{{$property->company->name}}</Nombre> 
            <Telefono>{{$property->company->phone}}</Telefono> 
            <Correo>{{$property->company->email}}</Correo> 
        </Empresa> 

    </Inmueble>

    @endforeach
</Publicaciones>

