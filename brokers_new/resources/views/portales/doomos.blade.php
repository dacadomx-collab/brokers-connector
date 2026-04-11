
<ads>

	@foreach ($properties as $property)
		

	<ad>
		<id><![CDATA[{{ $property->id }}]]></id>
		<date><![CDATA[[{{$property->created_at}}]]]></date>
		<title><![CDATA[{{ $property->title }}]]></title>
		<content><![CDATA[{{ $property->description }}]]></content>
		<country_code><![CDATA[MX]]></country_code>
		<region><![CDATA[{{$property->local->city->state->name}}]]></region>
		<location><![CDATA[{{ $property->local->city->name }}]]></location>
		<city_area><![CDATA[{{ $property->local->name }}]]></city_area>
		<address><![CDATA[{{$property->address}} {{$property->exterior}}, {{$property->local->name}}, {{$property->local->city->name}} {{$property->zipcode}}, 
			{{$property->local->city->state->name}}, México]]></address>
		<zipcode>{{$property->zipcode}}</zipcode>
		<latitude><![CDATA[{{$property->lat}}]]></latitude>
		<longitude><![CDATA[{{$property->lng}}]]></longitude>

		<property_type><![CDATA[{{ $property->type->name }}]]></property_type>
		<type><![CDATA[{{$property->status->name}}]]></type>
		<price currency="{{$property->currency_attr}}"><![CDATA[{{$property->price}}]]></price>
		<rooms><![CDATA[{{$property->bedrooms}}]]></rooms>
		<bathrooms><![CDATA[{{$property->baths_count}}]]></bathrooms>
		@if ($property->type->id == 28)
		<lot_area>{{ $property->total_area }}</lot_area>
		@else
		<total_area><![CDATA[{{ $property->total_area }}]]></total_area>
		<covered_area><![CDATA[{{ $property->built_area }}]]></covered_area>
		@endif
		<year><![CDATA[{{$property->antiquity}}]]></year>
		{{--  <mls_database></mls_database>  --}}
		
		<contact_phone><![CDATA[{{$property->agent_assignt->phone}}]]></contact_phone>
		<contact_email><![CDATA[{{$property->agent_assignt->email}}]]></contact_email>
		<contact_name><![CDATA[{{$property->agent_assignt->f_name}}]]></contact_name>

		<pictures> 
		@foreach ($property->imagesAPI() as $image)
		<picture_url><![CDATA[{{$image}}]]></picture_url> 
		@endforeach
		</pictures> 
		
		{{-- <condition><![CDATA[muy buena]]></condition> --}}
		<floor_number><![CDATA[1]]></floor_number>
		{{-- <expenses></expenses> --}}
		{{-- <zoning><![CDATA[Residencial]]></zoning> --}}
		<furnished><![CDATA[{{ $property->hasFeature(6) }}]]></furnished>
		{{-- <elevator><![CDATA[0]]></elevator> --}}
		<air_conditioning><![CDATA[{{$property->hasFeature(5)}}]]></air_conditioning>
		{{-- <door_type><![CDATA[Automático]]></door_type> --}}
		{{-- <heating><![CDATA[no]]></heating> --}}
		{{-- <kitchen><![CDATA[Tradicional]]></kitchen> --}}
		{{-- <warehouse><![CDATA[1]]></warehouse> --}}
		<patio><![CDATA[{{$property->hasFeature(33)}}]]></patio>
		<terrace><![CDATA[{{$property->hasFeature(38)}}]]></terrace>
		{{-- <parking><![CDATA[3]]></parking> --}}
		<pool><![CDATA[{{$property->hasFeature(34)}}]]></pool>

	</ad>
	@endforeach
</ads>
