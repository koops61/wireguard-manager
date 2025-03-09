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
- Un nom de domaine ou un accès à distance (ex. `kaniberry.ddns.net`)

---

## 📦 Installation

```bash
# Accéder au répertoire web
cd /var/www/html

# Cloner le dépôt
git clone https://github.com/koops61/wireguard-manager.git

# Aller dans le dossier
cd wireguard-manager

⚙️ Configuration
1 - Crée ta base de données MySQL :
2 - Configure config.php :


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

➡ Un KillSwitch WireGuard est disponible dans le répertoire /tools/ pour bloquer le trafic hors tunnel (Windows .bat).

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
