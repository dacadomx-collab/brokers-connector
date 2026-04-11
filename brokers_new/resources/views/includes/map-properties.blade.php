<div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
    <div class="single-review-st-hd">
        <h2 class="text-center">Mapa de propiedades</h2>
    </div>
    <div id="map"></div>
</div>

@push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84&callback=initMap" async defer></script>
    <script>
        var map;
        var iconURLPrefix = 'https://maps.google.com/mapfiles/ms/icons/';
        var icons = [
            iconURLPrefix + 'red-dot.png',
            iconURLPrefix + 'green-dot.png',
            iconURLPrefix + 'blue-dot.png',
            iconURLPrefix + 'orange-dot.png',
        ];

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 5,
                center: {
                    lat: {{ $allMyProperties->count() ? (float)$allMyProperties[0]->lat : 24.1583354 }},
                    lng: {{ $allMyProperties->count() ? (float)$allMyProperties[0]->lng : -110.3227886 }}
                },
                streetViewControl: false,
            });

            var infowindow = new google.maps.InfoWindow();

            @foreach($allMyProperties as $property)
                (function() {
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ (float)$property->lat }}, {{ (float)$property->lng }}),
                        map: map,
                        icon: icons[0]
                    });

                    google.maps.event.addListener(marker, 'click', function() {
                        var content = "<a id='map-link' href='/properties/view/{{ $property->id }}-{{ \Illuminate\Support\Str::slug($property->title) }}'>" +
                            "<div class='text-center' style='width:300px;'>" +
                            "<img style='width:100%;height:6.25rem;object-fit:cover;margin-bottom:2px;' src='{{ $property->image }}'><br>" +
                            "<h5>{{ $property->title }}&nbsp;" +
                            "<i class='fa fa-circle' title='{{ $property->published ? 'Publicado' : 'No publicado' }}'" +
                            " style='font-size:0.7rem;vertical-align:middle;color:{{ $property->published ? '#34a854' : '#ea4331' }}'></i>" +
                            "</h5>" +
                            "<p><b>Precio:</b><br>" +
                            "${{ number_format($property->price, 2) }} {{ $property->currency_attr }}<br>" +
                            "<span>{{ $property->status_name }} / {{ ucfirst($property->type_prop) }}</span></p>" +
                            "</div></a>";
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                    });
                })();
            @endforeach

            google.maps.event.addListener(map, 'click', function() {
                infowindow.close();
            });
        }
    </script>
@endpush
