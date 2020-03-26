Tests sur v0.0.2 globale - du 19 au 21 mars 2020
###

Tests sur bouton d'alerte (apres avoir ajouté les capteurs et actions de desactivation)
---

0. Creation de la conf et vérification en DB que les capteurs et les listeners sont bien présents => OK
1. Trigger bouton alerte => action alerte ok
2. Trigger bouton desactivation alerte => action desactivation alerte ok
3. Trigger bouton alerte plusieurs fois => action alerte relancée a chaque fois
4. idem bouton desactivation => idem
=> Test OK

Tests sur plugin bouton d'alerte beta 0.0.2 - 24 mars 2020
###

Tests d'interface : changement des labels, cmd, timers, saisies de données incohérantes, ...
---

* Erreur "Call to a member function getCmd() on boolean" => il y avait dans l'url un "&id=seniorcarealertbt". A essayer de reproduire, quand est-ce qu'on tombe la dessus ??
* Le label peut avoir des espaces, ca marche quand meme
* Changement d'un label => pas de problème de save
* Attention si la différence entre 2 labels est un simple espace : c'est différent et donc non lié !

Tests fonctionnels
---

* Appel AR avant l'action (liées par un label jamais utilisé encore) => il ne se passe rien => ok
* Appel action puis AR => les 2 se réalisent correctement => ok
* Changement du label lié => marche plus, ok

Tests dégradés
---

* Suppression de l'équipement sans cron actifs => ok
* Suppression de l'équipement avec cron actifs => message de warning => OK
* Lancement alerte avec des crons actifs, puis reboot Jeedom => les crons sont toujours présent au redemarrage et le cron qui n'a pas pu s'executer à l'heure à cause de jeedom off est executé au demarrage => OK


