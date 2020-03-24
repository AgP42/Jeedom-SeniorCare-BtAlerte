<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class seniorcarealertbt extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */


    public static function buttonAlert($_option) { // fct appelée par le listener des buttons d'alerte, n'importe quel bouton arrive ici
      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un trigger d\'un bouton d\'alerte ############');

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte
      $seniorcarealertbt->execActions('action_alert_bt'); // on appelle les actions definies pour cette personne pour les boutons d'alertes

    }

    public static function buttonAlertCancel($_option) { // fct appelée par le listener des buttons d'annulation d'alerte, n'importe quel bouton arrive ici
      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un trigger d\'un bouton d\'annulation d\'alerte ############');

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte
      $seniorcarealertbt->execActions('action_cancel_alert_bt'); // on appelle les actions definies pour cette personne pour les boutons d'alertes

    }

    public static function sensorSecurity($_option) { // fct appelée par le listener des capteurs de sécurité, n'importe quel capteur arrive ici
      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un trigger de sécurité ############');

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte
      foreach ($seniorcarealertbt->getConfiguration('security') as $security) { // on boucle direct dans la conf
        if ('#' . $_option['event_id'] . '#' == $security['cmd']) { // on cherche quel est l'event qui nous a déclenché pour pouvoir chopper son nom et type (utilisé pour les tags)

          log::add('seniorcarealertbt', 'debug', 'boucle capteurs security, name : ' . $security['name'] . ' - cmd : ' . $security['cmd']  . ' - ' . $security['sensor_security_type']);
          $seniorcarealertbt->execActions('action_security', $security['name'], $security['sensor_security_type']); // on appelle les actions definies pour cette personne

        }
      } // fin foreach tous les capteurs security de la conf
    }

    public static function sensorSecurityCancel($_option) { // fct appelée par le listener des boutons d'annulation de l'alerte de sécurité, n'importe quel capteur arrive ici
      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un bouton d\'annulation d\'alerte de sécurité ############');

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte
      $seniorcarealertbt->execActions('action_cancel_security'); // on appelle les actions definies pour cette personne

    }

    public static function checkAndActionSeuilsSensorConfort($seniorcarealertbt, $_name, $_cmd, $_seuilBas, $_seuilHaut, $_type) { // appelée soit par le cron15, soit par un listener (via la fct sensorConfort - desactivée), va regarder si on est dans les seuils définis et si non appliquer les actions voulues

    // TODO on pourrait ajouter une durée min pendant laquelle le capteur est hors seuils avant de déclencher l'alerte
    // TODO on pourrait ajouter la date de collecte de la valeur pour ne pas faire des alertes sur une vieille info, ou au contraire ajouter une alerte si pas de valeur fraiche pendant un certain temps. Mais ça peut etre aussi géré par le core dans les configuration de la cmd...

      $now = time();
      $rep_warning = $seniorcarealertbt->getConfiguration('repetition_warning');
      $tempsDepuisActionWarningConfort = $now - $seniorcarealertbt->getCache('actionWarningConfortStartTimestamp' . $_cmd); // on garde 1 cache par cmd
      $warningConfortLauched = $seniorcarealertbt->getCache('WarningConfortLauched' . $_cmd);

      $valeur = jeedom::evaluateExpression($_cmd);

      log::add('seniorcarealertbt', 'debug', 'Fct checkAndActionSeuilsSensorConfort, name : ' . $_name . ' - ' . $_type . ' - ' . $_cmd . ' - ' . $valeur . ' - ' . $_seuilBas . ' - ' . $_seuilHaut . ' - Rep warning : ' . $seniorcarealertbt->getConfiguration('repetition_warning'));

      log::add('seniorcarealertbt', 'debug', 'Fct checkAndActionSeuilsSensorConfort, WarningConfortLauched : ' . $warningConfortLauched . ' - last action lancé il y a (min) : ' . $tempsDepuisActionWarningConfort / 60);

      if(!is_numeric($valeur)){

        log::add('seniorcarealertbt', 'debug', 'Capteurs confort :' . $_name . ' la valeur est pas numerique, on fait rien !');

      } else if (($valeur <= $_seuilHaut && $valeur >= $_seuilBas) && !$warningConfortLauched){ // on est dans les seuils et on a pas lancé notre warning : aucune action a lancer

        log::add('seniorcarealertbt', 'debug', 'Capteurs confort :' . $_name . ' dans les seuils, on fait rien');
        return 1; // on retourne que notre capteur est ok dans les seuils

      } else if (($valeur <= $_seuilHaut && $valeur >= $_seuilBas) && $warningConfortLauched){ // on est dans les seuils et on a précédemment lancé notre warning => actions de retour à la normal

        log::add('seniorcarealertbt', 'debug', 'Capteurs confort :' . $_name . ' retour à la normal !');
        $seniorcarealertbt->setCache('WarningConfortLauched' . $_cmd, false); // on remet dans le cache qu'on a pas lancé les actions
        $seniorcarealertbt->execActions('action_cancel_warning_confort', $_name, $_type, $valeur, $_seuilBas, $_seuilHaut); // appel de la boucle d'execution des actions avec les infos pour les tag des messages
        return 1;

      } else if (($valeur > $_seuilHaut || $valeur < $_seuilBas) && // si la valeur sort des seuils et selon le choix de repetition
         ($rep_warning == '' ||
         ($rep_warning == '15min' &&  $tempsDepuisActionWarningConfort >= 60*14) || // si on a pas defini la repetition de warning ou si defini sur "15min" ou si
         ($rep_warning == 'once' && !$warningConfortLauched) || // rep_warning est sur "1fois" et qu'on ne l'a pas encore lancé ou si
         ($rep_warning == '1hour' && $tempsDepuisActionWarningConfort >= 60*59) || // rep_warning sur 1h et dernier lancement depuis plus de 59min (pour éviter de tomber 1s apres 1h et donc de louper le rappel...)
         ($rep_warning == '6hours' && $tempsDepuisActionWarningConfort >= 60*59*6) // rep_warning sur 6h et dernier lancement depuis 6h-6min
        )){

        log::add('seniorcarealertbt', 'debug', 'Capteurs confort :' . $_name . ' est hors seuils, tempsDepuisActionWarningConfort : ' . $tempsDepuisActionWarningConfort . ' warningConfortLauched : ' . $warningConfortLauched . ' rep_warning : ' . $rep_warning);

        $seniorcarealertbt->setCache('WarningConfortLauched' . $_cmd, true); // on garde en cache qu'on a lancé nos actions au moins 1 fois pour cette commande
        $seniorcarealertbt->setCache('actionWarningConfortStartTimestamp' . $_cmd, $now); // on memorise l'heure du lancement du warning

        $seniorcarealertbt->execActions('action_warning_confort', $_name, $_type, $valeur, $_seuilBas, $_seuilHaut); // on execute toutes les actions

        return 0; // on retourne qu'on a 1 capteur hors seuil (ils doivent tous répondre 1 pour que le cron lance les actions "tous ok")

      } else { // on est pas dans les seuils mais on a deja lancé les alertes selon la repetition voulu

        log::add('seniorcarealertbt', 'debug', 'Capteurs confort :' . $_name . ' est hors seuils, mais déjà lancé les actions de warning');
        return 0; // on retourne qu'on a 1 capteur hors seuil

      } //*/

    }

// commenté car on utilise plus les listener pour les confort, juste le cron 15
/*    public static function sensorConfort($_option) { // fct appelée par le listener des capteurs conforts (on sait pas lequel, ça serait trop simple, mais on connait l'event_id et la valeur).

      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un changement d\'un capteur confort ############');

    //  log::add('seniorcarealertbt', 'debug', 'Fct sensorConfort appelé par le listener : $_option[seniorcarealertbt_id] : ' . $_option['seniorcarealertbt_id'] . ' - value : ' . $_option['value'] . ' - event_id : ' . $_option['event_id']);

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']);
      if (is_object($seniorcarealertbt) && $seniorcarealertbt->getIsEnable() == 1 ) {
        foreach ($seniorcarealertbt->getConfiguration('confort') as $confort) { // on boucle direct dans la conf
          if ('#' . $_option['event_id'] . '#' == $confort['cmd']) { // on cherche quel est l'event qui nous a déclenché

          //  log::add('seniorcarealertbt', 'debug', 'Fct sensorConfort appelé par le listener, name : ' . $confort['name'] . ' - cmd : ' . $confort['cmd']  . ' - ' . $confort['sensor_confort_type'] . ' - ' . $confort['seuilBas'] . ' - ' . $confort['seuilHaut']);

            if($confort['seuilBas'] != '' || $confort['seuilHaut'] != '') { // si les seuils sont definis (on set le listener de toutes facons maintenant)
              $seniorcarealertbt->checkAndActionSeuilsSensorConfort($seniorcarealertbt, $confort['name'], $_option['value'], $confort['seuilBas'], $confort['seuilHaut'], $confort['sensor_confort_type']);
            }

          }

        }
      }

    } //*/

    public static function sensorLifeSign($_option) { // fct appelée par le listener des capteurs d'activité, n'importe quel capteur arrive ici
      log::add('seniorcarealertbt', 'debug', '################ Detection d\'un capteur d\'activité ############');


      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']);
      $seniorcarealertbt->setCache('lastLifeSignTimestamp', time()); // on met en cache le timestamp à l'heure du dernier event. C'est le cron qui regardera toutes les min si on est hors timer

      // on recupere l'état des des warning et alertes
      $actionWarningLifeSignLaunched = $seniorcarealertbt->getCache('actionWarningLifeSignLaunched');
      $actionAlertLifeSignLaunched = $seniorcarealertbt->getCache('actionAlertLifeSignLaunched');

      log::add('seniorcarealertbt', 'debug', 'Fct sensorLifeSign appelé par le listener, seniorcarealertbt_id : ' . $_option['seniorcarealertbt_id'] . ' - value : ' . $_option['value'] . ' - event_id : ' . $_option['event_id'] . ' - timestamp mis en cache : ' . time() . ' cache lu : ' . $actionWarningLifeSignLaunched . ' - '. $actionAlertLifeSignLaunched);

      if ($actionWarningLifeSignLaunched){ // si on était en phase d'avertissement, on lance les actions d'arret warning
        $seniorcarealertbt->execActions('action_desactivate_warning_life_sign');
      }

      if ($actionAlertLifeSignLaunched){ // si on était en phase d'alerte, on lance les actions d'arret alerte
        $seniorcarealertbt->execActions('action_desactivate_alert_life_sign');
      }

      // dans tous les cas on declare qu'on est pas en phase de warning ni d'alerte, puisqu'on vient de recevoir un signe de vie
      $seniorcarealertbt->setCache('actionWarningLifeSignLaunched', false);
      $seniorcarealertbt->setCache('actionAlertLifeSignLaunched', false);

    }


    public static function cron() { //executée toutes les min par Jeedom

      log::add('seniorcarealertbt', 'debug', '#################### CRON ###################');

      //pour chaque equipement (personne) declaré par l'utilisateur
      foreach (self::byType('seniorcarealertbt',true) as $seniorcarealertbt) {

        if (is_object($seniorcarealertbt) && $seniorcarealertbt->getIsEnable() == 1) { // si notre eq existe et est actif
          //TODO : c'est ici qu'il faudra gerer l'absence de la personne de son logement

          /********* Gestion de l'onglet "détection d'inactivité" ********/

          $lifeSignDetectionDelay = $seniorcarealertbt->getConfiguration('life_sign_timer') * 60; // on va lire la durée des timers dans la conf et on le met en secondes
          $lifeSignWarningDelay = $seniorcarealertbt->getConfiguration('warning_life_sign_timer') * 60;

          $lastLifeSignTimestamp = $seniorcarealertbt->getCache('lastLifeSignTimestamp'); // on va lire le timestamp du dernier trigger, en secondes
          $actionWarningLifeSignTimestamp = $seniorcarealertbt->getCache('actionWarningLifeSignTimestamp'); // on va lire le timestamp du lancement du warning, en secondes

          $now = time(); // timestamp courant, en s
          $secSinceLastLifeSign = $now - $lastLifeSignTimestamp; // le nb de secondes écoulées depuis le dernier event
          $secSinceWarningLifeSign = $now - $actionWarningLifeSignTimestamp; // le nb de secondes écoulées depuis le lancement des actions de warning

          $actionWarningLifeSignLaunched = $seniorcarealertbt->getCache('actionWarningLifeSignLaunched'); // on recupere l'état des des warning et alertes
          $actionAlertLifeSignLaunched = $seniorcarealertbt->getCache('actionAlertLifeSignLaunched');

          log::add('seniorcarealertbt', 'debug', 'Cache lu : ' . $actionWarningLifeSignLaunched . ' - ' . $actionAlertLifeSignLaunched);

          if ($secSinceLastLifeSign > $lifeSignDetectionDelay && !$actionWarningLifeSignLaunched && !$actionAlertLifeSignLaunched){
          //= le premier timer est échu mais aucune action ni warning si alerte n'est en cours --> on va lancer les actions warning
            log::add('seniorcarealertbt', 'debug', 'Actions Warning Life Sign A lancer. Timer lu : ' . $lifeSignDetectionDelay . ', sec depuis last event : ' . $secSinceLastLifeSign);

            $seniorcarealertbt->execActions('action_warning_life_sign');

            $seniorcarealertbt->setCache('actionWarningLifeSignLaunched', true); // on memorise qu'on a lancé les actions pour ne pas repeter toutes les min
            $seniorcarealertbt->setCache('actionWarningLifeSignTimestamp', $now); // on memorise l'heure du lancement du warning

          } else if ($secSinceLastLifeSign > $lifeSignDetectionDelay // 1er timer toujours échu
            && $actionWarningLifeSignLaunched && $secSinceWarningLifeSign > $lifeSignWarningDelay // on a deja lancé les actions warning et le timer de warning est échu aussi
            && !$actionAlertLifeSignLaunched){ // mais on a pas encore lancé d'alerte --> c'est le moment de le faire !
            log::add('seniorcarealertbt', 'debug', 'Actions Alerte Life Sign à lancer, temps depuis last event : ' . $secSinceLastLifeSign . ', sec depuis le lancement du warning : ' . $secSinceWarningLifeSign);

            // lance les actions alertes
            $seniorcarealertbt->execActions('action_alert_life_sign');

            $seniorcarealertbt->setCache('actionAlertLifeSignLaunched', true); // on memorise qu'on a lancé les actions d'alertes
            //TODO : gerer la repetition d'alerte toutes les 5min par exemple ?
          }

        } // fin if eq actif

      } // fin foreach equipement

    } //fin cron

    //*
    // * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
    // Sert ici pour les capteurs conforts
      public static function cron15() {

        log::add('seniorcarealertbt', 'debug', '#################### CRON 15 ###################');

        //pour chaque equipement (personne) declaré par l'utilisateur
        foreach (self::byType('seniorcarealertbt',true) as $seniorcarealertbt) {

          if (is_object($seniorcarealertbt) && $seniorcarealertbt->getIsEnable() == 1) { // si notre eq existe et est actif

            $etatSensor = 1;
            foreach ($seniorcarealertbt->getConfiguration('confort') as $confort) { // on boucle direct dans la conf

              log::add('seniorcarealertbt', 'debug', 'Cron15 boucle capteurs confort, name : ' . $confort['name'] . ' - cmd : ' . $confort['cmd']  . ' - ' . $confort['sensor_confort_type'] . ' - ' . $confort['seuilBas'] . ' - ' . $confort['seuilHaut']);

              if($confort['seuilBas'] != '' || $confort['seuilHaut'] != '') { // évalue si on a au moins 1 seuil defini (de toute facon on peut pas n'en remplir qu'1 des deux)

                $etatSensor *= $seniorcarealertbt->checkAndActionSeuilsSensorConfort($seniorcarealertbt, $confort['name'], $confort['cmd'], $confort['seuilBas'], $confort['seuilHaut'], $confort['sensor_confort_type']);
                log::add('seniorcarealertbt', 'debug', 'Cron15 boucle capteurs confort, etatSensor : ' . $etatSensor);
                // il suffit qu'il y ai 1 capteur qui renvoie 0 pour que notre $etatSensor passe a 0
              }

            } // fin foreach tous les capteurs conforts de la conf

            if($etatSensor){ // ils ont tous repondu 1, on va lancer les actions
              $seniorcarealertbt->execActions('action_cancel_all_warning_confort'); // appel de la boucle d'execution des actions avec les infos pour les tag des messages
            }

          } // fin if eq actif

        } // fin foreach equipement

      } //*/

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */




    public function execActions($_config, $_sensor_name = NULL, $_sensor_type = NULL, $_sensor_value = NULL, $_seuilBas = NULL, $_seuilHaut = NULL) { // on donne le type d'action en argument et ca nous execute toute la liste. Les autres arguments sont pour les tag des messages si applicable

      log::add('seniorcarealertbt', 'debug', '################ Execution des actions du type ' . $_config . ' pour ' . $this->getName() .  ' ############');

      foreach ($this->getConfiguration($_config) as $action) { // on boucle pour executer toutes les actions définies
        try {
          $options = array(); // va permettre d'appeler les options de configuration des actions, par exemple un scenario un message
          if (isset($action['options'])) {
            $options = $action['options'];
            foreach ($options as $key => $value) { // ici on peut définir les "tag" de configuration qui seront à remplacer par des variables
              // str_replace ($search, $replace, $subject) retourne une chaîne ou un tableau, dont toutes les occurrences de search dans subject ont été remplacées par replace.
              $value = str_replace('#senior_name#', $this->getName(), $value);
              $value = str_replace('#sensor_name#', $_sensor_name, $value);
              $value = str_replace('#sensor_type#', $_sensor_type, $value);
              $value = str_replace('#sensor_value#', $_sensor_value, $value);
              $value = str_replace('#low_threshold#', $_seuilBas, $value);
              switch ($_sensor_type) {
                  case 'temperature':
                      $unit = '°C';
                      break;
                  case 'humidity':
                      $unit = '%';
                      break;
                  case 'co2':
                      $unit = 'ppm';
                      break;
                  default:
                      $unit = '';
                      break;
              }
              $value = str_replace('#unit#', $unit, $value);
              $options[$key] = str_replace('#high_threshold#', $_seuilHaut, $value);
            }
          }
          scenarioExpression::createAndExec('action', $action['cmd'], $options);
        } catch (Exception $e) {
          log::add('seniorcarealertbt', 'error', $this->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
        }
      } //*/

    }

    /*     * *********************Méthodes d'instance************************* */

    public function cleanAllListener() {

      log::add('seniorcarealertbt', 'debug', 'Fct cleanAllListener pour : ' . $this->getName());

      $listeners = listener::byClass('seniorcarealertbt'); // on prend tous nos listeners de ce plugin, pour toutes les personnes
      foreach ($listeners as $listener) {
        $seniorcarealertbt_id_listener = $listener->getOption()['seniorcarealertbt_id'];

    //    log::add('seniorcarealertbt', 'debug', 'cleanAllListener id lue : ' . $seniorcarealertbt_id_listener . ' et nous on est l id : ' . $this->getId());

        if($seniorcarealertbt_id_listener == $this->getId()){ // si on correspond a la bonne personne, on le vire
          $listener->remove();
        }

      }


// sinon on a la version bourin, à mettre a jour pour chaque nouveau type de listener... :
/*      $listener = listener::byClassAndFunction('seniorcarealertbt', 'sensorLifeSign', array('seniorcarealertbt_id' => intval($this->getId())));
      if (is_object($listener)) {
        $listener->remove();
      }
      $listener = listener::byClassAndFunction('seniorcarealertbt', 'buttonAlert', array('seniorcarealertbt_id' => intval($this->getId())));
      if (is_object($listener)) {
        $listener->remove();
      }
      $listener = listener::byClassAndFunction('seniorcarealertbt', 'sensorConfort', array('seniorcarealertbt_id' => intval($this->getId())));
      if (is_object($listener)) {
        $listener->remove();
      }
      $listener = listener::byClassAndFunction('seniorcarealertbt', 'sensorSecurity', array('seniorcarealertbt_id' => intval($this->getId())));
      if (is_object($listener)) {
        $listener->remove();
      } //*/

    }

    public function preInsert() {

    }

    public function postInsert() {

    }

    public function preSave() {

    }

    // fct appellée par Jeedom aprés l'enregistrement de la configuration
    public function postSave() {


      //########## 1 - On va lire la configuration des capteurs dans le JS et on la stocke dans un grand tableau #########//

      $jsSensors = array(
        'life_sign' => array(), // sous-tableau pour stocker toutes les infos des capteurs de détection d'activité
        'alert_bt' => array(), // idem bouton d'alertes
        'cancel_alert_bt' => array(), // boutons d'annulation alerte immédiate
        'confort' => array(), // idem capteurs conforts
        'security' => array(), // idem capteurs sécurité
        'cancel_security' => array(), // boutons d'annulation alerte sécurité
      );

      foreach ($jsSensors as $key => $jsSensor) { // on boucle dans tous nos types de capteurs pour recuperer les infos
        log::add('seniorcarealertbt', 'debug', 'Boucle de $jsSensors : key : ' . $key);

        if (is_array($this->getConfiguration($key))) {
          foreach ($this->getConfiguration($key) as $sensor) {
            if ($sensor['name'] != '' && $sensor['cmd'] != '') { // si le nom et la cmd sont remplis

              $jsSensors[$key][$sensor['name']] = $sensor; // on stocke toute la conf, c'est à dire tout ce qui dans notre js avait la class "expressionAttr". Pour retrouver notre champs exact : $jsSensors[$key][$sensor['name']][data-l1key]. // attention ici a ne pas remplacer $jsSensors[$key] par $jsSensor. C'est bien dans le tableau d'origine qu'on veut écrire, pas dans la variable qui le represente dans cette boucle
              log::add('seniorcarealertbt', 'debug', 'Capteurs sensor config lue : ' . $sensor['name'] . ' - ' . $sensor['cmd']);

            }
          }
        }
      }

      //########## 2 - On boucle dans toutes les cmd existantes, pour les modifier si besoin #########//


      foreach ($jsSensors as $key => $jsSensor) { // on boucle dans tous nos différents types de capteurs. $key va prendre les valeurs suivantes : life_sign, alert_bt, confort puis security

        foreach ($this->getCmd() as $cmd) {
          if ($cmd->getLogicalId() == 'sensor_' . $key) {
            if (isset($jsSensor[$cmd->getName()])) { // on regarde si le nom correspond à un nom dans le tableau qu'on vient de recuperer du JS, si oui, on actualise les infos qui pourraient avoir bougé

              $sensor = $jsSensor[$cmd->getName()];
              $cmd->setValue($sensor['cmd']);

              if(isset($sensor['sensor_'.$key.'_type'])){ // ce sera vrai pour les types life-sign, confort et security
                $cmd->setGeneric_type($sensor['sensor_'.$key.'_type']);
              }

                // commenté car jamais utilisé, on va directement chercher dans la conf. A voir si sa sera utile de le garder en DB un jour... TODO
          /*    if($key == 'confort'){ // uniquement pour confort

                $cmd->setConfiguration('seuilBas', $sensor['seuilBas']);
                $cmd->setConfiguration('seuilHaut', $sensor['seuilHaut']);
                switch ($sensor['sensor_confort_type']) {
                    case 'temperature':
                        $unit = '°C';
                        break;
                    case 'humidity':
                        $unit = '%';
                        break;
                    case 'co2':
                        $unit = 'ppm'; //Les sondes de CO2 présentent généralement une plage de mesure de 0-5000 ppm. Il faudra recommander dans la doc une alerte à partir de 1000ppm max
                        break;
                    default:
                        $unit = '-'; //TODO
                        break;
                }
                $cmd->setUnite($unit);

              } //*/

              $cmd->save();

              // va chopper la valeur de la commande puis la suivre a chaque changement
              if (is_nan($cmd->execCmd()) || $cmd->execCmd() == '') {
                $cmd->setCollectDate('');
                $cmd->event($cmd->execute());
              }

              unset($jsSensors[$key][$cmd->getName()]); // on a traité notre ligne, on la vire. Attention ici a ne pas remplacer $jsSensors[$key] par $jsSensor. C'est bien dans le tableau d'origine qu'on veut virer notre ligne

            } else { // on a un sensor qui était dans la DB mais dont le nom n'est plus dans notre JS : on la supprime ! Attention, si on a juste changé le nom, on va le supprimer et le recreer, donc perdre l'historique éventuel. //TODO : voir si ça pose problème (est-il possible d'effectuer un transfert d'id préalable? --> la question est : comment tu sais que c'est le meme puisqu'il n'a plus le meme nom ?) Oui à améliorer, quand tout le reste sera ok ! ;-)
              $cmd->remove();
            }
          }
        } // fin foreach toutes les cmd du plugin
      } // fin foreach nos differents types de capteurs//*/

      //########## 3 - Maintenant on va creer les cmd nouvelles de notre conf (= celles qui restent dans notre tableau) #########//

      foreach ($jsSensors as $key => $jsSensor) { // on boucle dans tous nos types de capteurs. $key va prendre les valeurs suivantes : life_sign, alert_bt, confort puis security

        foreach ($jsSensor as $sensor) { // pour chacun des capteurs de ce type

          // ce qui identifie d'un point de vu unique notre capteur c'est son type et sa value(cmd)

          log::add('seniorcarealertbt', 'debug', 'New Capteurs config : type : ' . $key . ', sensor name : ' . $sensor['name'] . ', sensor cmd : ' . $sensor['cmd']);

          $cmd = new seniorcarealertbtCmd();
          $cmd->setEqLogic_id($this->getId());
          $cmd->setLogicalId('sensor_' . $key);
          $cmd->setName($sensor['name']);
          $cmd->setValue($sensor['cmd']);
          $cmd->setType('info');
          $cmd->setSubType('numeric');
          $cmd->setIsVisible(0);
          $cmd->setIsHistorized(1);
          $cmd->setConfiguration('historizeMode', 'none');

          if(isset($sensor['sensor_'.$key.'_type'])){ // ce sera vrai pour les types life-sign, confort et security
            $cmd->setGeneric_type($sensor['sensor_'.$key.'_type']);
          }

          if($key == 'confort'){ // uniquement pour les commandes de types confort

            // commenté car jamais utilisé, on va directement chercher dans la conf. A voir si sa sera utile de le garder en DB un jour... TODO
            /*
            $cmd->setConfiguration('seuilBas', $sensor['seuilBas']);
            $cmd->setConfiguration('seuilHaut', $sensor['seuilHaut']);
            switch ($sensor['sensor_confort_type']) {
                case 'temperature':
                    $unit = '°C';
                    break;
                case 'humidity':
                    $unit = '%';
                    break;
                case 'co2':
                    $unit = 'ppm'; //Les sondes de CO2 présentent généralement une plage de mesure de 0-5000 ppm. Il faudra recommander dans la doc une alerte à partir de 1000ppm max
                    break;
                default:
                    $unit = '-'; //TODO
                    break;
            }
            $cmd->setUnite($unit); //*/
            $cmd->setConfiguration('historizeMode', 'avg');
            $cmd->setConfiguration('historizeRound', 2);

          }

          $cmd->save();

          // va chopper la valeur de la commande puis la suivre a chaque changement
          if (is_nan($cmd->execCmd()) || $cmd->execCmd() == '') {
            $cmd->setCollectDate('');
            $cmd->event($cmd->execute());
          }

        } //*/ // fin foreach restant. A partir de maintenant on a des capteurs qui refletent notre config lue en JS
      }


      //########## 4 - Mise en place des listeners de capteurs pour réagir aux events #########//

      if ($this->getIsEnable() == 1) { // si notre eq est actif, on va lui definir nos listeners de capteurs

        // un peu de menage dans nos events avant de remettre tout ca en ligne avec la conf actuelle
        $this->cleanAllListener();

        // on boucle dans toutes les cmd existantes
        foreach ($this->getCmd() as $cmd) {

          // on assigne la fonction selon le type de capteur
          if ($cmd->getLogicalId() == 'sensor_life_sign') {
            $listenerFunction = 'sensorLifeSign';
          } else if ($cmd->getLogicalId() == 'sensor_alert_bt'){
            $listenerFunction = 'buttonAlert';
          } else if ($cmd->getLogicalId() == 'sensor_confort'){
            continue; // on veut pas de listener pour les capteurs confort ! Donc on coupe la boucle et on passe au prochain cmd
        //    $listenerFunction = 'sensorConfort';
          } else if ($cmd->getLogicalId() == 'sensor_security'){
            $listenerFunction = 'sensorSecurity';
          } else if ($cmd->getLogicalId() == 'sensor_cancel_alert_bt'){
            $listenerFunction = 'buttonAlertCancel';
          } else if ($cmd->getLogicalId() == 'sensor_cancel_security'){
            $listenerFunction = 'sensorSecurityCancel';
          }

          // on set le listener associée
          $listener = listener::byClassAndFunction('seniorcarealertbt', $listenerFunction, array('seniorcarealertbt_id' => intval($this->getId())));
          if (!is_object($listener)) { // s'il existe pas, on le cree, sinon on le reprend
            $listener = new listener();
            $listener->setClass('seniorcarealertbt');
            $listener->setFunction($listenerFunction); // la fct qui sera appellée a chaque evenement sur une des sources écoutée
            $listener->setOption(array('seniorcarealertbt_id' => intval($this->getId())));
          }
          $listener->addEvent($cmd->getValue()); // on ajoute les event à écouter de chacun des capteurs definis. On cherchera le trigger a l'appel de la fonction si besoin

          log::add('seniorcarealertbt', 'debug', 'sensor listener set - cmd :' . $cmd->getHumanName() . ' - event : ' . $cmd->getValue());

          $listener->save();

        } // fin foreach cmd du plugin
      } // fin if eq actif
      else { // notre eq n'est pas actif ou il a ete desactivé, on supprime les listeners s'ils existaient

        $this->cleanAllListener();

      }

      //########## 5 - initialisation du cache #########//

      // on declare qu'on est pas en phase de warning ni d'alerte
  //    $this->setCache('actionWarningLifeSignLaunched', false);
  //    $this->setCache('actionAlertLifeSignLaunched', false);


    } // fin fct postSave

    // preUpdate ⇒ Méthode appellée avant la mise à jour de votre objet
    // ici on vérifie la présence de nos champs de config obligatoire
    public function preUpdate() {

      $sensorsType = array( // liste des types avec des champs a vérifier
        'life_sign',
        'alert_bt',
        'confort',
        'security',
        'cancel_alert_bt',
        'cancel_security'
      );

      foreach ($sensorsType as $type) {
        if (is_array($this->getConfiguration($type))) {
          foreach ($this->getConfiguration($type) as $sensor) { // pour tous les capteurs de tous les types, on veut un nom et une cmd
            if ($sensor['name'] == '') {
              throw new Exception(__('Le champs Nom pour les capteurs ('.$type.') ne peut être vide',__FILE__));
            }

            if ($sensor['cmd'] == '') { // TODO on pourrait aussi ici vérifier que notre commande existe pour pas avoir de problemes apres...
              throw new Exception(__('Le champs Capteur ('.$type.') ne peut être vide',__FILE__));
            }

            if($type == 'confort'){ // uniquement pour les capteurs conforts, vérif sur les champs seuils

              if ($sensor['seuilHaut'] !='' && !is_numeric($sensor['seuilHaut']) || $sensor['seuilBas'] !='' && !is_numeric($sensor['seuilBas'])) {
                throw new Exception(__('Capteur confort - ' . $sensor['name'] . ', les valeurs des seuils doivent être numériques', __FILE__));
              }

              if ($sensor['seuilBas'] >= $sensor['seuilHaut']) {
                throw new Exception(__('Capteur confort - ' . $sensor['name'] . ', le seuil bas ne peut pas être supérieur ou égal au seuil haut', __FILE__)); // consequence : on peut pas ne definir qu'un seul seuil
              }

            }

          }
        }
      }
    }

    public function postUpdate() {

    }

    public function preRemove() {

      // quand on supprime notre eqLogic, on vire nos listeners associés
      $this->cleanAllListener();

    }

    public function postRemove() {

    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class seniorcarealertbtCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {

      log::add('seniorcarealertbt', 'debug', 'Fct execute pour : ' . $this->getLogicalId() . $this->getHumanName() . '- valeur renvoyée : ' . jeedom::evaluateExpression($this->getValue()));

      return jeedom::evaluateExpression($this->getValue());

    //  $eqLogic = $this->getEqLogic();

   /*   if ($this->getLogicalId() == 'sensor_confort') {
        log::add('seniorcarealertbt', 'debug', 'Fct execute - Capteurs confort, valeur renvoyée : ' . round(jeedom::evaluateExpression($this->getValue()), 1));
        return round(jeedom::evaluateExpression($this->getValue()), 1);
      }

      if ($this->getLogicalId() == 'sensor_alert_bt' || $this->getLogicalId() == 'sensor_life_sign' || $this->getLogicalId() == 'sensor_security') {
        log::add('seniorcarealertbt', 'debug', 'Fct execute - sensor_alert_bt or sensor_life_sign or sensor_security, valeur renvoyée : ' . jeedom::evaluateExpression($this->getValue()));
        return jeedom::evaluateExpression($this->getValue());
      } //*/



    }

    /*     * **********************Getteur Setteur*************************** */
}


