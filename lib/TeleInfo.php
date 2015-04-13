<?php
class TeleInfo
{
    private $device;
    private $database;

    private $_db;
    private $trame = array();

    public function __construct($database = 'teleinfo', $device = '/dev/ttyAMA0')
    {
        $this->device = $device;
        $this->database = $database;
        $this->initDataBases();
        $this->trame = $this->getTrame();
    }

    private function initDataBases()
    {
        $this->_db = new PDO('mysql:host=localhost;dbname=' . $this->database, USER, PWD);
    }

    private function getTrame()
    {
        $handle = fopen($this->device, "r"); // ouverture du flux
        while (fread($handle, 1) != chr(2)) ; // on attend la fin d'une trame pour commencer a avec la trame suivante
        $char = '';
        $trame = '';
        $datas = '';
        while ($char != chr(2)) { // on lit tous les caracteres jusqu'a la fin de la trame
            $char = fread($handle, 1);
            if ($char != chr(2)) {
                $trame .= $char;
            }
        }
        fclose($handle); // on ferme le flux
        $trame = chop(substr($trame, 1, -1)); // on supprime les caracteres de debut et fin de trame
        $messages = explode(chr(10), $trame); // on separe les messages de la trame
        foreach ($messages as $key => $message) {
            $message = explode(' ', $message, 3); // on separe l'etiquette, la valeur et la somme de controle de chaque message
            if (!empty($message[0]) && !empty($message[1])) {
                $etiquette = $message[0];
                $valeur = $message[1];
                $datas[$etiquette] = $valeur; // on stock les etiquettes et les valeurs de l'array datas
            }
        }
        $this->_db->prepare("INSERT INTO trame (trame) VALUES (?);")->execute(array(serialize($datas)));

        return $datas;
    }

    /**
     * enregistre la puissance instantanée en V.A et en W
     * @return bool
     */
    public function registerPuissance()
    {
        $datas = array();
        $datas['timestamp'] = time();
        $datas['hchp'] = substr($this->trame['PTEC'], 0, 2); // indicateur heure pleine/creuse, on garde seulement les carateres HP (heure pleine) et HC (heure creuse)
        $datas['va'] = preg_replace('#^[0]*#isU', '', $this->trame['PAPP']); // puissance en V.A, on supprime les 0 en debut de chaine
        $datas['iinst'] = preg_replace('#^[0]*#isU', '', $this->trame['IINST']); // intensité instantanée en A, on supprime les 0 en debut de chaine
        $datas['watt'] = $datas['iinst'] * 220; // intensite en A X 220 V
        $this->_db->exec("INSERT INTO puissance (timestamp, hchp, va, iinst, watt) VALUES (" . $datas['timestamp'] . ", '" . $datas['hchp'] . "', " . $datas['va'] . ", " . $datas['iinst'] . ", " . $datas['watt'] . ");");

        return true;
    }

    /**
     * enregistre la consommation en Wh
     */
    public function registerConsomation()
    {

        $today = strtotime('today 00:00:00');
        $yesterday = strtotime("-1 day 00:00:00");
        $previous = $this->_db->query("SELECT * FROM conso WHERE timestamp = '" . $today . "';")->fetch();
        if (!empty($previous)) {
            return false;
        }

        // recupere la conso totale enregistree la veille pour pouvoir calculer la difference et obtenir la conso du jour
        $previous = $this->_db->query("SELECT * FROM conso WHERE timestamp = '" . $yesterday . "';")->fetch();

        if (empty($previous)) {
            $previous = array();
            $previous['timestamp'] = $yesterday;
            $previous['total_hc'] = 0;
            $previous['total_hp'] = 0;
            $previous['daily_hc'] = 0;
            $previous['daily_hp'] = 0;
        }

        $datas = array();
        $datas['query'] = 'hchp';
        $datas['timestamp'] = $today;
        $datas['total_hc'] = preg_replace('#^[0]*#', '', $this->trame['HCHC']); // conso total en Wh heure creuse, on supprime les 0 en debut de chaine
        $datas['total_hp'] = preg_replace('#^[0]*#', '', $this->trame['HCHP']); // conso total en Wh heure pleine, on supprime les 0 en debut de chaine

        if ($previous['total_hc'] == 0) {
            $datas['daily_hc'] = $datas['total_hc'] / 1000;
        } else {
            $datas['daily_hc'] = ($datas['total_hc'] - $previous['total_hc']) / 1000; // conso du jour heure creuse = total aujourd'hui - total hier, on divise par 1000 pour avec un resultat en kWh
        }

        if ($previous['total_hp'] == 0) {
            $datas['daily_hp'] = $datas['total_hp'] / 1000;
        } else {
            $datas['daily_hp'] = ($datas['total_hp'] - $previous['total_hp']) / 1000; // conso du jour heure pleine = total aujourd'hui - total hier, on divise par 1000 pour avec un resultat en kWh
        }

        $this->_db->exec("INSERT INTO conso (timestamp, total_hc, total_hp, daily_hc, daily_hp) VALUES (" . $datas['timestamp'] . ", " . $datas['total_hc'] . ", " . $datas['total_hp'] . ", " . $datas['daily_hc'] . ", " . $datas['daily_hp'] . ");");


        return true;
    }
}
