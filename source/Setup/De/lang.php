<?php

/**
 * This file is part of O3-Shop.
 *
 * O3-Shop is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * O3-Shop is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright  Copyright (c) 2022 OXID eSales AG (https://www.oxid-esales.com)
 * @copyright  Copyright (c) 2022 O3-Shop (https://www.o3-shop.com)
 * @license    https://www.gnu.org/licenses/gpl-3.0  GNU General Public License 3 (GPLv3)
 */

$aLang = [

'charset'                                       => 'UTF-8',
'HEADER_META_MAIN_TITLE'                        => 'O3-Shop Installationsassistent',
'HEADER_TEXT_SETUP_NOT_RUNS_AUTOMATICLY'        => 'Sollte das Setup nicht nach einigen Sekunden automatisch weiterspringen, dann klicken Sie bitte',
'FOOTER_OXID_ESALES'                            => '&copy; O3-Shop 2022 - '.@date("Y").', &copy; OXID eSales AG 2003 - 2022',

'TAB_0_TITLE'                                   => 'Voraussetzungen',
'TAB_1_TITLE'                                   => 'Willkommen',
'TAB_2_TITLE'                                   => 'Lizenzbedingungen',
'TAB_3_TITLE'                                   => 'Datenbank',
'TAB_4_TITLE'                                   => 'Verzeichnisse & Login',
'TAB_5_TITLE'                                   => 'Lizenz',
'TAB_6_TITLE'                                   => 'Fertigstellen',

'TAB_0_DESC'                                    => 'Überprüfen, ob Ihr System die Voraussetzungen erfüllt',
'TAB_1_DESC'                                    => 'Herzlich willkommen<br>zur Installation von O3-Shop',
'TAB_2_DESC'                                    => 'Bestätigen Sie die Lizenzbedingungen',
'TAB_3_DESC'                                    => 'Zugangsdaten für Datenbank eingeben, Datenbankverbindung testen',
'TAB_4_DESC'                                    => 'Verzeichnisse und Admin-Zugangsdaten einrichten, Datenbank aktualisieren, Migration starten',
'TAB_5_DESC'                                    => 'Lizenzschlüssel eintragen',
'TAB_6_DESC'                                    => 'Installation erfolgreich',

'HERE'                                          => 'hier',

'ERROR_NOT_AVAILABLE'                           => 'FEHLER: %s nicht vorhanden!',
'ERROR_NOT_WRITABLE'                            => 'FEHLER: %s nicht beschreibbar!',
'ERROR_DB_CONNECT'                              => 'FEHLER: Keine Datenbankverbindung möglich!',
'ERROR_OPENING_SQL_FILE'                        => 'FEHLER: Kann SQL Datei %s nicht öffnen!',
'ERROR_FILL_ALL_FIELDS'                         => 'FEHLER: Bitte alle notwendigen Felder ausfüllen!',
'ERROR_COULD_NOT_CREATE_DB'                     => 'FEHLER: Datenbank %s nicht vorhanden und kann auch nicht erstellt werden!',
'ERROR_DB_ALREADY_EXISTS'                       => 'FEHLER: Es scheint, als ob in der Datenbank %s bereits eine O3-Shop Datenbank vorhanden ist. Bitte löschen Sie diese!',
'ERROR_BAD_SQL'                                 => 'FEHLER: (Tabellen)Probleme mit folgenden SQL Befehlen: ',
'ERROR_BAD_DEMODATA'                            => 'FEHLER: (Demodaten)Probleme mit folgenden SQL Befehlen: ',
'ERROR_NO_DEMODATA_INSTALLED'                   => 'ERROR: Demodaten-Paket ist nicht installiert. Bitte installieren Sie zuerst die Demodaten.',
'NOTICE_NO_DEMODATA_INSTALLED'                  => 'Demodaten-Paket ist nicht installiert. Bitte installieren Sie zuerst die Demodaten. Details dazu finden Sie im Abschnitt Installation der Datei README.md.',
'ERROR_CONFIG_FILE_IS_NOT_WRITABLE'             => 'FEHLER: %s/config.inc.php' . ' nicht beschreibbar!',
'ERROR_COULD_NOT_OPEN_CONFIG_FILE'              => 'Konnte config.inc.php nicht öffnen. Bitte in unserer FAQ oder im Forum nachlesen oder den O3 Support kontaktieren.',
'ERROR_COULD_NOT_FIND_FILE'                     => 'Setup konnte die Datei \"%s\" nicht finden!',
'ERROR_COULD_NOT_READ_FILE'                     => 'Setup konnte die Datei \"%s\" nicht öffnen!',
'ERROR_COULD_NOT_WRITE_TO_FILE'                 => 'Setup konnte nicht in die Datei \"%s\" schreiben!',
'ERROR_PASSWORD_TOO_SHORT'                      => 'Passwort zu kurz',
'ERROR_PASSWORDS_DO_NOT_MATCH'                  => 'Passwörter stimmen nicht überein',
'ERROR_USER_NAME_DOES_NOT_MATCH_PATTERN'        => 'Bitte geben Sie eine gültige E-Mail-Adresse ein!',
'ERROR_MYSQL_VERSION_DOES_NOT_FIT_REQUIREMENTS' => 'Die Datenbank Version erfüllt nicht die Systemvoraussetzungen!',

'ERROR_VIEWS_CANT_CREATE'                       => 'FEHLER: Kann Views nicht erstellen. Bitte prüfen Sie Ihre Benutzerrechte für die Datenbank.',
'ERROR_VIEWS_CANT_SELECT'                       => 'FEHLER: Kann nicht auf Views zugreifen. Bitte prüfen Sie Ihre Benutzerrechte für die Datenbank.',
'ERROR_VIEWS_CANT_DROP'                         => 'FEHLER: Kann Views nicht löschen. Bitte prüfen Sie Ihre Benutzerrechte für die Datenbank.',

'MOD_PHP_EXTENNSIONS'                           => 'PHP Erweiterungen',
'MOD_PHP_CONFIG'                                => 'PHP Konfiguration',
'MOD_SERVER_CONFIG'                             => 'Server-Konfiguration',

'MOD_MOD_REWRITE'                               => 'Apache mod_rewrite Modul',
'MOD_SERVER_PERMISSIONS'                        => 'Dateizugriffsrechte',
'MOD_ALLOW_URL_FOPEN'                           => 'allow_url_fopen und fsockopen auf Port 80',
'MOD_PHP4_COMPAT'                               => 'Zend Kompatibilitätsmodus muss ausgeschaltet sein',
// @deprecated since v.6.5.1 (2020-02-12);
'MOD_PHP_VERSION'                               => 'PHP Version ab 7.4 bis 8.2',
// END deprecated
'MOD_REQUEST_URI'                               => 'REQUEST_URI vorhanden',
'MOD_LIB_XML2'                                  => 'LIB XML2',
'MOD_PHP_XML'                                   => 'DOM',
'MOD_J_SON'                                     => 'JSON',
'MOD_I_CONV'                                    => 'ICONV',
'MOD_TOKENIZER'                                 => 'Tokenizer',
'MOD_BC_MATH'                                   => 'BCMath',
'MOD_MYSQL_CONNECT'                             => 'PDO_MySQL',
'MOD_MYSQL_VERSION'                             => 'MySQL Version 5.5, 5.7, 8.0 oder MariaDB 10',
'MOD_GD_INFO'                                   => 'GDlib v2 incl. JPEG Unterstützung',
'MOD_INI_SET'                                   => 'ini_set erlaubt',
'MOD_REGISTER_GLOBALS'                          => 'register_globals muss ausgeschaltet sein',
'MOD_MAGIC_QUOTES_GPC'                          => 'magic_quotes_gpc muss ausgeschaltet sein',
'MOD_ZEND_OPTIMIZER'                            => 'Zend Guard Loader installiert',
'MOD_ZEND_PLATFORM_OR_SERVER'                   => 'Zend Platform oder Zend Server installiert',
'MOD_MB_STRING'                                 => 'mbstring',
'MOD_CURL'                                      => 'cURL',
'MOD_OPEN_SSL'                                  => 'OpenSSL',
'MOD_SOAP'                                      => 'SOAP',
'MOD_UNICODE_SUPPORT'                           => 'UTF-8 Unterstützung',
'MOD_FILE_UPLOADS'                              => 'Hochladen von Dateien erlaubt (file_uploads)',
'MOD_BUG53632'                                  => 'Mögliche Probleme mit Server durch PHP Bugs',
'MOD_SESSION_AUTOSTART'                         => 'session.auto_start muss ausgeschaltet sein',
'MOD_MEMORY_LIMIT'                              => 'PHP Memory limit (min. 32MB, 60MB empfohlen)',

'STEP_0_ERROR_TEXT'                             => 'Ihr System erfüllt nicht alle nötigen Systemvoraussetzungen',
'STEP_0_ERROR_URL'                              => 'https://docs.o3-shop.com/eshop/de/latest/installation/neu-installation/server-und-systemvoraussetzungen.html',
'STEP_0_TEXT'                                   => '<ul class="req">' .
                                                   '<li class="pass"> - Die Voraussetzung ist erfüllt.</li>' .
                                                   '<li class="pmin"> - Die Voraussetzung ist nicht oder nur teilweise erfüllt. Der O3-Shop funktioniert trotzdem und kann installiert werden.</li>' .
                                                   '<li class="fail"> - Die Voraussetzung ist nicht erfüllt. Der O3-Shop funktioniert nicht ohne diese Voraussetzung und kann nicht installiert werden.</li>' .
                                                   '<li class="null"> - Die Voraussetzung konnte nicht überprüft werden.' .
                                                   '</ul>',
'STEP_0_DESC'                                   => 'In diesem Schritt wird überprüft, ob Ihr System die Voraussetzungen erfüllt:',
'STEP_0_TITLE'                                  => 'Systemvoraussetzungen überprüfen',

'STEP_1_TITLE'                                  => 'Willkommen',
'STEP_1_DESC'                                   => 'Willkommen beim Installationsassistenten für den O3-Shop',
'STEP_1_TEXT'                                   => '<p>Um eine erfolgreiche und einfache Installation zu gewährleisten, nehmen Sie sich bitte die Zeit, die folgenden Punkte aufmerksam zu lesen und Schritt für Schritt auszuführen.</p> <p>Viel Erfolg mit Ihrem O3-Shop wünscht Ihnen</p>',
'STEP_1_ADDRESS'                                => 'die O3-Shop Community<br>',
'BUTTON_BEGIN_INSTALL'                          => 'Shopinstallation beginnen',
'BUTTON_PROCEED_INSTALL'                        => 'Setup beginnen',

'STEP_2_TITLE'                                  => 'Lizenzbedingungen',
'BUTTON_RADIO_LICENCE_ACCEPT'                   => 'Ich akzeptiere die Lizenzbestimmungen.',
'BUTTON_RADIO_LICENCE_NOT_ACCEPT'               => 'Ich akzeptiere die Lizenzbestimmungen nicht.',
'BUTTON_LICENCE'                                => 'Weiter',

'STEP_3_TITLE'                                  => 'Datenbank',
'STEP_3_DESC'                                   => 'Nun wird die Datenbank erstellt und mit den notwendigen Tabellen befüllt. Dazu benötigen wir einige Angaben von Ihnen:',
'STEP_3_DB_HOSTNAME'                            => 'Datenbankserver Hostname oder IP Adresse',
'STEP_3_DB_PORT'                                => 'Datenbankserver TCP Port',
'STEP_3_DB_USER_NAME'                           => 'Datenbank Benutzername',
'STEP_3_DB_PASSWORD'                            => 'Datenbank Passwort',
'STEP_3_DB_PASSWORD_SHOW'                       => 'Passwort anzeigen',
'STEP_3_DB_DATABSE_NAME'                        => 'Datenbank Name',
'STEP_3_DB_DEMODATA'                            => 'Demodaten',
'STEP_3_CREATE_DB_WHEN_NO_DB_FOUND'             => 'Falls die Datenbank nicht vorhanden ist, wird versucht diese anzulegen',
'BUTTON_RADIO_INSTALL_DB_DEMO'                  => 'Demodaten installieren',
'BUTTON_RADIO_NOT_INSTALL_DB_DEMO'              => 'Demodaten <strong>nicht</strong> installieren',
'BUTTON_DB_CREATE'                              => 'Datenbank jetzt erstellen',

'STEP_3_1_TITLE'                                => 'Datenbank - in Arbeit ...',
'STEP_3_1_DB_CONNECT_IS_OK'                     => 'Datenbank Verbindung erfolgreich geprüft ...',
'STEP_3_1_DB_CREATE_IS_OK'                      => 'Datenbank %s erfolgreich erstellt ...',

'STEP_4_TITLE'                                  => 'Einrichten des O3-Shops',
'STEP_4_DESC'                                   => 'Bitte geben Sie hier die für den Betrieb notwendigen Daten ein:',
'STEP_4_SHOP_URL'                               => 'Shop URL',
'STEP_4_SHOP_DIR'                               => 'Verzeichnis auf dem Server zum Shop',
'STEP_4_SHOP_TMP_DIR'                           => 'Verzeichnis auf dem Server zum TMP Verzeichnis',
'STEP_4_ADMIN_LOGIN_NAME'                       => 'Administrator E-Mail (wird als Benutzername verwendet)',
'STEP_4_ADMIN_PASS'                             => 'Administrator Passwort',
'STEP_4_ADMIN_PASS_CONFIRM'                     => 'Administrator Passwort bestätigen',
'STEP_4_ADMIN_PASS_MINCHARS'                    => 'frei wählbar, mindestens 6 Zeichen',

'STEP_4_1_TITLE'                                => 'Verzeichnisse - in Arbeit ...',
'STEP_4_1_DATA_WAS_WRITTEN'                     => 'Kontrolle und Schreiben der Dateien erfolgreich!<br>Bitte warten ...',
'BUTTON_WRITE_DATA'                             => 'Daten jetzt speichern',

'STEP_4_2_TITLE'                                => 'Datenbank - Tabellen erstellen ...',
'STEP_4_2_OVERWRITE_DB'                         => 'Falls Sie dennoch installieren wollen und die alten Daten überschreiben, klicken Sie ',
'STEP_4_2_NOT_RECOMMENDED_MYSQL_VERSION'        => 'Falls Sie dennoch installieren wollen, klicken Sie ',
'STEP_4_2_UPDATING_DATABASE'                    => 'Datenbank erfolgreich aktualisiert. Bitte warten ...',

'STEP_6_TITLE'                                  => 'O3-Shop Einrichtung erfolgreich',
'STEP_6_DESC'                                   => 'Die Einrichtung Ihres O3-Shop wurde erfolgreich abgeschlossen.',
'STEP_6_LINK_TO_SHOP'                           => 'Hier geht es zu Ihrem Shop',
'STEP_6_LINK_TO_SHOP_ADMIN_AREA'                => 'Zugang zu Ihrer Shop Administration',
'STEP_6_TO_SHOP'                                => 'Zum Shop',
'STEP_6_TO_SHOP_ADMIN'                          => 'Zur Shop Administration',

'ATTENTION'                                     => 'Bitte beachten Sie',
'SETUP_DIR_DELETE_NOTICE'                       => 'WICHTIG: Bitte löschen Sie Ihr Setup-Verzeichnis falls dieses nicht bereits automatisch entfernt wurde!',
'SETUP_CONFIG_PERMISSIONS'                      => 'WICHTIG: Aus Sicherheitsgründen setzen Sie Ihre config.inc.php Datei auf read-only-Modus!',

'SELECT_SETUP_LANG'                             => 'Sprache für Installation',
'SELECT_PLEASE_CHOOSE'                          => 'Bitte auswählen',
'SELECT_DELIVERY_COUNTRY'                       => 'Hauptlieferland',
'SELECT_DELIVERY_COUNTRY_HINT'                  => 'Aktivieren Sie weitere Lieferländer im Administrationsbereich, falls benötigt.',
'SELECT_SHOP_LANG'                              => 'Sprache für Shop',
'SELECT_SHOP_LANG_HINT'                         => 'Aktivieren Sie weitere Sprachen im Administrationsbereich, falls gewünscht.',
'SELECT_SETUP_LANG_SUBMIT'                      => 'Auswählen',
'PRIVACY_POLICY'                                => 'Datenschutzerläuterungen',

'ERROR_SETUP_CANCELLED'                         => 'Das Setup wurde abgebrochen, weil Sie die Lizenzvereinbarungen nicht akzeptiert haben.',
'BUTTON_START_INSTALL'                          => 'Setup erneut starten',

'EXTERNAL_COMMAND_ERROR_1'                      => 'Fehler beim Ausführen des Kommandos \'%s\'. Returncode: \'%d\'.',
'EXTERNAL_COMMAND_ERROR_2'                      => 'Das Kommando gibt folgende Meldung zurück:',
];
