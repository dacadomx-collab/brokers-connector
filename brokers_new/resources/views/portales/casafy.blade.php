
    <ads>
        @foreach ($properties as $property)
        <ad>
          <id><![CDATA[{{$property->id}}]]></id>
          <url><![CDATA[{{'https://brokersconnector.com/property/info/'.$property->id}}]]></url>
          <title><![CDATA[{{$property->title}}]]></title>
          <content><![CDATA[{{$property->description}}]]></content>
          <type><![CDATA[{{ $property->casafy_status }}]]></type>
          <property_type><![CDATA[{{ $property->casafy_type }}]]></property_type>
          <address><![CDATA[{{$property->address}}]]></address>
          <region><![CDATA[{{$property->local->city->name}}]]></region>
          <agency>
            <id><![CDATA[{{ $property->company->id }}]]></id>
            <name><![CDATA[{{ $property->company->name }}]]></name>
            <phone><![CDATA[{{ $property->company->phone }}]]></phone>
            <email><![CDATA[{{ $property->company->email }}]]></email>
            <address><![CDATA[{{ $property->company->address }}]]></address>
            {{-- <city_area><![CDATA[Palermo]]></city_area> --}}
            {{-- <city><![CDATA[Ciudad de Buenos Aires]]></city> --}}
            {{-- <region><![CDATA[Ciudad de Buenos Aires]]></region> --}}
            <country><![CDATA[Mexico]]></country>
            <logo_url><![CDATA[{{ 'https://brokersconnector.com'.$property->company->logo }}]]></logo_url>
          </agency>
          <price currency="{{$property->currency_attr}}"><![CDATA[{{$property->price}}]]></price>
         
          <pictures>
            @foreach ($property->imagesAPI() as $index => $image)
            <picture>
              <picture_url><![CDATA[{{$image}}]]></picture_url>
              <picture_title><![CDATA[{{$property->title.' '.$index}}]]></picture_title>
            </picture>
            @endforeach
          </pictures>

          {{-- <date><![CDATA[2009-08-31]]></date> --}}
          <city_area><![CDATA[{{$property->local->name}}]]></city_area>
          <city><![CDATA[{{$property->local->city->name}}]]></city>
          <postcode><![CDATA[{{$property->zipcode}}]]></postcode>
          <floor_number><![CDATA[{{ $property->floor }}]]></floor_number>
          <latitude><![CDATA[{{ $property->lat }}]]></latitude>
          <longitude><![CDATA[{{ $property->lng }}]]></longitude>
          {{-- <orientation><![CDATA[north]]></orientation> --}}
          {{-- <floor_area><![CDATA[210]]></floor_area> --}}
          {{-- <floor_area_open><![CDATA[40]]></floor_area_open> --}}
          @if ($property->total_area)
          <plot_area><![CDATA[{{ $property->total_area }}]]></plot_area>
          @endif
          @if ($property->bedroooms)
          <rooms><![CDATA[{{ $property->bedroooms }}]]></rooms>
          @endif
          @if ($property->baths)
          <bathrooms><![CDATA[{{ $property->baths }}]]></bathrooms>
          @endif
          @if ($property->antiquity)
          <year><![CDATA[{{ $property->antiquity }}]]></year>
          @endif
          {{-- <blueprint_url><![CDATA[http://foo.com/realestate/11aa/blueprint.png]]></blueprint_url> --}}
          <is_furnished><![CDATA[{{$property->hasFeature(6)}}]]></is_furnished>
          {{-- <is_new><![CDATA[0]]></is_new> --}}
          <parking><![CDATA[{{ $property->parking_lots ? 1 : 0 }}]]></parking>
          <patio><![CDATA[{{$property->hasFeature(33)}}]]></patio>
          <terrace><![CDATA[{{$property->hasFeature(38)}}]]></terrace>
          <balcony><![CDATA[{{$property->hasFeature(12)}}]]></balcony>
          {{-- <expiration_date><![CDATA[2009-11-30]]></expiration_date> --}}
          @if ($property->features->count())
          <features>
            @foreach ($property->features as $feature)
            <feature><![CDATA[{{ $feature->name }}]]></feature>
            @endforeach
          </features>
          @endif
        </ad>
        @endforeach
</ads>


