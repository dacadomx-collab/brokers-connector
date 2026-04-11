$(function() {
    var LocsA = [
        {
            lat: 24.0555651,
            lon: -110.2973704,
            title: 'CASA EN VENTA EN PALMAR 4',
            html: [ '<div class="single-feature">',
                        '<thumb><img src="assets/img/feature/4.png" alt="img"></thumb>',
                        '<div class="details">',
                            '<a href="#" class="feature-logo"><img src="assets/img/icons/l3.png" alt="icons"></a>',
                            '<p class="author"><i class="fa fa-user"></i> Vilma Jarvi By Redbrox</p>',
                            '<h6 class="title"><a href="properties-details.html">CASA EN VENTA EN PALMAR 4</a></h6>',
                            '<h6 class="price">$350/mo</h6><del>$790/mo</del>',
                            '<ul class="info-list">',
                                '<li><i class="fa fa-bed"></i> 05 Bed</li>',
                                '<li><i class="fa fa-bath"></i> 02 Bath</li>',
                                '<li><img src="assets/img/icons/7.png" alt="img"> 1898 sq.</li>',
                            '</ul>',
                            '<ul class="contact-list">',
                                '<li><a class="phone" href="#"><i class="fa fa-phone"></i></a></li>',
                                '<li><a class="message" href="#"><img src="assets/img/icons/8.png" alt="img"></a></li>',
                                '<li><a class="btn btn-yellow" href="#">View Details</a></li>',
                            '</ul>',
                        '</div>',
                    '</div>'
                ].join(''),
            icon: '/assets/img/icons/marker.png',
        },           
    ];
    new Maplace({
        locations: LocsA,
        controls_on_map: false,
        map_options: {
            zoom: 12,
            scrollwheel: false,
            stopover: true
        }
    }).Load();

});