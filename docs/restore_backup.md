# Restore database backup

Sorry it's in French...

Here is the process to follow when a accident or a human mistake happened in openchurch server.

### Scénario A/ On s'en rend compte dans la journée
- Se connecter sur le server openchurch (`ssh hozana@open-church.io`) et chercher l'archive `openchurch.dump.sql.gz` dans `~/mysqldump/`
- Décompresser le .gz : `gunzip openchurch.dump.sql.gz`
- Ensuite charger le dump dans la db `openchurch` avec le user `openchurch` :
```
source ~/openchurch/.env
mysql -uopenchurch -p$MYSQL_PASSWORD openchurch < openchurch.dump.sql
```

### Scénario B/ Il s'est passé entre un ou cinq jours entre l'erreur sur le server openchurch et la prise de conscience
Se connecter au server ftp de backup, avec Filezilla par exemple.
Rappel des identifiants:
```
Host: dedibackup-dc3.online.net
User: sd-86030
```
Le mot de passe est à demander à un membre de l'équipe permanente d'Hozana.

- Chercher l'archive dans `/open_church_backup` sous la forme suivante: `open-church-home.20190105.master.tar.gz`
- Copier cette archive en local, puis sur le server openchurch (par exemple avec scp).
- Décompresser l'archive `tar xvzf open-church-home.20190105.master.tar.gz`
- Chercher l'archive `openchurch.dump.sql.gz` dans le sous-dossier mysqldump du dossier décompressé.
- Ensuite la procédure est la même que dans le scénario A.