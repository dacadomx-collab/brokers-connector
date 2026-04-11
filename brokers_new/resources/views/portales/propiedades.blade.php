<Anuncios>
    @foreach ($properties as $property)
    <Anuncio>
        <idAnuncio>{{$property->title}}</idAnuncio>
        <URLexterno>{{'https://brokersconnector.com/property/info/'.$property->id}}</URLexterno>

        <descripcion><![CDATA[{{$property->description}}]]></descripcion>

        <InformacionLocalizacion>
            <direccionCompleta><![CDATA[{{$property->address}} {{$property->exterior}}, {{$property->local->name}}, {{$property->local->city->name}} {{$property->zipcode}}, 
                {{$property->local->city->state->name}}, México]]></direccionCompleta>
            <calle>{{$property->address}}</calle>
            <numeroExterno>{{$property->exterior ? $property->exterior : 'S/N'}}</numeroExterno>
            <numeroExterno>{{$property->interior ? $property->interior : 'S/N'}}</numeroExterno>
            <colonia>{{$property->local->name}}</colonia>
            <codigoPostal>{{$property->zipcode}}</codigoPostal>
            <ciudad>{{$property->local->city->name}}</ciudad>
            <estado>{{$property->local->city->state->name}}</estado>

        </InformacionLocalizacion>

        <Geolocalizacion>
            <latitud>{{$property->lat}}</latitud>
            <longitud>{{$property->lng}}</longitud>
        </Geolocalizacion>

        <pagos>
            <Pago>
                <Costo>
                    <precioTotal>{{$property->price}}</precioTotal>
                    <moneda>{{$property->currency_attr}}</moneda>
                </Costo>
                <tipoOperacion>{{$property->status->propiedades}}</tipoOperacion>
            </Pago>
        </pagos>

        <InformacionPropiedad>
            <tipoPropiedad>{{strtoupper($property->use->name)}}</tipoPropiedad>
            <categoriaPropiedad>{{$property->type->propiedades}}</categoriaPropiedad>
            <DetallesPropiedad>
                <tamanioConstruccion>{{$property->built_area ? $property->built_area : 0}}</tamanioConstruccion>
                <tamanioTerreno>{{$property->total_area ? $property->total_area : 0 }}</tamanioTerreno>
                <pisos>{{$property->floor}}</pisos>
                <habitaciones>{{$property->bedrooms}}</habitaciones>
                <baniosCompletos>{{$property->baths_count}}</baniosCompletos>
                <mediosBanios>{{$property->medium_baths}}</mediosBanios>
                <numEstacionamientos>{{$property->parking_lots}}</numEstacionamientos>
                <anioConstruccion>{{$property->antiquity}}</anioConstruccion>
            </DetallesPropiedad>
        </InformacionPropiedad>

        <amenidades>
            @foreach ($property->features as $feature)<amenidad>{{str_replace(' ', '_', $feature->name)}}</amenidad>@endforeach
        </amenidades>

        <contactos>
            <Contacto>
                <nombreContacto>{{$property->agent_assignt->f_name_accents}}</nombreContacto>
                <telefonoContacto>{{$property->agent_assignt->phone}}</telefonoContacto>
                <emailContacto>{{$property->agent_assignt->email}}</emailContacto>
            </Contacto>
        </contactos>

        <recursosMultimedia>

            
            @foreach ($property->imagesAPI() as $image)
            <RecursoMultimedia>
                <URLdeImagen>{{$image}}</URLdeImagen>
            </RecursoMultimedia>
            @endforeach
        </recursosMultimedia>

    </Anuncio>
    @endforeach
</Anuncios>