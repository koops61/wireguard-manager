# 🔐 WireGuard Manager

**WireGuard Manager** est une interface web simple et efficace pour gérer vos connexions VPN WireGuard depuis un serveur (ex. Raspberry Pi). Il permet d'ajouter, supprimer, afficher et gérer facilement les clients via une base de données et une interface PHP/MySQL.

![Capture d'écran 2025-03-13 234020](https://github.com/user-attachments/assets/05ab5515-377c-4ff6-91f6-58001fb28cf7)


---

## 📂 Fonctionnalités

✅ Ajout de clients WireGuard avec génération automatique de clés  
✅ Attribution automatique d'une IP dédiée par client  
✅ Génération automatique du fichier de configuration client  
✅ Suppression de clients via l’interface web  
✅ Interface Web responsive et simple à utiliser  
✅ Intégration MySQL pour le suivi des clients  
✅ Génération de QR Codes (à venir)  

---

## 💡 Prérequis

- Apache2 / Nginx
- PHP >= 7.4
- MySQL / MariaDB
- WireGuard installé et configuré sur le serveur
- `php-mysql`, `php-curl`, `php-qrcode` (à venir)
- Un nom de domaine ou un accès à distance (ex. `hostname.ddns.net`)

---

## 📦 Installation
🔧 ÉTAPE 1 : Installation de WireGuard
1. Connecte-toi à ton Raspberry Pi via SSH (ou directement sur l’interface)
```
sudo apt update && sudo apt upgrade -y
sudo apt install wireguard -y
```

2. Vérifie que WireGuard est bien installé :
```
wg –version
```

Si tu vois une version s’afficher, c’est bon ✅
🔧 ajuster les permissions
1.	Vérifie les permissions actuelles
Exécute cette commande pour voir qui peut accéder à /etc/wireguard/ :
```
sudo ls -ld /etc/wireguard/
```
2.	Ajoute ton utilisateur au groupe wireguard (optionnel)
Si WireGuard a un groupe spécifique (parfois wireguard ou root), tu peux ajouter ton utilisateur dedans :
```
sudo usermod -aG wireguard $(whoami)
```

Puis recharge ta session avec :
```
su - $(whoami)
```

________________________________________
🔑 ÉTAPE 2 : Génération des Clés
WireGuard fonctionne avec des clés privées et publiques.
Exécute ces commandes :
```
wg genkey | tee /etc/wireguard/privatekey | wg pubkey > /etc/wireguard/publickey
```
Puis, affiche les clés générées :
```
cat /etc/wireguard/privatekey
cat /etc/wireguard/publickey
```

Note-les quelque part, elles serviront plus tard ! 📝
________________________________________
📄 ÉTAPE 3 : Configuration du Serveur
Crée un fichier de configuration pour WireGuard :
```
sudo nano /etc/wireguard/wg0.conf
```
Et colle ceci (remplace PRIVATE_KEY_DU_SERVEUR par ta clé privée et le ListenPort = ton-port) :
```
[Interface]
PrivateKey = PRIVATE_KEY_DU_SERVEUR
Address = 10.0.0.1/24
ListenPort = 51820

# Activation du NAT pour accès au réseau local
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```
Sauvegarde avec CTRL + X, O puis Entrée.
________________________________________
🔥 ÉTAPE 4 : Activation et Démarrage
Active WireGuard au démarrage :
```
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0
```
Vérifie qu’il fonctionne :
```
sudo wg show
```

Si tu vois l’interface wg0 avec l’adresse IP, c’est que ça tourne ! ✅
________________________________________
🛜 ÉTAPE 5 : Ouvrir le Port sur le Routeur
Tu dois ouvrir le port 51820 ou autre en UDP sur ta box internet pour que le VPN soit accessible depuis l’extérieur.
Va dans l’interface de ta box et ajoute une redirection de port vers ton Raspberry Pi sur le port 51820 ou autre en UDP.

🛠️ 6. Installer les paquets nécessaires : si Apache, PHP, MySQL sont déjà installer passe les étapes : 6, 7 et 8 

Sur ton Raspberry Pi, commence par installer Apache, PHP, MySQL et d'autres outils utiles :
```
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mariadb-server mariadb-client php libapache2-mod-php php-mysql php-cli unzip git -y
```

🔧 ÉTAPE 7. Configurer MySQL (MariaDB)
Après l’installation, sécurise ton serveur MySQL :
```
sudo mysql_secure_installation
```

Réponds aux questions comme ceci :
•	Définir un mot de passe root : Oui (et mets un mot de passe sécurisé)
•	Supprimer les utilisateurs anonymes : Oui
•	Désactiver la connexion root à distance : Oui
•	Supprimer la base de test : Oui
•	Recharger les tables de privilèges : Oui

🌐 ÉTAPE 8. Configurer Apache
Activer Apache et le démarrer :
```
sudo systemctl enable apache2
sudo systemctl start apache2
```

Activer les modules utiles :
```
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Vérifier si Apache fonctionne :
•	Va sur ton navigateur et tape http://[IP-de-ton-Raspberry]
•	Tu devrais voir la page d’accueil Apache 🎉

🛠️ ÉTAPE 9. Installer iptables
```
sudo apt update && sudo apt install iptables -y
```
Puis, vérifie qu'il est bien installé avec :
```
iptables –version
```
Si la commande renvoie bien une version (iptables v1.x.x), c'est bon.


📝 10. Télécharger et configurer le projet web

📥 Cloner le projet sur le Raspberry Pi

1.	Ouvre un terminal sur ton Raspberry Pi.
2.	Place-toi dans le répertoire où tu veux stocker le projet : 

```
cd /var/www/html
```

3.	Clone le dépôt: 
```
git clone https://github.com/koops61/wireguard-manager.git
```

4.	Entre dans le dossier du projet : 
```
cd wireguard-manager
```

🎯 Configurer les permissions
Pour que le serveur web (Apache ou Nginx) puisse accéder aux fichiers :
```
sudo chown -R www-data:www-data /var/www/html/wireguard-manager
sudo chmod -R 755 /var/www/html/wireguard-manager
```
⚙️ ÉTAPE 11. Configuration web

🔄 Configurer la base de données

Si ce n'est pas encore fait, importe le fichier database.sql dans MySQL :
1.	Connecte-toi à MySQL : 
```
mysql -u root -p
```
2.	Crée une base de données : 
```
CREATE DATABASE wireguard_manager;
```
3.	Quitte MySQL et importe le fichier SQL : 

```
mysql -u root -p wireguard_manager < /var/www/html/wireguard-manager/db/database.sql
```

🔄 - Configure config.php :

Edite le fichier config.php qui se trouve dans le dossier ./config/ et assure-toi que les informations MySQL et WireGuard sont correctes :
```php
$db_host = 'localhost';
$db_name = 'wireguard_manager';
$db_user = 'root';
$db_pass = 'ton_mot_de_passe';
```

🔐 ÉTAPE 12. Sécurité : 
✅ Installer et Activer rsyslog (si ce n'est pas déjà fait) :
 
```
sudo apt update && sudo apt install rsyslog -y
```

Active et démarre rsyslog :
```
sudo systemctl enable rsyslog
sudo systemctl start rsyslog
```
Vérifie son statut :
```
sudo systemctl status rsyslog
```
✅ Il doit être active (running).
force-le en lançant cette commande :
bash
CopierModifier
sudo touch /var/log/auth.log
sudo chmod 644 /var/log/auth.log
sudo chown root:adm /var/log/auth.log
Puis redémarre rsyslog :
bash
CopierModifier
sudo systemctl restart rsyslog
________________________________________
✅ Vérifier la configuration SSH
Il est possible que SSH ne soit pas configuré pour générer des logs.
Édite le fichier de configuration SSH :
```
sudo nano /etc/ssh/sshd_config
```
Assure-toi que cette ligne est présente et non commentée (# devant = désactivé) :
```
LogLevel INFO
```
Sauvegarde (CTRL + X, O, Entrée).
Redémarre le service SSH :
```
sudo systemctl restart ssh
```

👉  1 Installe fail2ban pour bloquer les tentatives d’attaques sur SSH :
```
sudo apt update && sudo apt install fail2ban -y
```
Une fois installé, active-le :
```
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```
Vérifie que ça fonctionne :
```
sudo fail2ban-client status sshd
```

 Démarrer et activer Fail2Ban
```
sudo systemctl start fail2ban
```

 Active-le pour qu'il démarre au boot :

```
sudo systemctl enable fail2ban
```

Vérifie que le service tourne bien :

```
sudo systemctl status fail2ban
```

✅ Si tout est bon, tu devrais voir active (running).
________________________________________
🚀 SI ERREUR  Vérifier la configuration de Fail2Ban

Si Fail2Ban ne démarre pas, il peut y avoir une erreur dans sa configuration.

Vérifie le fichier de logs pour voir pourquoi il ne démarre pas :
```
sudo journalctl -u fail2ban --no-pager --lines=50
```

Vérifie la configuration avec :
```
sudo fail2ban-client -x start
```
Édite la configuration de sshd dans Fail2Ban :
```
sudo nano /etc/fail2ban/jail.local
```

Ajoute (ou modifie) cette section :
```
[sshd]
enabled = true
port = 2222
filter = sshd
logpath = /var/log/auth.log
maxretry = 5
bantime = 3600
findtime = 600
```

📌 Explication :

•	enabled = true → Active le filtrage SSH.

•	port = 2222 → Indique à Fail2Ban que ton serveur SSH écoute sur 2222.

•	logpath = /var/log/auth.log → C’est le bon fichier de logs pour SSH sur Debian/Raspbian.

•	maxretry = 5 → 5 échecs de connexion avant un bannissement.

•	bantime = 3600 → Bannissement de 1 heure.

•	findtime = 600 → Vérifie les tentatives échouées dans les 10 dernières minutes.

Sauvegarde et quitte (CTRL + X, O, Entrée).

✅ Si tout va bien, Fail2Ban démarre.
________________________________________

🔎 Vérifier que SSH est bien protégé

Après avoir démarré Fail2Ban, teste à nouveau :
```
sudo fail2ban-client status sshd
```

✅ Si ça fonctionne, tu verras quelque chose comme :
```
Status for the jail: sshd
|- Filter
|  |- Currently failed: 0
|  |- Total failed: 3
|  `- File list: /var/log/auth.log
`- Actions
   |- Currently banned: 1
   |- Total banned: 1
   `- Banned IP list: 192.168.1.100
```
Si des IPs apparaissent sous "Banned IP list", cela signifie que Fail2Ban bloque correctement les attaquants. 🚀

🚀 Débannir une IP si besoin

Si une IP légitime est bannie (exemple : 92.255.85.107), débannis-la avec :
```
sudo fail2ban-client set sshd unbanip 92.255.85.107
```
🔥 Installation et Configuration de ufw (Uncomplicated Firewall)

ufw (Uncomplicated Firewall) est un pare-feu simple qui permet de bloquer ou d'autoriser les connexions à ton Raspberry Pi.

________________________________________
✅ Installer ufw

Sur ton Raspberry Pi, exécute :
```
sudo apt update && sudo apt install ufw -y
```

________________________________________
🔥 Autoriser SSH et WireGuard

Avant d’activer ufw, assure-toi d’autoriser SSH et WireGuard, sinon tu risques de te bloquer toi-même.

Autoriser SSH (port 2222, modifié sur ton Raspberry Pi) :
```
sudo ufw allow 2222/tcp
```

Autoriser WireGuard (port 51820 UDP par défaut) :
```
sudo ufw allow 51820/udp
```

Autoriser le trafic local sur le VPN (ex : 10.0.0.0/24) :
```
sudo ufw allow from 10.0.0.0/24
```

Autoriser le trafic depuis ton réseau local (192.168.1.0/24) :
```
sudo ufw allow from 192.168.1.0/24
```

________________________________________
🚀 Activer ufw

Une fois les règles définies, active le pare-feu :
```
sudo ufw enable
```

🔴 ⚠️ ATTENTION : Si tu n’as pas autorisé SSH (ufw allow 2222/tcp), tu risques de bloquer ta connexion !

Vérifie que ufw fonctionne bien :
```
sudo ufw status verbose
```

✅ Tu devrais voir quelque chose comme :
```
Status: active

To                         Action      From
--                         ------      ----
2222/tcp                   ALLOW       Anywhere
51820/udp                   ALLOW       Anywhere
10.0.0.0/24                ALLOW       Anywhere
192.168.1.0/24             ALLOW       Anywhere
```

________________________________________

🔒 Ajouter des règles supplémentaires (facultatif)

Si tu veux bloquer tout le trafic entrant sauf les ports autorisés, exécute :
```
sudo ufw default deny incoming
sudo ufw default allow outgoing
```
Cela empêche toutes les connexions non autorisées d’entrer.

Si tu veux voir toutes les règles en place :
```
sudo ufw status numbered
```

Si jamais tu veux supprimer une règle (par exemple la numéro 2) :
```
sudo ufw delete 2
```

________________________________________
🛠 Tester si le pare-feu bloque bien les attaques

Une fois ufw activé, surveille les connexions :
```
sudo journalctl -u ufw --no-pager --lines=50
```

Et regarde si Fail2Ban continue de bien bannir les IPs suspectes :
```
sudo fail2ban-client status sshd
```


🔄 2 - Préparation du mot de passe de connexion :

Génère ton hash directement en ligne de commande sur le serveur :
```bash
php /var/www/html/wireguard-manager/hash_password.php "ton_mot_de_passe"
```
(ou ouvre la page `http://ip-de-ton-serveur/wireguard-manager/hash_password.php` dans ton navigateur).

Copie le hash généré, puis supprime le fichier pour des raisons de sécurité :
```bash
rm /var/www/html/wireguard-manager/hash_password.php
```

🔄 3 - Configuration de config_login.php :

Edite le fichier `config_login.php` qui se trouve dans le dossier `./config/` :
Tu dois indiquer ton nom d'utilisateur et le mot de passe hashé généré :

```php
return [
    'username' => 'admin',
    'password_hash' => '$2y$10$...' // colle ici le hash généré
];
```

🔧 Bonus : Activer le Routage pour Accès au Réseau Local
Si tu veux accéder à ton réseau local (NAS, PC, imprimante...), active le routage :
```
echo "net.ipv4.ip_forward = 1" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

🚀 Utilisation
Ouvre le site web depuis ton navigateur :
http://ip-de-ton-serveur/wireguard-manager/

renseigne ton nom d'utilisateur ainsi que ton mot de passe créer à l'étape 12

 « Attention ton mot de passe est non le PASS-HASH »


![Capture d'écran 2025-03-14 020539](https://github.com/user-attachments/assets/f28e42bc-9d08-452a-9729-91585dbdbb14)

 

Tu réussis à te connecter youpi 😉
tu dois aller maintenant sur ton site pour supprimer le fichier hash_password.php : 
```
cd /var/www/html/wireguard-manager/
rm hash_password.php
```

Depuis l’interface :

Ajouter un client
Télécharger la configuration
Supprimer un client
Voir la liste des clients autorisés
-------------------

📌 À venir
✅ Téléchargement du QR Code pour les smartphones
✅ Téléchargement ZIP automatique de la configuration
✅ Ajout de filtres/sort dans l’interface
✅ Détection automatique de doublons d'IP
📄 Licence
Ce projet est sous licence MIT — tu peux l’utiliser, le modifier et le redistribuer librement.

🤝 Contribuer
Pull requests bienvenues !
Si tu veux proposer une amélioration, n'hésite pas à ouvrir une issue.

📫 Contact
Projet réalisé avec ❤️ par koops61 (kanis)
Pour toute aide, idée ou retour : ouvre une issue ou envoie un message.
