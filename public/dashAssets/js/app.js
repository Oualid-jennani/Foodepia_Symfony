$(function () {

    var fullName = $('.fullName').text().split(' ');
    console.log("fullName : " +fullName);
    var miniName = "";
    if(fullName.length >= 1){
        miniName = fullName[0].substr(0, 1);
    }

    if(fullName.length > 1){
        miniName += fullName[1].substr(0, 1);
    }
    $('.mini-name').html(miniName.toUpperCase());


    $('.spinner-grow').hide();
    $('#second_admin_country').on('change', function () {
        /*const url = Routing.generate('cities', {});*/
        const $country = $(this).val();
        $.ajax({
            'url' : '/cities', // To change with Routing.generate later
            'data' : {
                'country' : $country
            },
            'type': 'post',
            'beforeSend' : function() {
                $('#second_admin_city').find('option:not(:first)').remove();
                $('.spinner-border').show();
            },
            success: function (data) {
                $.each(data, function (i, p) {
                    $('#second_admin_city').append(new Option(p.name, p.id));
                    $('.spinner-border').hide();
                });
            }
        });

    });


    $('#variant_menu').on('change', function () {
        /*const url = Routing.generate('subMenus', {});*/
        const $menu = $(this).val();
        $.ajax({
            'url' : '/restaurant/ChangeSubMenus', // To change with Routing.generate later
            'data' : {
                'menu' : $menu
            },
            'type': 'post',
            'beforeSend' : function() {
                $('#variant_subMenu').find('option').remove();
            },
            success: function (data) {
                $.each(data, function (i, p) {
                    $('#variant_subMenu').append(new Option(p.name, p.id));
                });
            }
        });

    });

    $('#search_by_city_or_country_country').on('change', function () {
        /*const url = Routing.generate('cities', {});*/
        const $country = $(this).val();
        $.ajax({
            'url' : '/cities', // To change with Routing.generate later
            'data' : {
                'country' : $country
            },
            'type': 'post',
            'beforeSend' : function() {
                $('#search_by_city_or_country_city').find('option').remove();
                $('.spinner-border').show();
            },
            success: function (data) {
                $.each(data, function (i, p) {
                    $('#search_by_city_or_country_city').append(new Option(p.name, p.id));
                    $('.spinner-border').hide();
                });
            }
        });

    });
    if ($('#search_by_city_or_country_country').val()!="") {
        var $co = $('#search_by_city_or_country_country').val();
        $.ajax({
            'url' : '/cities', // To change with Routing.generate later
            'data' : {
                'country' : $co
            },
            'type': 'post',
            'beforeSend' : function() {
                $('#search_by_city_or_country_city').find('option').remove();
                $('.spinner-border').show();
            },
            success: function (data) {
                $.each(data, function (i, p) {
                    $('#search_by_city_or_country_city').append(new Option(p.name, p.id));
                    $('.spinner-border').hide();
                });
            }
        });
    }

    // $('#city_country').on('change', function () {
    //     /*const url = Routing.generate('cities', {});*/
    //     const $country = $(this).val();
    //     $.ajax({
    //         'url' : '/provinces', // To change with Routing.generate later
    //         'data' : {
    //             'country' : $country
    //         },
    //         'type': 'post',
    //         'beforeSend' : function() {
    //             $('#second_admin_city').find('option:not(:first)').remove();
    //             $('.spinner-border').show();
    //         },
    //         success: function (data) {
    //             console.log(data);
    //             $.each(data, function (i, p) {
    //                 $('#second_admin_city').append(new Option(p.name, p.id));
    //                 $('.spinner-border').hide();
    //             });
    //         }
    //     });
    //
    // });

} ());