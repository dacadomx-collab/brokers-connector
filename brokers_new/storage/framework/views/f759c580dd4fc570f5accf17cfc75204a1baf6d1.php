<div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
    <div class="single-review-st-hd">
        <h2 class="text-center">Mapa de propiedades</h2>
    </div>
    <div id="map"></div>
</div>

<?php $__env->startPush('scripts'); ?>
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
                    lat: <?php echo e($allMyProperties->count() ? (float)$allMyProperties[0]->lat : 24.1583354); ?>,
                    lng: <?php echo e($allMyProperties->count() ? (float)$allMyProperties[0]->lng : -110.3227886); ?>

                },
                streetViewControl: false,
            });

            var infowindow = new google.maps.InfoWindow();

            <?php $__currentLoopData = $allMyProperties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                (function() {
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(<?php echo e((float)$property->lat); ?>, <?php echo e((float)$property->lng); ?>),
                        map: map,
                        icon: icons[0]
                    });

                    google.maps.event.addListener(marker, 'click', function() {
                        var content = "<a id='map-link' href='/properties/view/<?php echo e($property->id); ?>-<?php echo e(\Illuminate\Support\Str::slug($property->title)); ?>'>" +
                            "<div class='text-center' style='width:300px;'>" +
                            "<img style='width:100%;height:6.25rem;object-fit:cover;margin-bottom:2px;' src='<?php echo e($property->image); ?>'><br>" +
                            "<h5><?php echo e($property->title); ?>&nbsp;" +
                            "<i class='fa fa-circle' title='<?php echo e($property->published ? 'Publicado' : 'No publicado'); ?>'" +
                            " style='font-size:0.7rem;vertical-align:middle;color:<?php echo e($property->published ? '#34a854' : '#ea4331'); ?>'></i>" +
                            "</h5>" +
                            "<p><b>Precio:</b><br>" +
                            "$<?php echo e(number_format($property->price, 2)); ?> <?php echo e($property->currency_attr); ?><br>" +
                            "<span><?php echo e($property->status_name); ?> / <?php echo e(ucfirst($property->type_prop)); ?></span></p>" +
                            "</div></a>";
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                    });
                })();
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            google.maps.event.addListener(map, 'click', function() {
                infowindow.close();
            });
        }
    </script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/includes/map-properties.blade.php ENDPATH**/ ?>