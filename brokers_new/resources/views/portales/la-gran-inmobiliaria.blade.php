<ads>
    @foreach ($properties as $property)
   
    <ad>
        <id><![CDATA[{{$property->id}}]]></id>
        <url><![CDATA[{{'https://brokersconnector.com/property/info/'.$property->id}}]]></url>
        <type><![CDATA[{{$property->type->gran_inmobiliaria}}]]></type>
        <operation><![CDATA[{{$property->status->gran_inmobiliaria}}]]></operation>
        <price>{{$property->price}}</price>
        <currency><![CDATA[{{$property->currency_attr}}]]></currency>
        <title><![CDATA[{{$property->title}}]]></title>
        <date_updated><![CDATA[{{$property->updated_at->format('Y-m-d')}}]]></date_updated>
        <date_published><![CDATA[{{$property->created_at->format('Y-m-d')}}]]></date_published>
        {{--  <payment_financing><![CDATA[12 cuotas]]></payment_financing>  --}}
        {{--  <payment_accepts_loan>0</payment_accepts_loan>  --}}
        {{--  <maintenance_fee>3000</maintenance_fee>  --}}
        {{--  <utilities_included></utilities_included>  --}}
        {{--  <youtube_code><![CDATA[dQw4w9WgX]]></youtube_code>  --}}
        <description><![CDATA[{{$property->description}}]]></description>

        <location>
            <street><![CDATA[{{$property->address}}]]></street>
            <number><![CDATA[{{$property->exterior}}]]></number>
            {{--  <between1></between1>  --}}
            {{--  <between2></between2>  --}}
            <country><![CDATA[México]]></country>
            <region><![CDATA[{{$property->local->city->state->name}}]]></region>
            <city><![CDATA[{{$property->local->city->name}}]]></city>
            <locality><![CDATA[{{$property->local->name}}]]></locality>
            <postal_code>{{$property->zipcode}}</postal_code>
            <floor>{{$property->floor_located}}</floor>
            {{--  <apartment></apartment>  --}}
            <latitude>{{$property->lat}}</latitude>
            <longitude>{{$property->lng}}</longitude>
        </location>

        <agency>
            <id><![CDATA[{{$property->company->id}}]]></id>
            <name><![CDATA[{{$property->company->name}}]]></name>
            <email><![CDATA[{{$property->company->email}}]]></email>
            {{--  <razon_social></razon_social>  --}}
            {{--  <license></license>  --}}
            <phone><![CDATA[{{$property->company->phone}}]]></phone>
            <phone2></phone2>
            <address>
                <street><![CDATA[{{$property->company->address}}]]></street>
                {{--  <number><![CDATA[1111]]></number>  --}}
                <postal_code><![CDATA[{{$property->company->zipcode}}]]></postal_code>
                <country><![CDATA[México]]></country>
                {{--  <region><![CDATA[Buenos Aires]]></region>  --}}
                {{--  <city><![CDATA[Palermo]]></city>  --}}
                {{--  <locality></locality>  --}}
                @if($property->company->logo)<logo_url><![CDATA[{{config('app.server').$property->company->logo}}]]></logo_url>@endif
            </address>
            <agent>
                <name><![CDATA[{{$property->agent_assignt->full_name}}]]></name>
                <surname><![CDATA[{{$property->agent_assignt->last_name}}]]></surname>
                <phone><![CDATA[{{$property->agent_assignt->phone}}]]></phone>
                <email><![CDATA[{{$property->agent_assignt->email}}]]></email>
            </agent>
        </agency>
        <images>
            @foreach ($property->imagesAPI() as $key => $image)
            <image>
                <url><![CDATA[{{$image}}]]></url>
                <title>{{$property->title.'_'.($key+1)}}</title>
            </image>
            @endforeach
        </images>
        <property>
            <plot_area>{{round($property->total_area)}}</plot_area>
            <floor_area>{{round($property->built_area)}}</floor_area>
            <terrain_front_width>{{$property->front}}</terrain_front_width>
            <terrain_depth>{{$property->length}}</terrain_depth>
            <year>{{$property->antiquity}}</year>
            {{--  <is_new></is_new>  --}}
            {{--  <condition></condition>  --}}
            <is_furnished><![CDATA[{{$property->hasFeature(6)}}]]></is_furnished>
            {{--  <rooms>{{$property->bedrooms}}</rooms>  --}}
            <bedrooms>{{$property->bedrooms}}</bedrooms>
            <bathrooms>{{$property->baths_count}}</bathrooms>
            <garages>{{$property->parking_lots}}</garages>
            {{--  <garages_type>0</garages_type>  --}}
            <patio><![CDATA[{{$property->hasFeature(33)}}]]></patio>
            <terrace><![CDATA[{{$property->hasFeature(38)}}]]></terrace>
            <balcony><![CDATA[1]]>{{$property->hasFeature(12)}}</balcony>
            {{--  <orientation><![CDATA[NE]]></orientation>  --}}
            {{--  <layout><![CDATA[Frente]]></layout>  --}}
            <floor>{{$property->floor_located}}</floor>
            {{--  <apartment><![CDATA[A]]></apartment>  --}}
            {{--  <professional>0</professional>  --}}
            {{--  <commercial>0</commercial>  --}}
            {{--  <luminosity>1</luminosity>  --}}

            <services>
                <water>{{$property->hasFeature(40)}}</water>
                {{--  <sewage></sewage>  --}}
                <gas>{{$property->hasFeature(23)}}</gas>
                <internet>{{$property->hasFeature(25)}}</internet>
                <electricity>{{$property->hasFeature(40)}}</electricity>
                <paved>{{$property->hasFeature(71)}}</paved>
                <phone>{{$property->hasFeature(28)}}</phone>
                <videocable>{{$property->hasFeature(10)}}</videocable>
            </services>

        </property>
    </ad>
    @endforeach
</ads>

