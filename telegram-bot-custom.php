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

    if ( $text == 'segnala') {
        telegram_sendmessage( $telegram_user_id, 'Inviami la tua posizione attuale');
        update_post_meta( $plugin_post_id, 'telegram_custom_state', 'position_wait' );
    } else if ( get_post_meta( $plugin_post_id, 'telegram_custom_state', true ) == 'description_wait' && $text != '') {
      $lat = get_post_meta( $plugin_post_id, 'telegram_last_latitude', true );
      $long = get_post_meta( $plugin_post_id, 'telegram_last_longitude', true );
      $description = sanitize_text_field($text);

      global $wpdb;

      $wpdb->insert(
          $wpdb->prefix . 'segnalazioni',
          array(
              'lat' => $lat,
              'lon' => $long,
              'telegram_id' => $telegram_user_id,
              'wordpress_id' =>$plugin_post_id,
              'description' => $description
          ),
          array( '%s', '%s', '%d', '%d', '%s' )
      );
      telegram_sendmessage( $telegram_user_id, 'Dati salvati. Grazie');
    }

    return;
}

add_action('telegram_parse_location','telegramcustom_c_parse_location', 10, 3);

function telegramcustom_c_parse_location ( $telegram_user_id, $lat, $long  ) {
  $plugin_post_id = telegram_getid( $telegram_user_id );

  if ( !$plugin_post_id ) {
      return;
  }

  if ( get_post_meta( $plugin_post_id, 'telegram_custom_state', true ) == 'position_wait' ) {
    $int = telegram_location_haversine_distance ( 42.7, 13.24, $lat, $long, $earthRadius = 6371);
    telegram_sendmessage( $telegram_user_id, 'Posizione ricevuta.'.PHP_EOL.'Ti trovi a '.$int.' km di distanza dall\'epicentro');
    telegram_sendmessage( $telegram_user_id, 'Inviami la descrizione della tua posizione');
    update_post_meta( $plugin_post_id, 'telegram_custom_state', 'description_wait' );
  }

}

function telegramcustom_csv_pull() {

  $filename = ABSPATH.'segnalazioni.csv';

  telegram_log('######', 'platform', 'start csv pull '.$filename);

  global $wpdb;
  $file = 'segnalazioni';
  $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}segnalazioni;",ARRAY_A);

  if (empty($results)) {
    return;
  }

  $csv_output = '"'.implode('","',array_keys($results[0])).'"';

  foreach ($results as $row) {
    $csv_output .= "\r\n".'"'.implode('","',$row).'"';
  }

  $filename = $file."_".date("Y-m-d_H-i",time());
  header("Content-type: text/csv; charset=utf-8");
  header("Content-disposition: csv" . date("Y-m-d") . ".csv");
  header( "Content-disposition: filename=".$filename.".csv");
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

?>
