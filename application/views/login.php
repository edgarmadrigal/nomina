<!DOCTYPE html>
<?php header('Access-Control-Allow-Origin: *');  ?>
<html lang="es-mx">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.20.4/TweenMax.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>

    <script src="assets/js/login.js" type="text/javascript"></script>

<head>
  <link rel="stylesheet" media="screen" href="assets/particules/css/style.css">
</head>
<body>
<div id="particles-js"  >
<div class="container" style="position: absolute;float: left;    width: 100%;" >
    <div class="row vertical-offset-100">
    	<div class="col-md-4 col-md-offset-4">
    		<div class="panel panel-default">
			  	<div class="panel-heading">
			    	<h3 class="panel-title">Iniciar Session</h3>
			 	</div>
			  	<div  class="panel-body">
			    	<form rol="form">
                    <fieldset>
			    	  	<div class="form-group">
			    		    <input class="form-control" placeholder="usuario" name="usuario" id="usuario" type="text">
			    		</div>
			    		<div class="form-group">
			    			<input class="form-control" placeholder="Password" name="password" id="password" type="password" value="">
                        </div>
			    		<div class="form-group">
                            <span id="error" class="error invalid-feedback text-center"></span>
                        </div>
			    		<input class="btn btn-lg btn-success btn-block" type="submit" value="Entrar" id="admin_login">
			    	</fieldset>
			      	</form>
			    </div>
			</div>
		</div>
	</div>
</div>
</div>
<!-- scripts -->
<script src="assets/particules/particles.js"></script>
<script src="assets/particules/js/app.js"></script>
<style>
 .particles-js-canvas-el{
    background: black;
    position: static;
    float: left;
 }   
</style>