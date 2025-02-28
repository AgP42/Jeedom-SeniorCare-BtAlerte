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

    public static function alertBtActionDelayed($_options) { // fonction appelée par les cron qui servent a reporter l'execution des actions d'alerte. Dans les options on trouve le eqLogic_id et 'action' qui lui meme contient tout ce qu'il faut pour executer l'action reportée, incluant le titre et message pour les messages

      $seniorcarealertbt = seniorcarealertbt::byId($_options['eqLogic_id']);

      if (is_object($seniorcarealertbt)) {

        log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Fct alertBtActionDelayed Appellée par le CRON - eqLogic_id : ' . $_options['eqLogic_id'] . ' - cmd : ' . $_options['action']['cmd'] . ' - action_label : ' . $_options['action']['action_label']);


        $seniorcarealertbt->execAction($_options['action']);

      } else {
        log::add('seniorcarealertbt', 'erreur', $seniorcarealertbt->getHumanName() . ' - Erreur lors de l\'exécution - EqLogic inconnu');
      }

    }

    public static function buttonAlert($_option) { // fct appelée par le listener des buttons d'alerte, n'importe quel bouton arrive ici

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton d'alerte

      if (is_object($seniorcarealertbt)) {

        log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' ################ Bouton d\'alerte déclenché ############');

        foreach ($seniorcarealertbt->getConfiguration('action_alert_bt') as $action) { // pour toutes les actions définies

          log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Config Action bouton d\'alerte - action_label : ' . $action['action_label'] . ' - action_timer : ' . $action['action_timer']);

          if(is_numeric($action['action_timer']) && $action['action_timer'] > 0){ // si on a un timer bien defini et > 0 min, on va lancer un cron pour l'execution retardée de l'action
            // si le CRON existe deja, on ne l'update pas pour ne pas retarder l'alerte en cas de multi appui d'alerte. Et on veut pas non plus setter plusieurs CRON pour la meme action..

            $cron = cron::byClassAndFunction('seniorcarealertbt', 'alertBtActionDelayed', array('eqLogic_id' => intval($seniorcarealertbt->getId()), 'action' => $action)); // cherche le cron qui correspond exactement à "ce plugin, cette fonction et ces options (personne, action (qui contient cmd, option (les titres et messages notamment) et label))" Si on change le label ou le message, c'est plus le meme "action" et donc cette fonction ne le trouve pas et un nouveau cron sera crée !
            // lors d'une sauvegarde ou suppression de l'eqLogic, si des crons sont existants, ils seront supprimés avec un message d'alerte

            if (!is_object($cron)) { // pas de cron trouvé, on le cree

                $cron = new cron();
                $cron->setClass('seniorcarealertbt');
                $cron->setFunction('alertBtActionDelayed');

                $options['eqLogic_id'] = intval($seniorcarealertbt->getId());
                $options['action'] = $action; //inclu tout le detail de l'action : sa cmd, ses options pour les messages, son label, ...
                $cron->setOption($options);

                log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Set CRON : ' . $options['eqLogic_id'] . ' - ' . $options['action']['cmd'] . ' - ' . $options['action']['action_label']);

                $cron->setEnable(1);
                $cron->setTimeout(5); //minutes

                $delai = strtotime(date('Y-m-d H:i:s', strtotime('+'.$action['action_timer'].' min ' . date('Y-m-d H:i:s')))); // on lui dit de se déclencher dans 'action_timer' min
                $cron->setSchedule(cron::convertDateToCron($delai));

                $cron->setOnce(1); //permet qu'il s'auto supprime une fois executé
                $cron->save();

            } else {

              log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' CRON existe deja pour : ' . $cron->getOption()['eqLogic_id'] . ' - ' . $cron->getOption()['action']['cmd'] . ' - ' . $cron->getOption()['action']['action_label'] . ' => on ne fait rien !');
            }

          }else{ // pas de timer valide defini, on execute l'action immédiatement
            // C'est volontaire de ne pas vérifier que l'action a déjà été lancée, ca permet de relancer l'alerte immédiatement si la personne redemande et aussi de lancer quand meme l'action si aucune action "annulation" n'est définie donc que le cache ne se remet jamais a 0...
            // TODO : on pourrait quand meme mettre une tempo pour filtrer 40 appuis sur le bouton... à voir !
            log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Pas de timer liée, on execute ' . $action['cmd']);

            $seniorcarealertbt->execAction($action);
          }

        } // fin foreach toutes les actions

      } else {
        log::add('seniorcarealertbt', 'erreur', $seniorcarealertbt->getHumanName() . ' - Erreur lors de l\'exécution - EqLogic inconnu');
      }

    }

    public static function buttonAlertCancel($_option) { // fct appelée par le listener des buttons d'annulation d'alerte, n'importe lequel des boutons d'annulation arrive ici

      $seniorcarealertbt = seniorcarealertbt::byId($_option['seniorcarealertbt_id']); // on cherche la personne correspondant au bouton

      if (is_object($seniorcarealertbt)) {

        log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' ################ Bouton d\'annulation d\'alerte déclenché ############');

        // boucle d'execution des actions
        foreach ($seniorcarealertbt->getConfiguration('action_cancel_alert_bt') as $action) { // pour toutes les actions d'annulation définies

          $execActionLiee = $seniorcarealertbt->getCache('execAction_'.$action['action_label_liee']); // on va lire le cache d'execution de l'action liée, savoir si deja lancé ou non...

          log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Config Action Annulation bouton d\'alerte, action : '. $action['cmd'] .', label action liée : ' . $action['action_label_liee'] . ' - action liée deja executée : ' . $execActionLiee);

          if($action['action_label_liee'] == ''){ // si pas d'action liée, on execute direct

            log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Pas d\'action liée, on execute ' . $action['cmd']);

            $seniorcarealertbt->execAction($action);

          }else if(isset($action['action_label_liee']) && $action['action_label_liee'] != '' && $execActionLiee == 1){ // si on a une action liée définie et qu'elle a été executée => on execute notre action

            log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Action liée ('.$action['action_label_liee'].') executée précédemment, donc on execute ' . $action['cmd'] . ' et remise à 0 du cache d\'exec de l\'action origine');

            $seniorcarealertbt->execAction($action);

          }else{ // sinon, on log qu'on n'execute pas l'action et la raison
            log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Action liée ('.$action['action_label_liee'].') non executée précédemment, donc on execute pas ' . $action['cmd']);
          }

        } // fin foreach toutes les actions

        // boucle de reset des caches
        foreach ($seniorcarealertbt->getConfiguration('action_cancel_alert_bt') as $action) { // pour toutes les actions d'annulation définies

          $execActionLiee = $seniorcarealertbt->getCache('execAction_'.$action['action_label_liee']); // on va lire le cache d'execution de l'action liée, savoir si deja lancé ou non...

          if(isset($action['action_label_liee']) && $action['action_label_liee'] != '' && $execActionLiee == 1){ // si on a une action liée définie et qu'elle a été executée => on remet le cache de l'action liée à 0 (fait uniquement pour les boutons d'annulation et non à la réception de l'AR, donc l'aidant ayant recu une alerte pourra recevoir l'info qu'il y a eu une AR (mais on sait pas par qui... TODO...) puis que l'alerte est résolue)

            log::add('seniorcarealertbt', 'debug', $seniorcarealertbt->getHumanName() . ' Action liée ('.$action['action_label_liee'].') executée précédemment, remise à 0 du cache d\'exec de l\'action origine');

            $seniorcarealertbt->setCache('execAction_'.$action['action_label_liee'], 0);

          }

        } // fin foreach toutes les actions

        //coupe les CRON des actions d'alertes non encore appelés
        $this->cleanAllCron();

      } else {
        log::add('seniorcarealertbt', 'erreur', $seniorcarealertbt->getHumanName() . ' - Erreur lors de l\'exécution - EqLogic inconnu');
      }

    }

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


    /*     * *********************Méthodes d'instance************************* */

    public function execAction($action) { // execution d'une seule action, avec son label si c'est une alerte
    // $this doit rester l'eqLogic et non la commande elle meme, pour chopper les tags

      log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' ################ Execution de l\' actions ' . $_config . ' pour ' . $this->getName() .  ' ############');

      try {
        $options = array(); // va permettre d'appeler les options de configuration des actions, par exemple un scenario ou les textes pour un message
        if (isset($action['options'])) {
          $options = $action['options'];
          foreach ($options as $key => $value) { // ici on peut définir les "tag" de configuration qui seront à remplacer par des variables
            // str_replace ($search, $replace, $subject) retourne une chaîne ou un tableau, dont toutes les occurrences de search dans subject ont été remplacées par replace.
            $value = str_replace('#senior_name#', $this->getConfiguration('senior_name'), $value);
            $value = str_replace('#senior_phone#', $this->getConfiguration('senior_phone'), $value);
            $value = str_replace('#senior_address#', $this->getConfiguration('senior_address'), $value);

            $value = str_replace('#trusted_person_name#', $this->getConfiguration('trusted_person_name'), $value);
            $value = str_replace('#trusted_person_phone#', $this->getConfiguration('trusted_person_phone'), $value);

      //      $value = str_replace('#url_ar#', $this->getConfiguration('url_ar'), $value); // marche pas malheureusement, probablement une histoire de formatage... TODO...

      //      $value = str_replace('#sensor_name#', $_sensor_name, $value);
      //      $value = str_replace('#sensor_type#', $_sensor_type, $value);
            $options[$key] = str_replace('#sensor_value#', $_sensor_value, $value);
          }
        }
        scenarioExpression::createAndExec('action', $action['cmd'], $options);

        if(isset($action['action_label'])){ // si on avait un label (donc c'est une action d'alerte), on memorise qu'on a lancé l'action
          $this->setCache('execAction_'.$action['action_label'], 1);
          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' setCache TRUE pour label : ' . $action['action_label']);
        }

      } catch (Exception $e) {
        log::add('seniorcarealertbt', 'error', $this->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());

      }

    }


    public function btAlertAR() { // fct appelée par la cmd action appelée par l'extérieur pour AR de l'alerte en cours

      log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' ################ Detection d\'un appel d\'Accusé de Réception ############');

      foreach ($this->getConfiguration('action_ar_alert_bt') as $action) { // pour toutes les actions définies pour les AR

        $execActionLiee = $this->getCache('execAction_'.$action['action_label_liee']);

        log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Config Action Accusé Réception bouton d\'alerte, action : '. $action['cmd'] .', label action liée : ' . $action['action_label_liee'] . ' - action liée deja executée : ' . $execActionLiee);

        if($action['action_label_liee'] == ''){ // si pas d'action liée, on execute direct

          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Pas d\'action liée, on execute ' . $action['cmd']);

          $this->execAction($action);

        }else if(isset($action['action_label_liee']) && $action['action_label_liee'] != '' && $execActionLiee == 1){ // si on a une action liée définie et qu'elle a été executée => on execute notre action

          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Action liée ('.$action['action_label_liee'].') executée précédemment, donc on execute ' . $action['cmd']);

          $this->execAction($action);

        }else{ // sinon, on log qu'on ne l'execute pas
          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Action liée ('.$action['action_label_liee'].') non executée précédemment, donc on execute pas ' . $action['cmd']);
        }

      } // fin foreach toutes les actions

      // décaler les crons des actions d'alertes non encore appelés
      $this->rescheduleAlertCrons();

    }

    public function rescheduleAlertCrons() {

      $comportement = $this->getConfiguration('comportement_actions_alerte_reception_AR');

      log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' - Fct rescheduleAlertCrons, comportement choisi : ' . $comportement);

      $crons = cron::searchClassAndFunction('seniorcarealertbt','alertBtActionDelayed'); // on prend tous nos crons de ce plugin, cette fonction, pour tous les equipements
      if (is_array($crons) && count($crons) > 0) {
        foreach ($crons as $cron) {
          if (is_object($cron) && $cron->getOption()['eqLogic_id'] == $this->getId() && $cron->getState() != 'run') { // si l'id correspond et qu'il est pas en cours, on le decale ou le supprime

            if($comportement == 'delay'){

              $delai_ar = $this->getConfiguration('delai_ar');

              $delai = strtotime(date('Y-m-d H:i:s', strtotime('+ '.$delai_ar.' min ' . $cron->getNextRunDate())));
              $cron->setSchedule(cron::convertDateToCron($delai));

              $cron->save();

              log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' - Cron trouvé à décaler - cmd : ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label'] . ' - maintenant prévu à : ' . $cron->getNextRunDate() . ' (delai ajouté : ' . $delai_ar . ' min)');

            } else if ($comportement == 'remove'){

              log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' - Cron trouvé à supprimer - cmd : ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label'] . ' - prévu initialement à : ' . $cron->getNextRunDate());

              $cron->remove();
            }

          }
        }
      }

    }

    public function cleanAllCron($displayWarningMessage = false) {

      log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Fct cleanAllCron');

      $crons = cron::searchClassAndFunction('seniorcarealertbt','alertBtActionDelayed'); // on prend tous nos crons de ce plugin, cette fonction, pour tous les equipements
      if (is_array($crons) && count($crons) > 0) {
        foreach ($crons as $cron) {
          if (is_object($cron) && $cron->getOption()['eqLogic_id'] == $this->getId() && $cron->getState() != 'run') { // si l'id correspond et qu'il est pas en cours, on le vire
            log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' - Cron trouvé à supprimer - cmd : ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label']);

            if($displayWarningMessage){

              log::add('seniorcarealertbt', 'error', $this->getHumanName() . 'Attention, des actions d\'alerte avec un délai avant exécution sont en cours et vont être supprimées, merci de vous assurer que la personne n\'a pas besoin d\'assistance ! Il s\'agit de ' . $this->getConfiguration('senior_name') . ', action supprimée : ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label']);
            }

            $cron->remove();
          }
        }
      }

/*      log::add('seniorcarealertbt', 'debug', 'Fct cleanAllCron pour : ' . $this->getName());

      $cron = cron::byClassAndFunction('seniorcarealertbt', 'alertBtActionDelayed'); //on cherche le 1er cron pour ce plugin et cette action (il n'existe pas de fonction core renvoyant un array avec tous les cron de la class, comme pour les listeners... dommage...)

      while (is_object($cron) && $cron->getOption()['eqLogic_id'] == $this->getId()) { // s'il existe et que l'id correspond, on le vire puis on cherche le suivant et tant qu'il y a un suivant on boucle

        log::add('seniorcarealertbt', 'debug', 'Cron trouvé à supprimer pour eqLogic_id : ' . $cron->getOption()['eqLogic_id'] . ' - cmd : ' . ' - ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label']);

        if($displayWarningMessage){

          log::add('seniorcarealertbt', 'error', 'Attention, des actions d\'alerte avec un délai avant exécution sont en cours et vont être supprimées, merci de vous assurer que la personne associée n\'a pas besoin d\'assistance ! Il s\'agit de ' . $this->getConfiguration('senior_name') . ' - pour l\'eqLogic ' . $this->getName() . ', action supprimée : ' . $cron->getOption()['action']['cmd'] . ' - action_label : ' . $cron->getOption()['action']['action_label']);
        }

        $cron->remove();
        $cron = cron::byClassAndFunction('seniorcarealertbt', 'alertBtActionDelayed');
      }*/


    }

    public function cleanAllListener() {

      log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Fct cleanAllListener pour : ' . $this->getName());

      $listeners = listener::byClass('seniorcarealertbt'); // on prend tous nos listeners de ce plugin, pour toutes les personnes
      foreach ($listeners as $listener) {
        $seniorcarealertbt_id_listener = $listener->getOption()['seniorcarealertbt_id'];

    //    log::add('seniorcarealertbt', 'debug', 'cleanAllListener id lue : ' . $seniorcarealertbt_id_listener . ' et nous on est l id : ' . $this->getId());

        if($seniorcarealertbt_id_listener == $this->getId()){ // si on correspond a la bonne personne, on le vire
          $listener->remove();
        }

      }

    }

    public function preInsert() {

    }

    // Méthode appellée après la création de votre objet --> on va créer la commande pour l'AR
    public function postInsert() {
      $cmd = $this->getCmd(null, 'alerte_bt_ar');
      if (!is_object($cmd)) {
        $cmd = new seniorcarealertbtCmd();
        $cmd->setName(__('Accuser Réception Alerte', __FILE__));
      }
      $cmd->setLogicalId('alerte_bt_ar');
      $cmd->setEqLogic_id($this->getId());
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setIsVisible(0);
      $cmd->setIsHistorized(1);
      $cmd->setConfiguration('historizeMode', 'none'); //on garde en mémoire tous les AR recu.
      //TODO : voir comment on pourrait avoir l'info de "qui" a accusé réception...
      $cmd->save();
    }

    public function preSave() {

      //supprime les CRON des actions d'alertes non encore appelés, affiche une alerte s'il y en avait
      //sert à ne pas laisser trainer des CRONs en cours si on change le message ou le label puis en enregistre. Cas exceptionnel, mais au cas où...

  //    $calculstarttime = date('H:i:s');

      $this->cleanAllCron(true);

  //    log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' cleanAllCron start à ' . $calculstarttime . ' fin à : ' . date('H:i:s'));

    }

    // fct appellée par Jeedom aprés l'enregistrement de la configuration
    public function postSave() {

      //########## 1 - On va lire la configuration des capteurs dans le JS et on la stocke dans un grand tableau #########//

      $jsSensors = array(
        'alert_bt' => array(), // sous-tableau pour stocker toutes les infos des bouton d'alertes
        'cancel_alert_bt' => array(), // boutons d'annulation alerte immédiate
      );

      foreach ($jsSensors as $key => $jsSensor) { // on boucle dans tous nos types de capteurs pour recuperer les infos
        log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Boucle de $jsSensors : key : ' . $key);

        if (is_array($this->getConfiguration($key))) {
          foreach ($this->getConfiguration($key) as $sensor) {
            if ($sensor['name'] != '' && $sensor['cmd'] != '') { // si le nom et la cmd sont remplis

              $jsSensors[$key][$sensor['name']] = $sensor; // on stocke toute la conf, c'est à dire tout ce qui dans notre js avait la class "expressionAttr". Pour retrouver notre champs exact : $jsSensors[$key][$sensor['name']][data-l1key]. // attention ici a ne pas remplacer $jsSensors[$key] par $jsSensor. C'est bien dans le tableau d'origine qu'on veut écrire, pas dans la variable qui le represente dans cette boucle
              log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Capteurs sensor config lue : ' . $sensor['name'] . ' - ' . $sensor['cmd']);

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
			  $value = $cmd->execCmd();
		  	  if (!is_numeric($value) || is_nan((float)$value) || $value == '') {
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

          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' New Capteurs config : type : ' . $key . ', sensor name : ' . $sensor['name'] . ', sensor cmd : ' . $sensor['cmd']);

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
		  $value = $cmd->execCmd();
          if (!is_numeric($value) || is_nan((float)$value) || $value == '') {
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
          } else {
            continue; // sinon c'est que c'est pas un truc auquel on veut assigner un listener, on passe notre tour
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

          log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' sensor listener set - cmd :' . $cmd->getHumanName() . ' - event : ' . $cmd->getValue());

          $listener->save();

        } // fin foreach cmd du plugin
      } // fin if eq actif
      else { // notre eq n'est pas actif ou il a ete desactivé, on supprime les listeners s'ils existaient

        $this->cleanAllListener();

      }

      //########## 5 - Divers #########//

      // on pouvait pas le faire à la creation de la cmd donc on le fait dans le postSave : on prend l'url et on l'enregistre en configuration
      /* //sauf que le tag marche pas, donc sert à rien, je vire pour l'instant, à voir TODO
      $cmd = $this->getCmd(null, 'alerte_bt_ar');
      if (is_object($cmd)) {
        $cmd->setConfiguration('url_ar', $cmd->getDirectUrlAccess());
        $cmd->save();
      } //*/


    } // fin fct postSave

    // preUpdate ⇒ Méthode appellée avant la mise à jour de votre objet
    // ici on vérifie la présence de nos champs de config obligatoire
    public function preUpdate() {

      $sensorsType = array( // liste des types avec des champs a vérifier
        'alert_bt',
        'cancel_alert_bt',
      );

      $allNames = array(); // on va stocker tous les noms pour verifier ensuite leur unicité

      foreach ($sensorsType as $type) {
        if (is_array($this->getConfiguration($type))) {
          foreach ($this->getConfiguration($type) as $sensor) { // pour tous les capteurs de tous les types, on veut un nom et une cmd
            if ($sensor['name'] == '') {
              throw new Exception(__('Le champs Nom pour les capteurs ('.$type.') ne peut être vide',__FILE__));
            }

            array_push($allNames, trim($sensor['name']));

            if ($sensor['cmd'] == '') { // TODO on pourrait aussi ici vérifier que notre commande existe pour pas avoir de problemes apres...
              throw new Exception(__('Le champs Capteur ('.$type.') ne peut être vide',__FILE__));
            }

            if (substr_count($sensor['cmd'], '#') < 2) {
              throw new Exception(__('Attention : '.$sensor['cmd'].' pour : ' . $sensor['name'] . ' n\'est pas être une commande jeedom valide',__FILE__));
            }

          }
        }
      }

      //tester l'unicité de tous les noms
      if(count($allNames) !== count(array_unique($allNames))){
        throw new Exception(__('Les noms des capteurs d\'activité doivent être uniques',__FILE__));
      }
    }

    public function postUpdate() {

    }

    public function preRemove() { //quand on supprime notre eqLogic

      // on vire nos listeners associés
      $this->cleanAllListener();

      //supprime les CRON des actions d'alertes non encore appelés, affiche une alerte s'il y en avait
      //sert à ne pas laisser trainer des CRONs en cours si on change le message ou le label puis en enregistre. Mais ne devrait arriver qu'exceptionnellement
      $this->cleanAllCron(true);

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

        log::add('seniorcarealertbt', 'debug', $this->getHumanName() . ' Fct execute - valeur renvoyée : ' . jeedom::evaluateExpression($this->getValue()));

        return jeedom::evaluateExpression($this->getValue());
      }

    }

    /*     * **********************Getteur Setteur*************************** */
}
