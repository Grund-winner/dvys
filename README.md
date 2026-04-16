# DVYS AI - Configuration de déploiement

## Plateforme IA conversationnelle pour casino et paris sportifs

### Déploiement sur Render

1. Connectez le repo GitHub à Render
2. Render détectera automatiquement PHP
3. Configurez les variables d'environnement :

### Variables d'environnement requises

| Variable | Description | Obligatoire |
|----------|-------------|-------------|
| `APP_SECRET` | Clé secrète pour les sessions (auto-générée) | Oui |
| `OPENAI_API_KEY` | Clé API OpenAI pour le chat IA | Oui |
| `OPENAI_MODEL` | Modèle IA (défaut: gpt-3.5-turbo) | Non |
| `BASE_URL` | URL de votre site | Oui |
| `ADMIN_USERNAME` | Nom d'utilisateur admin | Non |
| `ADMIN_PASSWORD_HASH` | Hash du mot de passe admin | Non |

### Configuration 1Win Partners

Postback URL à configurer dans 1Win Partners :
```
https://votre-domaine.com/postback.php?event={event}&sub1={sub1}&amount={amount}
```

### Structure

- `index.php` - Landing page
- `dashboard.php` - Tableau de bord utilisateur
- `chat.php` - Chat IA
- `predictions.php` - Pronostics foot
- `referrals.php` - Système de parrainage
- `admin/` - Panneau d'administration
- `api/` - Endpoints API
- `postback.php` - Réception des notifications 1Win

### Premier utilisateur = Admin

Le premier compte créé sur la plateforme aura automatiquement les droits admin.
