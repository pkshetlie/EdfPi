<?php

class TeleDatas{
    private $database;

    private $_db ;
    private $trame = array();

    public function __construct($database = 'teleinfo')
    {
        $this->database = $database;
        $this->initDataBases();
    }

    public function getPuissanceByTimestamp($ts){

        $results = $this->_db->query("SELECT * FROM puissance WHERE timestamp > ".$ts." ORDER BY timestamp ASC; LIMIT 0,1");
        if($results->rowCount()>0){
            return $results->fetchObject();
        }
        return null;
    }

    public function getConsomationTempsReel()
    {
        $now = time();
        $past = strtotime("-1 hour", $now);
        $timestamp = 0;
        $results = $this->_db->query("SELECT * FROM puissance WHERE timestamp > $past ORDER BY timestamp ASC;");
        $datas = array();
        while ($row = $results->fetch()) {
            $datas[] =  ($row['va']);
            $timestamp = $row['timestamp'];
        }
        return array($datas,$timestamp);
    }

    private function initDataBases(){
        $this->_db = new PDO('mysql:host=localhost;dbname=' . $this->database, USER, PWD);
    }
    public function getCompteurInfos(){
        $results = $this->_db->query("SELECT * FROM conso ORDER BY timestamp DESC LIMIT 0,1;");
        $obj = $results->fetchObject();
        return $obj;
    }

    public function getTrame(){
        $results = $this->_db->query("SELECT * FROM trame ORDER BY id DESC LIMIT 0,1;");
        $obj = $results->fetchObject();
        return unserialize($obj->trame);
    }
//
//  recupere les donnees de puissance des $nb_days derniers jours et les met en forme pour les affichers sur le graphique
//
    public function getDatasPuissance($nb_days)
    {

        $months = array('01' => 'janv', '02' => 'fev', '03' => 'mars', '04' => 'avril', '05' => 'mai', '06' => 'juin', '07' => 'juil', '08' => 'aout', '09' => 'sept', '10' => 'oct', '11' => 'nov', '12' => 'dec');
        $now = time();
        $past = strtotime("-$nb_days day", $now);

        $results = $this->_db->query("SELECT * FROM puissance WHERE timestamp > $past ORDER BY timestamp ASC;");

        $datas = array();

        while ($row = $results->fetch()) {
            $year = date("Y", $row['timestamp']);
            $month = date("n", $row['timestamp'] - 1);
            $day = date("j", $row['timestamp']);
            $hour = date("G", $row['timestamp']);
            $minute = date("i", $row['timestamp']);
            $second = date("s", $row['timestamp']);
            $datas[] = "[{v:new Date($year, $month, $day, $hour, $minute, $second), f:'" . date("j", $row['timestamp']) . " " . $months[date("m", $row['timestamp'])] . " " . date("H\hi", $row['timestamp']) . "'}, {v:" . ($row['va']/1000) . ", f:'" . ($row['va']/1000) . " V.A'}, {v:" . ($row['watt']/1000) . ", f:'" . ($row['watt']/1000) . " kW'}]";

        }

        return implode(', ', $datas);
    }


//
//  recupere les donnees de consommation des $nb_days derniers jours et les met en forme pour les affichers sur le graphique
//
    public function getDatasConso($nb_days)
    {
        $months = array('01' => 'janv', '02' => 'fev', '03' => 'mars', '04' => 'avril', '05' => 'mai', '06' => 'juin', '07' => 'juil', '08' => 'aout', '09' => 'sept', '10' => 'oct', '11' => 'nov', '12' => 'dec');
        $now = time();
        $past = strtotime("-$nb_days day", $now);

        $results = $this->_db->query("SELECT * FROM conso WHERE timestamp > $past ORDER BY timestamp ASC;");

        $datas = array();

        while ($row = $results->fetch()) {
            $day = date("j", $row['timestamp']) . " " . $months[date("m", $row['timestamp'])];
            $datas[] = "['" . $day . "', {v:" . $row['daily_hp'] . ", f:'" . $row['daily_hp'] . " kWh'}, {v:" . $row['daily_hc'] . ", f:'" . $row['daily_hc'] . " kWh'}]";
        }

        return implode(', ', $datas);
    }

}