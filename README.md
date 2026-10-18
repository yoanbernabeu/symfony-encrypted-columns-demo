# Encrypted Doctrine Columns : démo Symfony 8.2

Projet de démonstration de l'article **[Colonnes Doctrine chiffrées avec Symfony 8.2 : votre base peut fuiter, pas vos données](https://yoandev.co/symfony-colonnes-doctrine-chiffrees)** sur yoandev.co.

On part d'un petit carnet de clients, puis on chiffre ses colonnes sensibles avec le composant **KeyManagement** de Symfony 8.2 : l'application continue de lire les données en clair, alors que la base ne contient plus que des octets illisibles. Enfin, on rend l'email de nouveau cherchable grâce à un **blind index**.

La page affiche côte à côte ce que voit l'application et ce que verrait quelqu'un qui a volé la base.

> [!WARNING]
> KeyManagement est un composant **expérimental**. Ce dépôt a été testé sur Symfony `8.2.x-dev` en octobre 2026, avant la sortie de Symfony 8.2. Il est figé dans cet état : le `composer.lock` installe exactement les versions testées.

## Prérequis

- PHP 8.4 ou plus récent, avec les extensions `sodium`, `openssl` et `pdo_sqlite`
- Composer
- Optionnel : le [Symfony CLI](https://symfony.com/download) et `sqlite3` pour explorer la base

## Les étapes

Chaque étape de l'article correspond à une branche, qui contient l'état du code **à la fin** de cette étape :

| Branche | État du code |
|---|---|
| `etape-0-depart` | L'application de départ, sans chiffrement |
| `etape-1-installation` | Le composant KeyManagement installé et configuré, rien n'est encore chiffré |
| `etape-2-chiffrement` | L'email, le téléphone et la date de naissance chiffrés avec `#[Encrypted]` |
| `etape-3-blind-index` | La recherche par email rétablie avec `#[BlindIndexed]` (identique à `main`) |

Pour voir ce qu'une étape a changé :

```bash
git diff etape-1-installation etape-2-chiffrement
```

## Installation

```bash
git clone https://github.com/yoanbernabeu/symfony-encrypted-columns-demo.git
cd symfony-encrypted-columns-demo
git checkout etape-0-depart   # ou la branche de votre choix
composer install
```

### Générer les clés (à partir de `etape-1-installation`)

Les clés ne sont jamais commitées : chacun génère les siennes dans `.env.local`.

La clé maître du KMS local Sodium :

```bash
echo "DEV_KMS_KEY=$(php -r 'echo sodium_bin2base64(sodium_crypto_aead_xchacha20poly1305_ietf_keygen(), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);')" > .env.local
```

À partir de `etape-3-blind-index`, la clé du blind index. La commande affiche un JSON : seule la valeur `wrapped` (la clé chiffrée par la clé maître) va dans `.env.local`.

```bash
php bin/console key-management:generate-data-key app-key
# copier la valeur "wrapped" dans .env.local :
# APP_INDEX_KEY=...
```

Ou en une ligne :

```bash
php bin/console key-management:generate-data-key app-key | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo "APP_INDEX_KEY=".$j["wrapped"]."\n";' >> .env.local
```

> [!CAUTION]
> Si vous perdez `DEV_KMS_KEY`, les données chiffrées sont perdues : personne ne peut plus les déchiffrer. Dans ce projet de démo, il suffit de recréer la base.

### Créer la base et lancer l'application

```bash
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
php -S 127.0.0.1:8000 -t public   # ou : symfony serve
```

Puis ouvrir http://127.0.0.1:8000.

### Changer de branche

Les migrations et les données diffèrent d'une étape à l'autre : le plus simple est de repartir d'une base neuve.

```bash
git checkout etape-2-chiffrement
composer install
rm -f var/data.db
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

## Explorer la base

```bash
sqlite3 var/data.db ".dump customer"
sqlite3 var/data.db "SELECT scope, master_key_id FROM key_management_data_keys"
grep -a -c "ada@example.com" var/data.db   # 1 avant le chiffrement, 0 après
```

## Ce qui fait partie de la fonctionnalité, et ce qui sert à la démo

- **La fonctionnalité** : `src/Entity/Customer.php`, `config/packages/key_management.yaml`, le service du blind index dans `config/services.yaml` et la recherche dans `src/Controller/CustomerController.php`. C'est ce que vous reproduirez dans votre propre projet.
- **L'outil de démo** : `src/Twig/DemoExtension.php` et les templates. Ils lisent la base sans passer par Doctrine ORM pour afficher ce qu'elle contient réellement. Vous n'en aurez pas besoin dans une vraie application.

## Versions testées

| Paquet | Version |
|---|---|
| `symfony/framework-bundle` | `8.2.x-dev` (`68af6c6`) |
| `symfony/key-management` | `8.2.x-dev` (`bbeb847`) |
| `symfony/doctrine-dbal-key-management` | `8.2.x-dev` (`ab94626`) |
| `symfony/doctrine-orm-key-management` | `8.2.x-dev` (`56cb8c3`) |
| `doctrine/orm` | `3.7.5` |
| `doctrine/dbal` | `4.5.0` |
| `doctrine/doctrine-bundle` | `3.3.2` |
