<?php
/*
Plugin Name: Telegram Bot & Channel (Custom)
Description: My Custom Telegram Plugin
Author: My name
Version: 1
*/

add_action('telegram_parse','telegramcustom_parse', 10, 2);

function telegramcustom_parse( $telegram_user_id, $text ) {

    $plugin_post_id = telegram_getid( $telegram_user_id );

    if ( !$plugin_post_id ) {
        return;
    }

    if ( $telegram_user_id == '63480147' || $telegram_user_id == '61359892' ) {
      if ( $text == 'ok' ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'segnalazioni',
            array(
                'stato' => 1
            ),
            array(
                'stato' => '0'
            ),
            array( '%d', '%d' )
        );
        telegram_sendmessage( $telegram_user_id, 'Approvato tutto. Digita d:ID se vuoi rimuovere qualcosa');
        die();
      } else if ( substr( $text, 0, 1 ) === 'd' ) { //as:id:issue
        $arr = explode(':', $text);
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'segnalazioni',
            array(
                'stato' => 2
            ),
            array(
                'id' => $arr[1]
            ),
            array( '%d', '%d' )
        );
        telegram_sendmessage( $telegram_user_id, $arr[1].' eliminato');
        die();
      } else if ( substr( $text, 0, 2 ) === 'as' ) { //as:id:issue

        $arr = explode(':', $text);

        global $wpdb;

        $ob = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'segnalazioni WHERE id="'.$arr[1].'"');

        $title = $ob->description;
        $body = '!['.$ob->description.']('.$ob->url.')'.PHP_EOL.'[Link Mappa](http://umap.openstreetmap.fr/it/map/terremoto-centro_100394#12/'.$ob->lat.'/'.$ob->lon.')';

        $data = array(
          'value1' => $title,
          'value2' => $body
        );

        $options = array(
          'http' => array(
            'method'  => 'POST',
            'content' => json_encode( $data ),
            'header'=>  "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
            )
        );

        $context  = stream_context_create( $options );
        $result = file_get_contents( 'https://maker.ifttt.com/trigger/crea_issue/with/key/nwnv5x6ooRIwUFXax2A81Dt5mpRI71wmz9RKfR11-Us', false, $context );
        $response = json_decode( $result );
        die();

      }
    }

    if ( $text == 'info' || $text == '/help' ) {
      telegram_sendmessage( $telegram_user_id, 'Bot creato per http://terremotocentroitalia.info/'.PHP_EOL.'Per info e suggerimenti: @Milmor');
    } else if ( $text == 'carica più foto' ) {
      telegram_sendmessage( $telegram_user_id, 'Puoi inviare più foto contemporaneamente e in differita utilizzando "invia file" anziché "invia foto".'
    .PHP_EOL.'In questo modo verrà estratta in automatico latitudine, longitudine e data di scatto della foto');
    } else if ( $text == 'oggi' ) {
      telegram_sendmessage( $telegram_user_id, 'http://goo.gl/rZ0BeY');
    } else if ( $text == 'totale' ) {
      telegram_sendmessage( $telegram_user_id, 'http://goo.gl/bqaHVn');
    } else if ( $text == '/stato' || $text == 'stato' ) {
      global $wpdb;
      $conteggio = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}segnalazioni WHERE telegram_id=".$telegram_user_id.";" );
      telegram_sendmessage( $telegram_user_id, 'Hai inviato '.$conteggio.' segnalazioni.');
    } else if ( $text == '/segnala' || $text == 'segnala') {
        telegram_sendmessage( $telegram_user_id, 'Tramite la 📎 invia per prima cosa la tua posizione');
    } else if ( get_post_meta( $plugin_post_id, 'telegram_custom_state', true ) == 'description_wait' && $text != '') {
      $lat = get_post_meta( $plugin_post_id, 'telegram_last_latitude', true );
      $long = get_post_meta( $plugin_post_id, 'telegram_last_longitude', true );
      if ( !$lat > 0 || !$long > 0 ) {
        telegram_sendmessage( $telegram_user_id, 'Tramite la 📎 invia per prima cosa la tua posizione');
        delete_post_meta( $plugin_post_id, 'telegram_custom_description' );
        delete_post_meta( $plugin_post_id, 'telegram_custom_state' );
        return;
      }
      update_post_meta( $plugin_post_id, 'telegram_custom_description', sanitize_text_field($text) );
      telegram_sendmessage( $telegram_user_id, 'Inviami una foto');
    }
    return;
}

function telegram_parse_document_s ( $telegram_user_id, $document  ) {

  $url = telegram_download_file( $telegram_user_id, $document['file_id'] );
  if ( $url ) {

    $local_url = ABSPATH.'wp-content/uploads/telegram-bot/'.$plugin_post_id.'/'.basename( $url );

    if ( $geo = telegram_read_gps_location( $local_url ) ) {

      $lat = $geo['lat'];
      $lon = $geo['lon'];
      $time = $geo['time'];

      global $wpdb;

      $wpdb->insert(
          $wpdb->prefix . 'segnalazioni',
          array(
              'lat' => $lat,
              'lon' => $lon,
              'telegram_id' => $telegram_user_id,
              'wordpress_id' => $plugin_post_id,
              'description' => 'EXIF',
              'url' => $url,
              'time' => $time
          ),
          array( '%s', '%s', '%d', '%d', '%s' )
      );
      $int = telegram_location_haversine_distance ( 42.7, 13.24, $lat, $lon, $earthRadius = 6371);

      telegram_sendmessage( $telegram_user_id, 'Foto registrata e geolocalizzata a '.$int.' km dall\'epicentro.  Grazie');
      telegramcustom_send_check( $wpdb->insert_id, $url );
    } else {
      telegram_sendmessage( $telegram_user_id, 'Geolocalizzazione non trovata: impossibile salvare.');
      telegram_log('@@', '##', 'not compatible');
    }
  } else {
    telegram_sendmessage( $telegram_user_id, 'Errore. Se il problema persiste contattaci');
  }
} add_action('telegram_parse_document','telegram_parse_document_s', 10, 2);

function telegram_parse_photo_s ( $telegram_user_id, $photo  ) {

$plugin_post_id = telegram_getid( $telegram_user_id );

    $lat = get_post_meta( $plugin_post_id, 'telegram_last_latitude', true );
    $long = get_post_meta( $plugin_post_id, 'telegram_last_longitude', true );
    if ( !$lat > 0 || !$long > 0 ) {
      telegram_sendmessage( $telegram_user_id, 'Tramite la 📎 invia per prima cosa la tua posizione');
      return;
    }

  $url = telegram_download_file( $telegram_user_id, $photo[2]['file_id'] );
  if ( $url ) {

    $desc = get_post_meta( $plugin_post_id, 'telegram_custom_description', true );

    get_post_meta($plugin_post_id, 'telegram_username', true) ? $t_user = get_post_meta($plugin_post_id, 'telegram_username', true) : $t_user = '';

    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'segnalazioni',
        array(
            'lat' => $lat,
            'lon' => $long,
            'telegram_id' => $telegram_user_id,
            'telegram_username' => $t_user,
            'wordpress_id' => $plugin_post_id,
            'description' => $desc,
            'url' => $url
        ),
        array( '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
    );
    telegram_sendmessage( $telegram_user_id, 'Foto registrata. Grazie');
    delete_post_meta( $plugin_post_id, 'telegram_custom_description' );
    delete_post_meta( $plugin_post_id, 'telegram_custom_state' );
    delete_post_meta( $plugin_post_id, 'telegram_last_latitude' );
    delete_post_meta( $plugin_post_id, 'telegram_last_longitude' );

    telegramcustom_send_check( $wpdb->insert_id, $url );

  }
} add_action('telegram_parse_photo','telegram_parse_photo_s', 10, 2);


add_action('telegram_parse_location','telegramcustom_c_parse_location', 10, 3);

function telegramcustom_c_parse_location ( $telegram_user_id, $lat, $long  ) {
  $plugin_post_id = telegram_getid( $telegram_user_id );

  if ( !$plugin_post_id ) {
      return;
  }

  if ( get_post_meta( $plugin_post_id, 'telegram_custom_state', true ) == 'position_wait' ) {
    $int = telegram_location_haversine_distance ( 42.7, 13.24, $lat, $long, $earthRadius = 6371);
    telegram_sendmessage( $telegram_user_id, 'Posizione ricevuta ('.$int.' km dall\'epicentro)');
    telegram_sendmessage( $telegram_user_id, 'Inviami una descrizione o direttamente la foto');
    update_post_meta( $plugin_post_id, 'telegram_custom_state', 'description_wait' );
  } else {
    $int = telegram_location_haversine_distance ( 42.7, 13.24, $lat, $long, $earthRadius = 6371);
    telegram_sendmessage( $telegram_user_id, 'Posizione ricevuta ('.$int.' km dall\'epicentro)');
    telegram_sendmessage( $telegram_user_id, 'Inviami una descrizione o direttamente la foto');
    update_post_meta( $plugin_post_id, 'telegram_custom_state', 'description_wait' );
  }

}

function telegramcustom_send_check( $id, $url ) {
   telegram_sendphoto( '63480147', $id.' - Digita ok per approvare, d:ID per eliminare, as:ID per creare issue', $url);
   telegram_sendphoto( '61359892', $id.' - Digita ok per approvare, d:ID per eliminare, as:ID per creare issue', $url);
}

function telegramcustom_csv_pull() {

  $where = '';
  if ( isset( $_GET[ 'mese' ] ) && absint( $_GET[ 'mese' ] ) ) {
    $where = ' AND MONTH(time)='.$_GET[ 'mese' ];
  }
  if ( isset( $_GET[ 'giorno' ] ) ) {
    if ( absint( $_GET[ 'giorno' ] ) ) {
      $where .= ' AND DAY(time)='.$_GET[ 'giorno' ];
    } else if ( $_GET['giorno'] == 'oggi') {
      $where = ' AND DAY(time)='.date("d");
    }
  }

  global $wpdb;
  $file = 'segnalazioni';
  $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}segnalazioni WHERE stato=1".$where.";",ARRAY_A);

  if (empty($results)) {
    print '"id","lat","lon","telegram_id","wordpress_id","description","url","time","stato"';
    die();
  }

  $csv_output = '"'.implode('","',array_keys($results[0])).'"';

  foreach ($results as $row) {
    $csv_output .= "\r\n".'"'.implode('","',$row).'"';
  }

  if (  $_GET[ 'get-csv' ] == 'true' ) {
  $filename = $file."_".date("Y-m-d_H-i",time());
  header( "Access-Control-Allow-Origin: *");
  header( "Content-type: text/csv; charset=utf-8");
  header( "Content-disposition: csv" . date("Y-m-d") . ".csv");
  header( "Content-disposition: filename=".$filename.".csv");
}
  print $csv_output;
  die();
}

function telegram_c_plugin_parse_request() {
	global $wp_query;
	if ( isset( $_GET[ 'get-csv' ] ) ) {
    telegramcustom_csv_pull();
	}
}
add_action('template_redirect', 'telegram_c_plugin_parse_request');

function telegram_read_gps_location($file){
    if (is_file($file)) {
        $info = exif_read_data($file);
        if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
            isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
            in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

            $GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
            $GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

            $lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
            $lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
            $lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
            $lng_degrees_a = explode('/',$info['GPSLongitude'][0]);
            $lng_minutes_a = explode('/',$info['GPSLongitude'][1]);
            $lng_seconds_a = explode('/',$info['GPSLongitude'][2]);

            $lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
            $lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
            $lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
            $lng_degrees = $lng_degrees_a[0] / $lng_degrees_a[1];
            $lng_minutes = $lng_minutes_a[0] / $lng_minutes_a[1];
            $lng_seconds = $lng_seconds_a[0] / $lng_seconds_a[1];

            $lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
            $lng = (float) $lng_degrees+((($lng_minutes*60)+($lng_seconds))/3600);

            //If the latitude is South, make it negative.
            //If the longitude is west, make it negative
            $GPSLatitudeRef  == 's' ? $lat *= -1 : '';
            $GPSLongitudeRef == 'w' ? $lng *= -1 : '';

            return array(
                'lat' => $lat,
                'lon' => $lng,
                'time' => $info['DateTimeOriginal']
            );
        }
    }
    return false;
}

?>
