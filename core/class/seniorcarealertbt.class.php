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
    public static function buttonAlertAR($_option) { // fct appelée par le listener de la commande API pour l'accusé de reception de l'alerte
      log::add('seniorcarealertbt', 'debug', '################ Reception d\'un AR pour l\'alerte ############');

 //    $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte
  //    $seniorcarealertbt->execActions('action_alert_bt'); // on appelle les actions definies pour cette personne pour les boutons d'alertes

    }

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


/*    public static function cron() { //executée toutes les min par Jeedom

      log::add('seniorcarealertbt', 'debug', '#################### CRON ###################');

      //pour chaque equipement (personne) declaré par l'utilisateur
      foreach (self::byType('seniorcarealertbt',true) as $seniorcarealertbt) {


      } // fin foreach equipement

    } //fin cron //*/

    //*
    // * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
    // Sert ici pour les capteurs conforts
/*      public static function cron15() {

        log::add('seniorcarealertbt', 'debug', '#################### CRON 15 ###################');

        //pour chaque equipement (personne) declaré par l'utilisateur
        foreach (self::byType('seniorcarealertbt',true) as $seniorcarealertbt) {

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

    }

    public function btAlertAR() {
      log::add('seniorcarealertbt', 'debug', 'ICI LES ACTIONS QUAND ON A APPELE L\'AR');
      $this->execActions('action_ar_alert_bt'); // on appelle les actions definies pour cette personne pour les boutons d'alertes
    }

    public function preInsert() {

    }

    // Méthode appellée après la création de votre objet --> on va créer la commande pour l'AR
    public function postInsert() {
      $cmd = $this->getCmd(null, 'alerte_bt_ar');
      if (!is_object($cmd)) {
        $cmd = new seniorcarealertbtCmd();
        $cmd->setName(__('AR Bouton Alerte', __FILE__));
      }
      $cmd->setLogicalId('alerte_bt_ar');
      $cmd->setEqLogic_id($this->getId());
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setIsVisible(0);
      $cmd->setIsHistorized(1);
      $cmd->setConfiguration('historizeMode', 'none');
      $cmd->save();
    }

    public function preSave() {

    }

    // fct appellée par Jeedom aprés l'enregistrement de la configuration
    public function postSave() {


      //########## 1 - On va lire la configuration des capteurs dans le JS et on la stocke dans un grand tableau #########//

      $jsSensors = array(
        'alert_bt' => array(), // sous-tableau pour stocker toutes les infos des bouton d'alertes
        'cancel_alert_bt' => array(), // boutons d'annulation alerte immédiate
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
          if ($cmd->getLogicalId() == 'sensor_alert_bt'){
            $listenerFunction = 'buttonAlert';
          } else if ($cmd->getLogicalId() == 'sensor_cancel_alert_bt'){
            $listenerFunction = 'buttonAlertCancel';
          } else if ($cmd->getLogicalId() == 'alerte_bt_ar'){
            $listenerFunction = 'buttonAlertAR';
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

    } // fin fct postSave

    // preUpdate ⇒ Méthode appellée avant la mise à jour de votre objet
    // ici on vérifie la présence de nos champs de config obligatoire
    public function preUpdate() {

      $sensorsType = array( // liste des types avec des champs a vérifier
        'alert_bt',
        'cancel_alert_bt',
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


      if ($this->getLogicalId() == 'alerte_bt_ar') {
       // log::add('seniorcarealertbt', 'debug', 'Appel de l AR via API');
        $eqLogic = $this->getEqLogic();
        $eqLogic->btAlertAR();
      } else { // sinon c'est un sensor et on veut juste sa valeur

        log::add('seniorcarealertbt', 'debug', 'Fct execute pour : ' . $this->getLogicalId() . $this->getHumanName() . '- valeur renvoyée : ' . jeedom::evaluateExpression($this->getValue()));

        return jeedom::evaluateExpression($this->getValue());
      }

    }

    /*     * **********************Getteur Setteur*************************** */
}


