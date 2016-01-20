<?php
header('Content-Type: text/html; charset=utf-8');

function randomPassword() {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 12; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

?>
<h2>Instalar Wordpress</h2>
<form action="wepe.php" method="post">
Dominio (ej: ejemplo.com): <input type="text" name="dominio"><br>
Anteponer www? <input type="checkbox" value="www" name="www" checked><br>
Activar Multisitio? <input type="checkbox" value="multisitio" name="multisitio">
<br><br>
<input type="submit" name="crear" value="Crear">
</form>

<?php
if($_POST['crear']){
  exec('sudo /usr/sbin/nginx -t 2>&1',$output,$retorno); // Chequea configuracion de nginx
  if ($retorno != 0){
    echo "<b>Error en configuracion de nginx. Por favor chequear!</b> </br></br>";
    echo "Detalles:</br>";
    
    for($i=0;$i<count($output);$i++){
      echo $output[$i];
      echo "<br>";
    }

  }
  else{ // Si configuracion de nginx esta ok...
    // Descarga, Descomprime y copia Wordpress
    exec('wget http://es.wordpress.org/latest-es_ES.tar.gz -O /tmp/wordpress-latest.tar.gz && tar -C /www -xvf /tmp/wordpress-latest.tar.gz && mv /www/wordpress /www/'.$_POST['dominio'].' && chown -R www-data /www/'.$_POST['dominio'].' && rm /tmp/wordpress-latest.tar.gz');
    // Crea archivo de configuracion de nginx
    
    if($_POST['www']) $configp1='server_name '.$_POST['dominio'].' www.'.$_POST['dominio'].'; # Dominio aquí
set $dir /www/'.$_POST['dominio'].'; # Directorio del sitio aquí';
	  
    else $configp1='server_name '.$_POST['dominio'].'; # Dominio aquí
set $dir /www/'.$_POST['dominio'].'; # Directorio del sitio aquí';
	  
    $config='## Configuración Optimizada para ser usada con Wordpress

server {

'.$configp1.'



### NO MODIFICAR HACIA ABAJO

access_log  /var/log/nginx/$server_name.access.log;

    location / {
        root   $dir;
        index  index.php;
		try_files $uri $uri/ /index.php?q=$uri&$args;
    }

## Images and static content is treated different
    location ~* ^.+.(ogg|ogv|svg|svgz|eot|otf|woff|mp4|rss|atom|jpg|jpeg|gif|png|ico|zip|tgz|gz|rar|bz2|doc|xls|exe|ppt|tar|mid|midi|wav|bmp|rtf)$ {
      access_log        off;
      expires           30d;
      root $dir;
    }

## Parse all .php file in the /var/www directory
    location ~ .php$ {

        location ~* wp\-login\.php {
        limit_req   zone=one  burst=1 nodelay;

        fastcgi_split_path_info ^(.+\.php)(.*)$;
        fastcgi_pass   backend;
       include fastcgi_params;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $dir$fastcgi_script_name;

        }

        fastcgi_split_path_info ^(.+\.php)(.*)$;
        fastcgi_pass   backend;
       include fastcgi_params;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $dir$fastcgi_script_name;
        }


## Disable viewing .htaccess & .htpassword
    location ~ /\.ht {
        deny  all;
  }	

	location = /robots.txt { access_log off; log_not_found off; }
	location ~ /\. { deny  all; access_log off; log_not_found off; }

}';
    

    // Escribimos archivo de config
    
    $archivo = fopen("/etc/nginx/sites-enabled/".$_POST['dominio'], "w") or die("Error creando archivo de configuracion!");
    fwrite($archivo, $config);
    fclose($archivo);
    
    // Volvemos a chequear nginx
    
    exec('sudo /usr/sbin/nginx -t 2>&1',$output,$retorno); // Chequea configuracion de nginx
    if ($retorno != 0){
        exec("rm /etc/nginx/sites-enabled/".$_POST['dominio']);
	echo "<b>Error en configuracion de nginx. Revirtiendo cambios!</b> </br></br>";
	echo "Detalles:</br>";
	
	for($i=0;$i<count($output);$i++){
	  echo $output[$i];
	  echo "<br>";
	}
    }
    else{ // Si esta todo ok, recargamos nginx
    
      exec('sudo service nginx reload 2>&1',$output,$retorno);
      if ($retorno !=0){
      echo "<b>Error recargando nginx!</b>";
      echo "</br></br></br>Detalles:</br>";
	  for($i=0;$i<count($output);$i++){
	    echo $output[$i];
	    echo "<br>";
	  }
      }
      else{ 
      
      // Creando bases de datos
      
      $array=explode(".",$_POST['dominio']);
      
      if(count($array)==2) $user=substr($array[0], 0,10).substr($array[1], 0,3);
      if(count($array)==3) $user=substr($array[0], 0,5).substr($array[1], 0,8).substr($array[2], 0,3);
      if(count($array)>3) $user=substr($array[count($array)-3], 0,3).substr($array[count($array)-2], 0,8).substr($array[count($array)-1], 0,3).count($array);
      
          $host="localhost"; 

	  $root="root"; 
	  $root_password="ROOTPASSWORD"; 

	  $pass=randomPassword();
	  $db=$user;
	  
	      try {
		  $dbh = new PDO("mysql:host=$host", $root, $root_password);

		  $dbh->exec("CREATE DATABASE `$db`;
			  CREATE USER '$user'@'localhost' IDENTIFIED BY '$pass';
			  GRANT ALL ON `$db`.* TO '$user'@'localhost';
			  FLUSH PRIVILEGES;") 
		  or die(print_r($dbh->errorInfo(), true));
		  
	      } catch (PDOException $e) {
		  die("DB ERROR: ". $e->getMessage());
	      }
	      
	      
      // Generando wp-config.php
      
      $wpsalt=file_get_contents("https://api.wordpress.org/secret-key/1.1/salt/");
      
      if($_POST['multisitio']) $multisitio="/* Multisite */
define( 'WP_ALLOW_MULTISITE', true );";
      else $multisitio="";
      
$wpconfig="<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to wp-config.php and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', '".$db."');

/** Tu nombre de usuario de MySQL */
define('DB_USER', '".$user."');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', '".$pass."');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', '".$host."');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
".$wpsalt."

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
\$table_prefix  = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

".$multisitio."

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

";

$wpfile = fopen("/www/".$_POST['dominio']."/wp-config.php", "w") or die("Error creando archivo de configuracion!");
    fwrite($wpfile, $wpconfig);
    fclose($wpfile);
      
      echo "<b>Sitio instalado correctamente</b>";
    
      }
    }
    
    
    
  }
}


?>

