# terremotocentroitalia-bot-telegram

## Telegram Bot for earthquake italian emergency (24 august 2016)

http://terremotocentroitalia.info/

Code based on the WordPress plugin https://wordpress.org/plugins/telegram-bot/

La funzione telegram_location_haversine_distance ( 42.7, 13.24, $lat, $long, $earthRadius = 6371) è la formula dell'emisenoverso e calcola la distanza (in km o in metri moltiplicando $earthRadius*1000) dall'epicentro (24-08-2016 03:36:32 Magnitudo 6.0).

## Nuova segnalazione

Invio propria posizione GPS
Inserimento di una descrizione
Invio di una foto tramite "Invia Foto" oppure digitazione "Invia" per inoltrare la segnalazione senza foto
Attendere la conferma (in caso contrario provare a rimandare la foto o la posizione GPS)
**Il processo inizia sempre con l'invio della posizione GPS.**

## Pulsanti tastiera

- segnala -> spiega come inoltrare una segnalazone (il processo parte inviando la posizione GPS)
- mappa -> mostra un'altra tastiera dalla quale si può visualizzare mappa odierna e totale
- stato -> mostra il numero delle segnalazioni inoltrate dall'utente
- info -> mostra credits

## Funzioni amministratori

Gli amministratori riceveranno, sempre all'interno del bot, la notifica di ogni foto (segnalazione).

- ok -> approva tutte le segnalazioni ricevute nel bot (stato da 0 a 1)
- d:ID -> elimina una segnalazione (stato da 0 o 1 a 2)
- as:ID -> crea issue su GitHub - https://github.com/emergenzeHack/terremotocentro_segnalazioni/

## Note

- Ogni segnalazione ha stato iniziale = 0
- Il CSV estrapola solamente le segnalazioni con stato = 1
- Il comando d:ID è attivo sia per segnalazioni con stato = 0, sia per stato = 1
- Il comando ok agisce solo su foto con stato = 0
- In caso di digitazione as:ID ma issue già creata viene ritornato errore
