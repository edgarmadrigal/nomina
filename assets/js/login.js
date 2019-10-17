 
 $(document).ready(function () {
        var base_url="";    
        //http://192.168.128.24:8080/nomina/
        
        $(document).on('click', '#admin_login', function (e) {
            e.preventDefault();
            var usuario = $('#usuario').val();
            var password = $('#password').val();
            $.ajax({
                url: base_url+'home/login',
                type: 'post',
                dataType: 'json',
                async: true,
                data: {
                    'usuario': usuario,
                    'password': password
                }
            }).done(function (data) {
                //console.log(data);
                if(data==0){
                    $("#error").empty();
                    $("#error").append("Las credenciales no existen en la base de datos");
                }else{          
                    $("#error").empty();          
                    window.location = base_url+'home';
                }
            }).fail(function(xhr, err) { 
                //console.log($(responseTitle).text() + "\n" + formatErrorMessage(xhr, err));
            });
        });   
        
        $(document).on('click', '#logout_admin', function (e) {
            e.preventDefault();
            $.ajax({
                url: base_url+'home/logout',
                type: 'POST',
                dataType: 'json',
                async: true
            }).done(function (data) {
                console.log(data);
                window.location = base_url+'home';
            });
        });

    });