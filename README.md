# 🔐 WireGuard Manager

**WireGuard Manager** est une interface web simple et efficace pour gérer vos connexions VPN WireGuard depuis un serveur (ex. Raspberry Pi). Il permet d'ajouter, supprimer, afficher et gérer facilement les clients via une base de données et une interface PHP/MySQL.
 

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

🔧 7. Configurer MySQL (MariaDB)
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

🌐 8. Configurer Apache
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

📝 9. Télécharger et configurer le projet web

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

5.	Entre dans le dossier du projet : 
```
cd wireguard-manager
```

🎯 Configurer les permissions
Pour que le serveur web (Apache ou Nginx) puisse accéder aux fichiers :
```
sudo chown -R www-data:www-data /var/www/html/wireguard-manager
sudo chmod -R 755 /var/www/html/wireguard-manager
```
⚙️ Configuration web

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

Edite le fichier config.php qui se trouve dans le dossier ./conf/ et assure-toi que les informations MySQL sont correctes :
```
// Configuration de la connexion à la base de données
$host = 'localhost';
$dbname = 'wireguard_manager';
$username = 'root';
$password = 'password';
```
🔄 - préparation pour une connexion sécurisée : 
Edite le fichier hash_password.php qui se trouve a la racine de ton site
```

<?php
echo password_hash("ton-mdp-ici-et ouvre dans une page web sur le srv pour cree le mdp en hash en suite copie colle dans config_login.php", PASSWORD_BCRYPT);
?>
```
et ouvre le dans une page web sur ton site Pour générer ton password en hash 
ex : 
http://ip-de-ton-serveur/wireguard-manager/hash_password.php
Note-le quelque part, il servira à l'étape suivante ! 📝

🔄 - Configure config_login.php :

Edite le fichier config_login.php qui se trouve dans le dossier ./conf/ 
Tu dois indiquer un nom d'utilisateur et un password hash  que tu as généré au préalable
attention à ne jamais mettre ton mots de passe En clair  ici:
```
    'username' => 'user',
    'password_hash' => 'password-hash-here'
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

Depuis l’interface :

Ajouter un client
Télécharger la configuration
Supprimer un client
Voir la liste des clients autorisés

🔐 Sécurité
➡ Pense à restreindre l'accès à ton interface via un .htpasswd ou une authentification.

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
