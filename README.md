# 🔐 WireGuard Manager

**WireGuard Manager** est une interface web simple et efficace pour gérer vos connexions VPN WireGuard depuis un serveur (ex. Raspberry Pi). Il permet d'ajouter, supprimer, afficher et gérer facilement les clients via une base de données et une interface PHP/MySQL.

![image](https://github.com/user-attachments/assets/9c163a2b-cc16-4dd7-a4b1-a1218dc9a7a5)


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
🛠️ 1. Installer les paquets nécessaires : si Apache, PHP, MySQL sont déjà installer passe les étapes 1, 2 et 3

Sur ton Raspberry Pi, commence par installer Apache, PHP, MySQL et d'autres outils utiles :
```
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mariadb-server mariadb-client php libapache2-mod-php php-mysql php-cli unzip git -y
```

🔧 2. Configurer MySQL (MariaDB)
Après l’installation, sécurise ton serveur MySQL :
``sudo mysql_secure_installation``

Réponds aux questions comme ceci :
•	Définir un mot de passe root : Oui (et mets un mot de passe sécurisé)
•	Supprimer les utilisateurs anonymes : Oui
•	Désactiver la connexion root à distance : Oui
•	Supprimer la base de test : Oui
•	Recharger les tables de privilèges : Oui

🌐 3. Configurer Apache
Activer Apache et le démarrer :
``
sudo systemctl enable apache2
sudo systemctl start apache2
``

Activer les modules utiles :
``
sudo a2enmod rewrite
sudo systemctl restart apache2
``

Vérifier si Apache fonctionne :
•	Va sur ton navigateur et tape http://[IP-de-ton-Raspberry]
•	Tu devrais voir la page d’accueil Apache 🎉

📝 4. Télécharger et configurer le projet web
•	Place ton site web dans le dossier Apache :


📥 Cloner le projet sur le Raspberry Pi

1.	Ouvre un terminal sur ton Raspberry Pi.
2.	Place-toi dans le répertoire où tu veux stocker le projet : 

``cd /var/www/html``

3.	Clone le dépôt: 
``git clone https://github.com/koops61/wireguard-manager.git``

4.	Entre dans le dossier du projet : 
``cd wireguard-manager``

🎯 Configurer les permissions
Pour que le serveur web (Apache ou Nginx) puisse accéder aux fichiers :
``
sudo chown -R www-data:www-data /var/www/html/wireguard-manager
sudo chmod -R 755 /var/www/html/wireguard-manager
``
⚙️ Configuration
🔄 Configurer la base de données
Si ce n'est pas encore fait, importe le fichier database.sql dans MySQL :
1.	Connecte-toi à MySQL : 
``mysql -u root -p``
2.	Crée une base de données : 
``CREATE DATABASE wireguard_manager;``
3.	Quitte MySQL et importe le fichier SQL : 

``mysql -u root -p wireguard_manager < /var/www/html/wireguard-manager/db/database.sql``

2 - Configure config.php :
Ouvre config.php et assure-toi que les informations MySQL sont correctes :

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
