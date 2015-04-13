# Téléinfo & Raspberry-Pi

## Présentation
ce script est basé sur l'article de 
[ce blog](http://www.magdiblog.fr/gpio/teleinfo-edf-suivi-conso-de-votre-compteur-electrique/) à suivre pour le coté éléctronique mon script ne fait que remplacer le sien, pour tout le reste je vous conseille de suivre son tuto. 
## Pré requis
* Un serveur web (apache, nginx, lighttpd, ...) à monter tout seul.
* PHP 5.4
* Serveur MySQL 
* cron
* git (mais vous pouvez vous en passer si vous téléchargez le repo github)

## Installation
Trouvez la racine de vore serveur web ``/var/www/`` pour moi 
faites un :

``git clone https://github.com/pkshetlie/EdfPi.git edf/``

Créez la base de donnée à l'aide du fichier __teleinfo.sql__

et installer un cron pour le user __pi__, voici ce que ca donne pour moi:

``* * * * * /usr/bin/php /var/www/edf/teleinfo_puissance.php``

j'ai choisi de l'effectuer le relevé téléinformation toutes les minutes






