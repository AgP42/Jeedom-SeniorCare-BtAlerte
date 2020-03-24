Présentation
============

Ce plugin pour Jeedom permet l'aide au maintien à domicile des personnes âgées.
La demande initiale vient de ce sujet sur le forum : [Développer un logiciel d’Analyse comportementale](https://community.jeedom.com/t/developper-un-logiciel-danalyse-comportementale/19111/2).
A ce jour, les principales fonctionnalités sont :
* Détection d’inactivité
* Gestion de boutons d’alertes
* Surveillance du confort (température, humidité, CO2, …)
* Surveillance sécurité (détecteurs de fumée, de fuite de gaz, …)
* Surveillance de « Dérive comportementale » afin de détecter et prendre en charge au plus tôt et donc au mieux, les difficultés inhérentes à l'âge

Lien vers le code source : [https://github.com/AgP42/seniorcarealertbt/](https://github.com/AgP42/seniorcarealertbt/)

Si vous souhaitez participer au projet, n’hésitez pas à le faire savoir ici [Développer un logiciel d’Analyse comportementale](https://community.jeedom.com/t/developper-un-logiciel-danalyse-comportementale/19111/2)

Avertissement
==========

Ce plugin a été conçu pour apporter une aide aux personnes souhaitant rester chez elles et à leurs aidants.
Nous ne pouvons toutefois pas garantir son bon fonctionnement ni qu'un dysfonctionnement de l’équipement domotique n'arrive au mauvais moment.
Merci de l'utiliser en tant que tel et de ne pas prendre de risque pour la santé de ceux que nous cherchons à aider !

Changelog
==========

Ce plugin est en cours de développement, toutes les fonctions ne sont pas encore codées, certaines n'ont été que partiellement testées.

Beta 0.0.1 - 18 mars 2020 :
---
* Fonction "Détection d’inactivités"
* Fonction "Bouton d'alerte"
* Fonction "Capteurs confort"

Beta 0.0.2 - 21 mars 2020
---
* refactorisation du code, relecture et debug
* Ajout Gestion des capteurs Sécurité
* session de test n°1 et debug associé - voir docs/fr_FR/tests.md
* Ajout des actions de désactivation des alertes "bouton d'alerte", "capteur sécurité" et "capteur confort"
* Ajout de la gestion de la non-répétition des alertes sur les capteurs confort
* Capteurs conforts évalués par cron15 et non plus par listener
* Mise à jour de la doc

0.0.3 & 0.0.4 - 22 mars 2020
---

* debug erreur js sur la liste des actions lorsque tous les capteurs sont dans les seuils
* debug 2 autres erreurs js de ce type
* creation puis correction de l'erreur de suppression du "bouton d'alerte"
* changement du tag #value# en #sensor_value# pour éviter les interferences avec le tag #value# du core
* ajout des tag #sensor_name# et #sensor_type# pour les actions d'alerte de securité
* debug sur les capteurs conforts !
* Mise à jour de la doc


Configuration du plugin
========================

Ajouter les différentes personnes à suivre, puis pour chacune configurer les différents onglets.

Onglet Général
---
* Indiquer le nom de la personne
* "Objet parent" : il s'agit de l'objet Jeedom auquel rattacher la personne. Il doit etre différent de "Aucun"
* Activer la personne
* Visible sert a visualiser les infos sur le dashboard, pour l'instant il n'y a rien a visualiser

Onglet **Gestion absences**
---
To Do
L’objectif ici étant de lier ce plugin au plugin « Agenda » pour les jours d’absence réguliers et les vacances.
Il permet également d’ajouter les capteurs adéquats pour savoir si la personne est présente ou non pedant les absences ponctuelles.
En cas d'absence, il convient de désactiver entre autres la fonction de détection d’inactivité.

Onglet **Détection d'inactivité**
---
Il s'agit ici de déclencher une alerte en cas d'absence d’activité de la personne, cas d'un malaise ou d'une chute par exemple.

**A améliorer : le délai de détection d'inactivité selon jour ou nuit**

Trois étapes de fonctionnement :

1. Définir les capteurs de détection d’activité (ouverture porte, fenêtre, interrupteur, mouvement, …) et un délai associé. Si aucun des capteurs d'activité n'a été activé à l’échéance du délai, le plugin déclenchera l’étape suivante « Avertissement ».
2. « Avertissement » :
  * L'objectif de cette étape est de prévenir la personne que le système a détecté une inactivité et lui permettre de réagir avant que l’alerte ne soit envoyée aux aidants extérieurs
  * Configuration à réaliser :
    - définir la liste des actions à lancer pour l’avertissement
    - définir la durée maximum pendant laquelle la personne pourra réagir avant de déclencher l'alerte
    - définir les actions permettant d'annuler l’avertissement si la personne réagit (la réaction de la personne est détectée par n’importe quel capteur définis à l’étape 1)
  * Si la personne ne réagit pas dans le délai imparti, le plugin passera a l'étape "Alerte". Dans ce cas, les actions pour "annuler l'avertissement" ne seront pas réalisées.
3. « Alerte » :
  * Définir la liste des actions à lancer afin d’alerter les aidants et informer la personne dépendante qu’une alerte est en cours
  * Définir la liste d’actions permettant de désactiver ces actions d’alerte. L'alerte est déactivée par n’importe quel capteur de l’étape 1. La personne dans son logement ou un aidant une fois sur place pourront donc le faire

![](https://raw.githubusercontent.com/AgP42/seniorcarealertbt/dev/docs/assets/images/Detection_inactivite.png)

Onglet **Bouton d'alerte**
---
Cet onglet permet de regrouper différents boutons d'alertes immédiates que la personne pourra activer pour demander de l'aide. Il peut s'agir d'un bouton à porter sur soi ou de boutons dans une zone particulière.

* Définir un ou plusieurs capteurs de type "bouton" ou "interrupteur"
* Définir les actions qui seront immédiatement réalisées à l'activation de n'importe lequel de ces capteurs
* Définir un ou plusieurs capteurs de type "bouton" ou "interrupteur" qui serviront à annuler l'alerte
* Définir les actions qui seront réalisées à l'activation des capteurs d'annulation

Si l'une de vos action est de type "message", vous pouvez utiliser le tag #senior_name# qui enverra le nom configuré dans l'onglet "Général".

![](https://raw.githubusercontent.com/AgP42/seniorcarealertbt/dev/docs/assets/images/Boutons_alerte.png)

Onglet **Confort**
---
Cet onglet permet de regrouper les informations de confort du logement.
Il peut s'agir de la température, ou du taux d'humidité pour certaines pièces et du niveau de CO2.
A partir de 1000 ppm (CO2), il est recommandé d'aérer le logement.
Vous pouvez aussi suivre la température extérieure.

* Définir les différents capteurs de confort du logement à suivre. Il peut s'agit de capteurs de température, d'humidité, de CO2 ou de tout autre type.
  * Vous devez donner un nom unique à chacun de vos capteurs. Attention : le changement de nom d'un capteur revient à le supprimer et a en recréer un nouveau, vous perdez donc l'historique associé
  * Sélectionner la commande Jeedom du capteur associé. Attention, chaque capteur ne doit être utilisé qu'une seule fois. Si besoin d'utiliser 2 fois la même source, merci de le dupliquer avec un virtuel.
  * Définir son type
  * Définir les seuils haut et bas
* Définir les actions exécutées pour chaque capteur lors du dépassement de seuil et la gestion voulue pour les répétitions (tant que le capteur est hors seuils)
* Définir (ou non) les actions qui seront exécutées pour chaque capteur lors du retour dans les seuils aprés un dépassement (exécutées à chaque "retour", pour chaque capteur)
* Définir (ou non) les actions à exécuter lorsque tous les capteurs ont leurs valeurs dans les seuils définis

Détails de fonctionnement :
* Toutes les 15 min, Jeedom évaluera pour chacun des capteurs si sa valeur est dans les seuils définis ou non
* Les actions "Actions avertissement (pour chaque capteur hors seuils, je dois ?)" seront alors exécutées pour chaque capteur hors seuils sauf si l'avertissement a déjà été donné pour ce capteur et que l’utilisateur a choisi de ne pas le répéter
* Lorsqu'un capteur précédemment hors seuil revient dans ses bornes, les actions "Actions arrêt l'avertissement - pour chaque capteur de retour dans les seuils, je dois ?" seront alors exécutées pour ce capteur
* Si tous les capteurs sont évalués "dans les seuils", les actions "Actions arrêt l'avertissement - lorsque tous les capteurs sont dans les seuils, je dois ?" seront alors exécutées


Si l'une de vos action est de type "message", vous pouvez utiliser les tags suivants :
  * #senior_name# : nom configuré dans l'onglet "Général"
  * #sensor_name# : nom du capteur ayant déclenché l'avertissement
  * #sensor_type# : type de ce capteur - attention, le type sera donné en anglais
  * #sensor_value# : valeur courante
  * #low_threshold# : seuil bas défini
  * #high_threshold# : seuil haut défini
  * #unit# : unité correspondant à la valeur

![](https://raw.githubusercontent.com/AgP42/seniorcarealertbt/dev/docs/assets/images/Confort.png)

Onglet **Sécurité**
---
Cet onglet permet de regrouper les capteurs d'urgennce du logement de la personne dépendante (détecteur de fumée, alerte fuite de gaz, …) et aussi les actions d'alerte immédiate vers l’extérieur au cas où la personne ne peut déjà plus agir.

* Définir un ou plusieurs capteurs de sécurité. L'alerte sera déclenchée à chaque changement d'état du capteur, peu importe le sens du changement d'état
* Définir les actions immédiatement réalisées à l'activation de n'importe lequel de ces capteurs
* Définir un ou plusieurs capteurs de type "bouton" ou "interrupteur" servant à annuler l'alerte
* Définir les actions réalisées à l'activation des capteurs d'annulation

Si l'une de vos action est de type "message", vous pouvez utiliser les tags suivants :
  * #senior_name# : nom configuré dans l'onglet "Général"
  * #sensor_name# : nom du capteur ayant déclenché l'alerte (uniquement pour l'alerte et non pour l'annulation d'alerte)
  * #sensor_type# : type de ce capteur - attention, le type sera donné en anglais

![](https://raw.githubusercontent.com/AgP42/seniorcarealertbt/dev/docs/assets/images/Onglet_securité.png)

Onglet **Dérive comportementale**
---
To Do

Prévu à ce jour : faire une liste de cases à cocher selon les risques que l’on souhaite suivre pour cette personne, avec les risques suivants (liste à compléter, toute suggestion sera bienvenue !) :

* Rythme de vie : suivre les horaires de lever/coucher/sieste
* Isolement : suivre le nombre de sorties par jour/semaines (éventuellement liées a la météo)
* Infection urinaire : ici on pourra surveiller 2 capteurs (au choix ou les 2 ensemble) : Nombre de lever la nuit et Nombre de chasse d’eau jour et nuit.
* Alimentation : suivre le Nombre d’ouverture du frigo / jour et les tranches horaires pour voir si décalage dans la prise des repas avec alerte si le frigo n’a pas été ouvert bien que la personne soit présente


Onglet **Avancé - Commandes Jeedom**
---
Panneau desktop
================
To Do - Il permettra de suivre les différents capteurs et visualiser les alertes

Comportement au démarrage et après redémarrage Jeedom
======

Fonction de **Détection d'inactivité**
---
* Après création et première sauvegarde, le déclenchement de l'un des capteur d'activité initialise le mécanisme. En cas de non-déclenchement de l'un de ces capteurs d'activité dans cette minute, les actions d' "avertissement" vont se lancer.
* Après un redémarrage de Jeedom, le système aura perdu l'information de la date du dernier capteur d'activité et si des alarmes ont déjà été envoyées. Les actions warnings vont donc se déclencher. Aussi, en cas de redémarrage de Jeedom, vous devrez penser à activer un des capteurs d'activité dans la première minute.


Fonction **Bouton d'alerte**
---
RAS

Fonction **Confort**
---
RAS

Fonction **Sécurité**
---
RAS

Remarques générales
===
* Pour les capteurs "détections d'inactivité", "bouton d'alerte", "bouton d'annulation d'alerte", "capteur de sécurité" et "bouton d'annulation d'alerte de sécurité", c'est le changement de valeur du capteur qui est détecté et déclenche les actions, la valeur en elle-même n'est pas prise en compte !
* Pour les capteurs conforts, leur valeur est évaluée toutes les 15 min et non à chaque changement
* L'ensemble des capteurs définis dans le plugin doivent posséder un nom unique. Le changement de nom d'un capteur revient à le supprimer et à en créer un nouveau. De fait, la totalité de l'historique associé à ce capteur sera donc perdu.

Exemples d'usage et configuration associée
========================

Bonus : configuration pour recevoir les alertes par notification sur un smartphone android
========================

Bonus 2 : configuration pour mettre un jour un widget sur un smartphone android selon les infos de ce plugin
========================
