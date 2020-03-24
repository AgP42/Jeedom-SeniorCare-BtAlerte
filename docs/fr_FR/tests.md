Infos/constatations :
* les valeurs en cache ne sont pas effacées lors de la sauvegarde (mais effacées au reboot), donc on garde les timestamps du dernier trigger d'activité et l'état des warning et alertes
* si on ne remplit pas la conf pour les champs de durée, le code lit 0. Dans ce cas le warning est appelé au prochain CRON et le alerte au CRON suivant (ils ne passent pas tous les 2 dans le meme CRON)

Tests sur v0.0.2 du 19 au 21 mars 2020
###

Tests sur bouton d'alerte (apres avoir ajouté les capteurs et actions de desactivation)
---

0. Creation de la conf et vérification en DB que les capteurs et les listeners sont bien présents => OK
1. Trigger bouton alerte => action alerte ok
2. Trigger bouton desactivation alerte => action desactivation alerte ok
3. Trigger bouton alerte plusieurs fois => action alerte relancée a chaque fois
4. idem bouton desactivation => idem
=> Test OK

