translating the plugin:

- create a po file for your language, for example de_DE.po
- copy all text from provided plugin pot file into your po file
- edit the po file with poedit http://www.poedit.net/
- create the mo file and save both files in the lang directory of the plugin
- edit wp-config.php file in your installation and set WPLANG so it matches your languages: define('WPLANG', 'de_DE');
  or if WordPress version 4+ just select your language in Admin under Settings -> General -> Site Language.

Plugin should now display all messages in your language.

-- BACKUP YOUR LANGUAGE PO/MO FILES  --

On a plugin update, language files are *NOT* preserved.
You will need to manually restore your po/mo files right after the update.
Or, send us a copy of your translation files and we'll add them in an update!
