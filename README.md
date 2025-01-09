# FedeProject-CMA: Application de Gestion de Contacts 📇

[![Version PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![Version Symfony](https://img.shields.io/badge/Symfony-7.1.8-black?logo=symfony)](https://symfony.com)
[![Licence: MIT](https://img.shields.io/badge/Licence-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Maintenance](https://img.shields.io/badge/Maintenu%3F-oui-green.svg)](https://github.com/Ryvexam/FedeProject-CMA/graphs/commit-activity)

<p align="center">
  <img src="https://symfony.com/logos/symfony_black_02.png" height="120" alt="Logo Symfony">
</p>

## 🌟 À propos

**FedeProject-CMA** est une application web de gestion de contacts construite avec Symfony 7.1.8.

### ✨ Fonctionnalités principales
- 👤 Créer, mettre à jour et supprimer des contacts et des groupes facilement
- 👥 Organiser les contacts en groupes personnalisables
- 🔄 Importer des contacts directement depuis Google Contacts
- 🎨 Interface utilisateur épurée et intuitive

## 🛠️ Prérequis

- 🐘 **PHP** : 8.2 ou supérieur
- 📦 **Composer** : Gestionnaire de dépendances PHP
- ⚒️ **Symfony CLI** : Pour exécuter et gérer les projets Symfony
- 🗄️ **SQLite** : Système de base de données utilisé dans ce projet

## 🚀 Installation

### 1️⃣ Cloner le projet
```bash
git clone https://github.com/Ryvexam/FedeProject-CMA.git
cd FedeProject-CMA
```

### 2️⃣ Installer les dépendances
```bash
composer install
```

### 3️⃣ Configurer l'environnement
Créez et configurez votre environnement local :
```bash
cp .env .env.local
```
Mettez à jour la configuration de la base de données :
```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### 4️⃣ Initialiser la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5️⃣ Lancer le serveur
```bash
symfony server:start
```
Accédez à votre application sur [http://localhost:8000](http://localhost:8000) 🌐

## 🛠️ Commandes utiles

- 🔥 **Démarrer le serveur Symfony** :
  ```bash
  symfony server:start
  ```

- 🔍 **Valider le schéma de la base de données** :
  ```bash
  php bin/console doctrine:schema:validate
  ```

## 🔄 Intégration Google Contacts

Pour activer la fonctionnalité d'importation depuis Google Contacts, configurez vos identifiants OAuth Google dans `.env.local` :
```env
GOOGLE_CLIENT_ID=votre-client-id
GOOGLE_CLIENT_SECRET=votre-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/google/callback
```

## 🏗️ Structure du projet

Le projet suit la structure standard de Symfony :
- 📁 `src/` : Contrôleurs, entités et services
- 📁 `templates/` : Templates de vue Twig
- 📁 `public/` : Assets publics et point d'entrée

## 👥 Contribuer

Les contributions sont les bienvenues ! N'hésitez pas à :
- 🐛 Signaler des bugs
- 💡 Suggérer des fonctionnalités
- 🔧 Soumettre des pull requests

## 📬 Contact

- Développeur : [Ryvexam](https://github.com/Ryvexam)
- Lien du projet : [https://github.com/Ryvexam/FedeProject-CMA](https://github.com/Ryvexam/FedeProject-CMA)

---

⭐ N'oubliez pas de mettre une étoile à ce dépôt si vous le trouvez utile !