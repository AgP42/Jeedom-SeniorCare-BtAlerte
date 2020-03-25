Présentation
============

Ce plugin fait parti d'un ensemble de plugins pour Jeedom permettant l'aide au maintien à domicile des personnes âgées : SeniorCare.

La demande initiale vient de ce sujet sur le forum : [Développer un logiciel d’Analyse comportementale](https://community.jeedom.com/t/developper-un-logiciel-danalyse-comportementale/19111).

Ce plugin permet :
* Gestion de boutons d’alertes

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

Beta 0.0.1 - 24 mars 2020
---

* Gestion des boutons d'alerte immédiate
* Création documentation


Configuration du plugin
========================

Ajouter les différentes personnes à suivre, puis pour chacune configurer les différents onglets.

Onglet Général
---
* Indiquer le nom de la personne
* "Objet parent" : il s'agit de l'objet Jeedom auquel rattacher la personne. Il doit être différent de "Aucun"
* Activer le plugin pour cette personne
* Visible sert a visualiser les infos sur le dashboard, il n'y a rien a visualiser pour ce plugin

Onglet **Bouton d'alerte**
---
Cet onglet permet de regrouper différents boutons d'alertes immédiates que la personne pourra activer pour demander de l'aide. Il peut s'agir d'un bouton à porter sur soi ou de boutons dans une zone particulière.

* Définir un ou plusieurs capteurs de type "bouton" ou "interrupteur"
* Définir les actions qui seront immédiatement réalisées à l'activation de n'importe lequel de ces capteurs
* Définir un ou plusieurs capteurs de type "bouton" ou "interrupteur" qui serviront à annuler l'alerte
* Définir les actions qui seront réalisées à l'activation des capteurs d'annulation

Si l'une de vos action est de type "message", vous pouvez utiliser le tag #senior_name# qui enverra le nom configuré dans l'onglet "Général".

![](https://raw.githubusercontent.com/AgP42/seniorcarealertbt/master/docs/assets/images/Boutons_alerte.png)


Onglet **Avancé - Commandes Jeedom**
---


Comportement au démarrage et après redémarrage Jeedom
======

Fonction **Bouton d'alerte**
---
RAS


Remarques générales
===
* Pour les capteurs "bouton d'alerte" et "bouton d'annulation d'alerte", c'est le changement de valeur du capteur qui est détecté et déclenche les actions, la valeur en elle-même n'est pas prise en compte !
* L'ensemble des capteurs définis dans le plugin doivent posséder un nom unique. Le changement de nom d'un capteur revient à le supprimer et à en créer un nouveau. De fait, la totalité de l'historique associé à ce capteur sera donc perdue.
