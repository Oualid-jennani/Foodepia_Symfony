$(function () {

        //<editor-fold desc="City Registration">
        //<editor-fold desc="Customer Registration">
        $('#customer_registration_country').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $country = $(this).val();
                $.ajax({
                        'url' : '/provinces', // To change with Routing.generate later
                        'data' : {
                                'country' : $country
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#customer_registration_province').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#customer_registration_province').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        $('#customer_registration_province').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $province = $(this).val();
                $.ajax({
                        'url' : '/province-cities', // To change with Routing.generate later
                        'data' : {
                                'province' : $province
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#customer_registration_city').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#customer_registration_city').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        //</editor-fold>

        $('#restau_registration_country').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $country = $(this).val();
                $.ajax({
                        'url' : '/cities', // To change with Routing.generate later
                        'data' : {
                                'country' : $country
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#restau_registration_city').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#restau_registration_city').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });

        //<editor-fold desc="Partner Registration">
        $('#partner_registration_country').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $country = $(this).val();
                $.ajax({
                        'url' : '/provinces', // To change with Routing.generate later
                        'data' : {
                                'country' : $country
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#partner_registration_province').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#partner_registration_province').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        $('#partner_registration_province').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $province = $(this).val();
                $.ajax({
                        'url' : '/province-cities', // To change with Routing.generate later
                        'data' : {
                                'province' : $province
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#partner_registration_city').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#partner_registration_city').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        //</editor-fold>

        //<editor-fold desc="Driver Registration">
        $('#driver_registration_country').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $country = $(this).val();
                $.ajax({
                        'url' : '/provinces', // To change with Routing.generate later
                        'data' : {
                                'country' : $country
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#driver_registration_province').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#driver_registration_province').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        $('#driver_registration_province').on('change', function () {
                /*const url = Routing.generate('cities', {});*/
                const $province = $(this).val();
                $.ajax({
                        'url' : '/province-cities', // To change with Routing.generate later
                        'data' : {
                                'province' : $province
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#driver_registration_city').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                $.each(data, function (i, p) {
                                        $('#driver_registration_city').append(new Option(p.name, p.id));
                                        $('.spinner-border').hide();
                                });
                        }
                });

        });
        //</editor-fold>

        //</editor-fold>

        //<editor-fold desc="Search by city country">
        $('.spinner-grow').hide();

        $('#search_by_city_country').on('change', function () {

                const $country = $(this).val();
                $('#search_by_city_country').next().find(".select-selected").html("Loading...");
                $.ajax({
                        'url' : '/cities', // To change with Routing.generate later
                        'data' : {
                                'country' : $country
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                $('#search_by_city_city').find('option:not(:first)').remove();
                                $('.spinner-border').show();
                        },
                        success: function (data) {
                                //$('#search_by_city_city').append(new Option("Choose your City"));
                                 $.each(data, function (i, p) {
                                         $('#search_by_city_city').append(new Option(p.name, p.id));
                                 });
                                $('.spinner-grow').hide();

                                changeSelect("my-select-city");
                        }
                });

        });

        // $('#search_by_city_province').on('change', function () {
        //
        //         const $province = $(this).val();
        //
        //         $.ajax({
        //                 'url' : '/province-cities', // To change with Routing.generate later
        //                 'data' : {
        //                         'province' : $province
        //                 },
        //                 'type': 'post',
        //                 'beforeSend' : function() {
        //                         $('#search_by_city_city').find('option').remove();
        //                         $('.spinner-border').show();
        //                 },
        //                 success: function (data) {
        //                         console.log(data);
        //                         $('#search_by_city_city').append(new Option("Choose your City"));
        //                         $.each(data, function (i, p) {
        //                                 $('#search_by_city_city').append(new Option(p.name, p.id));
        //                         });
        //                         $('.spinner-grow').hide();
        //
        //                         changeSelect("my-select-city");
        //                 }
        //         });
        //
        // });

        //</editor-fold>


        if ($('#search_by_city_country').val()!= "") {
                var  $co = $('#search_by_city_country').val();
                $.ajax({
                        'url' : '/cities', // To change with Routing.generate later
                        'data' : {
                                'country' : $co
                        },
                        'type': 'post',
                        'beforeSend' : function() {
                                //$('#search_by_city_city').find('option:not(:first)').remove();
                                $('.spinner-grow').show();
                        },
                        success: function (data) {
                                //$('#search_by_city_city').append(new Option("Choose a city"));
                                $.each(data, function (i, p) {
                                        $('#search_by_city_city').append(new Option(p.name, p.id));
                                });
                                $('.spinner-grow').hide();

                                changeSelect("my-select-city")

                        }
                });
        }

} ());


changeSelect("my-select-country");
changeSelect("my-select-province");
changeSelect("my-select-city");

function changeSelect(mySelect) {
        var x, i, j, l, ll, selElmnt, a, b, c;
        /*look for any elements with the class "custom-select":*/
        x = document.getElementsByClassName(mySelect);
        x[0].getElementsByClassName("select-selected")[0].remove();
        x[0].getElementsByClassName("select-items")[0].remove();

        l = x.length;
        for (i = 0; i < l; i++) {
                selElmnt = x[i].getElementsByTagName("select")[0];
                ll = selElmnt.length;
                /*for each element, create a new DIV that will act as the selected item:*/
                a = document.createElement("DIV");
                a.setAttribute("class", "select-selected");
                a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
                x[i].appendChild(a);
                /*for each element, create a new DIV that will contain the option list:*/
                b = document.createElement("DIV");
                b.setAttribute("class", "select-items select-hide");
                for (j = 1; j < ll; j++) {
                        /*for each option in the original select element,
                        create a new DIV that will act as an option item:*/
                        c = document.createElement("DIV");
                        c.innerHTML = selElmnt.options[j].innerHTML;
                        c.addEventListener("click", function(e) {
                                /*when an item is clicked, update the original select box,
                                and the selected item:*/
                                var y, i, k, s, h, sl, yl;
                                s = this.parentNode.parentNode.getElementsByTagName("select")[0];
                                sl = s.length;
                                h = this.parentNode.previousSibling;
                                for (i = 0; i < sl; i++) {
                                        if (s.options[i].innerHTML == this.innerHTML) {

                                                s.selectedIndex = i;
                                                s.dispatchEvent(new Event("change"))

                                                h.innerHTML = this.innerHTML;
                                                y = this.parentNode.getElementsByClassName("same-as-selected");
                                                yl = y.length;
                                                for (k = 0; k < yl; k++) {
                                                        y[k].removeAttribute("class");
                                                }
                                                this.setAttribute("class", "same-as-selected");
                                                break;
                                        }
                                }
                                h.click();
                        });
                        b.appendChild(c);
                }
                x[i].appendChild(b);
                a.addEventListener("click", function(e) {
                        /*when the select box is clicked, close any other select boxes,
                        and open/close the current select box:*/
                        e.stopPropagation();
                        closeAllSelect(this);
                        this.nextSibling.classList.toggle("select-hide");
                        this.classList.toggle("select-arrow-active");
                });
        }
        function closeAllSelect(elmnt) {
                /*a function that will close all select boxes in the document,
                except the current select box:*/
                var x, y, i, xl, yl, arrNo = [];
                x = document.getElementsByClassName("select-items");
                y = document.getElementsByClassName("select-selected");
                xl = x.length;
                yl = y.length;
                for (i = 0; i < yl; i++) {
                        if (elmnt == y[i]) {
                                arrNo.push(i)
                        } else {
                                y[i].classList.remove("select-arrow-active");
                        }
                }
                for (i = 0; i < xl; i++) {
                        if (arrNo.indexOf(i)) {
                                x[i].classList.add("select-hide");
                        }
                }
        }
        /*if the user clicks anywhere outside the select box,
        then close all select boxes:*/
        document.addEventListener("click", closeAllSelect);
}
