<?php

namespace App\Twig;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\KeyManagement\Envelope;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Outil de démonstration : montre ce que la base contient réellement.
 *
 * Rien ici ne fait partie de la fonctionnalité. Ces fonctions servent uniquement
 * à afficher, à côté de l'application, ce que verrait quelqu'un qui lit la base.
 */
class DemoExtension
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Les lignes brutes d'une table, sans passer par Doctrine ORM.
     */
    #[AsTwigFunction('raw_rows')]
    public function rawRows(string $table): array
    {
        $connection = $this->em->getConnection();

        return $connection->fetchAllAssociative('SELECT * FROM '.$connection->quoteSingleIdentifier($table));
    }

    /**
     * Les champs d'une entité dont la colonne est chiffrée.
     */
    #[AsTwigFunction('encrypted_fields')]
    public function encryptedFields(string $class): array
    {
        $metadata = $this->em->getClassMetadata($class);

        return array_values(array_filter(
            $metadata->getFieldNames(),
            fn (string $field) => str_starts_with((string) $metadata->getTypeOfField($field), 'encrypted_'),
        ));
    }

    /**
     * La valeur que Doctrine envoie à la base pour ce champ, par exemple dans un WHERE.
     */
    #[AsTwigFunction('doctrine_value')]
    public function doctrineValue(string $class, string $field, mixed $value): mixed
    {
        $type = Type::getType($this->em->getClassMetadata($class)->getTypeOfField($field));

        return $type->convertToDatabaseValue($value, $this->em->getConnection()->getDatabasePlatform());
    }

    /**
     * Décrit une valeur brute telle qu'on la verrait dans un dump.
     */
    #[AsTwigFilter('dump_cell')]
    public function dumpCell(mixed $value): array
    {
        if (null === $value) {
            return ['type' => 'null'];
        }

        $value = (string) $value;

        // Du texte lisible : affiché tel quel
        if (preg_match('//u', $value) && !preg_match('/[\x00-\x08\x0E-\x1F]/', $value)) {
            return ['type' => 'text', 'value' => $value];
        }

        // Des octets : on affiche la fin en hexadécimal (le début est un en-tête commun)
        return [
            'type' => 'binary',
            'hex' => self::hex(substr($value, -4)),
            'length' => \strlen($value),
            'ref' => self::reference($value),
        ];
    }

    /**
     * Les clés de données rangées par le store, avec le nombre de valeurs que chacune protège.
     */
    #[AsTwigFunction('data_keys')]
    public function dataKeys(array $rows): array
    {
        $connection = $this->em->getConnection();
        if (!$connection->createSchemaManager()->tablesExist(['key_management_data_keys'])) {
            return [];
        }

        $uses = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                if (\is_string($value) && null !== $ref = self::reference($value)) {
                    $uses[$ref] = ($uses[$ref] ?? 0) + 1;
                }
            }
        }

        $keys = [];
        foreach ($connection->fetchAllAssociative('SELECT id, scope, master_key_id, key_material FROM key_management_data_keys ORDER BY id') as $key) {
            $ref = bin2hex($key['id']);
            $keys[] = [
                'ref' => $ref,
                'id' => substr($ref, -8),
                'scope' => $key['scope'],
                'master_key_id' => $key['master_key_id'],
                'key_material' => self::hex(substr($key['key_material'], -4)),
                'uses' => $uses[$ref] ?? 0,
            ];
        }

        return $keys;
    }

    private static function hex(string $bytes): string
    {
        return implode(' ', str_split(bin2hex($bytes), 2));
    }

    // La référence de la clé de données que porte une valeur chiffrée avec le store
    private static function reference(string $value): ?string
    {
        if (!class_exists(Envelope::class)) {
            return null;
        }

        try {
            $reference = Envelope::fromBytes($value)->reference;
        } catch (\Throwable) {
            return null;
        }

        return null !== $reference ? bin2hex($reference) : null;
    }
}
